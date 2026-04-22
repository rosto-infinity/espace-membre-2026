---
source_course: "php-databases"
source_lesson: "php-databases-pdo-fetch-modes"
---

# Les Modes de Récupération PDO & l'Hydratation

PDO offre **plusieurs façons de récupérer des données**. Choisissez le bon mode de récupération selon votre cas d'usage.

## Les Modes de Récupération

```php
<?php
$stmt = $pdo->query('SELECT id, name, email FROM users');

// FETCH_ASSOC - Tableau associatif (recommandé)
$user = $stmt->fetch(PDO::FETCH_ASSOC);
// ['id' => 1, 'name' => 'Jean', 'email' => 'jean@example.com']

// FETCH_NUM - Tableau numérique
$user = $stmt->fetch(PDO::FETCH_NUM);
// [1, 'Jean', 'jean@example.com']

// FETCH_BOTH - Les deux clés (par défaut, gaspilleur)
$user = $stmt->fetch(PDO::FETCH_BOTH);
// ['id' => 1, 0 => 1, 'name' => 'Jean', 1 => 'Jean', ...]

// FETCH_OBJ - Objet stdClass
$user = $stmt->fetch(PDO::FETCH_OBJ);
// objet avec $user->id, $user->name, $user->email
```

## Récupération dans des Objets (FETCH_CLASS)

```php
<?php
class User {
    public int $id;
    public string $name;
    public string $email;
    private ?string $passwordHash = null;

    public function getDisplayName(): string {
        return $this->name;
    }
}

// FETCH_CLASS - Hydrater dans une classe
$stmt = $pdo->query('SELECT id, name, email FROM users');
$stmt->setFetchMode(PDO::FETCH_CLASS, User::class);

foreach ($stmt as $user) {
    echo $user->getDisplayName();  // La méthode est disponible !
}

// Avec des arguments de constructeur
$stmt->setFetchMode(
    PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE,
    User::class,
    ['arg1Constructeur', 'arg2Constructeur']
);
```

## Les Variations de fetchAll()

```php
<?php
// Toutes les lignes en tableau de tableaux associatifs
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Une seule colonne de toutes les lignes
$emails = $stmt->fetchAll(PDO::FETCH_COLUMN, 2);  // Index de colonne
// ['jean@example.com', 'marie@example.com', ...]

// Paires clé-valeur (première colonne comme clé)
$stmt = $pdo->query('SELECT id, name FROM users');
$names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
// [1 => 'Jean', 2 => 'Marie', ...]

// Groupé par la première colonne
$stmt = $pdo->query('SELECT role, id, name FROM users');
$byRole = $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_ASSOC);
// ['admin' => [['id' => 1, 'name' => 'Jean']], 'user' => [...]]

// Unique par la première colonne
$stmt = $pdo->query('SELECT id, name, email FROM users');
$indexed = $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC);
// [1 => ['name' => 'Jean', 'email' => '...'], 2 => [...]]
```

## Hydratation Personnalisée

```php
<?php
// FETCH_FUNC - Callback personnalisé
$stmt = $pdo->query('SELECT id, name, email FROM users');
$users = $stmt->fetchAll(PDO::FETCH_FUNC, function($id, $name, $email) {
    return new User($id, $name, $email);
});
```

## Exemple Concret

**Repository avec différents patterns de récupération**

```php
<?php
declare(strict_types=1);

// Repository avec méthodes de récupération typées
class UserRepository {
    public function __construct(
        private PDO $pdo
    ) {}

    public function findById(int $id): ?User {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, email, created_at FROM users WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);

        return $stmt->fetch() ?: null;
    }

    /** @return User[] */
    public function findAll(): array {
        $stmt = $this->pdo->query(
            'SELECT id, name, email, created_at FROM users ORDER BY name'
        );
        $stmt->setFetchMode(PDO::FETCH_CLASS, User::class);

        return $stmt->fetchAll();
    }

    /** @return array<int, string> */
    public function getEmailsById(): array {
        $stmt = $this->pdo->query('SELECT id, email FROM users');
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /** @return array<string, User[]> */
    public function groupByStatus(): array {
        $stmt = $this->pdo->query(
            'SELECT status, id, name, email, created_at FROM users ORDER BY status, name'
        );

        $grouped = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $status = $row['status'];
            unset($row['status']);
            $grouped[$status][] = $this->hydrate($row);
        }

        return $grouped;
    }

    private function hydrate(array $data): User {
        $user = new User();
        $user->id = (int) $data['id'];
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->createdAt = new DateTimeImmutable($data['created_at']);
        return $user;
    }
}
?>
```

## Les Grimoires

- [PDOStatement::fetch (Documentation Officielle)](https://www.php.net/manual/en/pdostatement.fetch.php)

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
