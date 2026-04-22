---
source_course: "php-security"
source_lesson: "php-security-prepared-statements"
---

# Les Requêtes Préparées : La Solution

Les requêtes préparées **préviennent complètement l'injection SQL** en séparant le code SQL des données.

## Comment Fonctionnent les Requêtes Préparées

```
1. PRÉPARER : Envoyer le template SQL à la base de données
   "SELECT * FROM users WHERE email = ?"

2. LIER : Envoyer les données séparément
   Données : "user@example.com"

3. EXÉCUTER : La base de données les combine en toute sécurité
   Les données ne sont JAMAIS interprétées comme du SQL
```

## Requêtes Préparées PDO

### Paramètres Nommés

```php
<?php
$email = $_POST['email'];  // Même si malveillant

$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

// L'entrée malveillante "'; DROP TABLE users;--"
// est traitée comme une CHAÎNE LITTÉRALE, pas du SQL !
```

### Paramètres Positionnels

```php
<?php
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND status = ?');
$stmt->execute([$email, 'active']);
```

### INSERT avec Requêtes Préparées

```php
<?php
$stmt = $pdo->prepare(
    'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password)'
);

$stmt->execute([
    'name' => $name,
    'email' => $email,
    'password' => password_hash($password, PASSWORD_DEFAULT)
]);
```

## Ce qu'on NE PEUT PAS Paramétrer

Les paramètres fonctionnent seulement pour les DONNÉES, pas pour la structure SQL :

```php
<?php
// MAUVAIS : On ne peut pas paramétrer les noms de tables/colonnes
$stmt = $pdo->prepare('SELECT * FROM :table');  // Erreur !
$stmt = $pdo->prepare('SELECT * FROM users ORDER BY :column');  // Erreur !

// BON : Liste blanche pour les parties SQL dynamiques
$allowedColumns = ['name', 'email', 'created_at'];
$orderBy = in_array($_GET['sort'], $allowedColumns, true)
    ? $_GET['sort']
    : 'created_at';

$stmt = $pdo->prepare("SELECT * FROM users ORDER BY $orderBy");
```

## Erreurs Courantes

```php
<?php
// MAUVAIS : Concaténation d'entrée utilisateur
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = " . $_GET['id']);

// MAUVAIS : Utiliser query() avec entrée utilisateur
$pdo->query("SELECT * FROM users WHERE id = {$_GET['id']}");

// MAUVAIS : Préparé mais sans utiliser les paramètres
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = '$id'");

// BON : Toujours utiliser les paramètres pour les données utilisateur
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id');
$stmt->execute(['id' => $_GET['id']]);
```

## Exemple Complet

**Wrapper de base de données avec identifiants validés**

```php
<?php
declare(strict_types=1);

// Wrapper de base de données sécurisé
class SecureDatabase {
    public function __construct(
        private PDO $pdo
    ) {
        // S'assurer que PDO lance des exceptions
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // Désactiver les préparations émulées pour des vraies préparations côté serveur
        $this->pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function findOne(string $table, array $conditions): ?array {
        $this->validateTableName($table);

        $where = [];
        foreach (array_keys($conditions) as $column) {
            $this->validateColumnName($column);
            $where[] = "$column = :$column";
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE %s LIMIT 1',
            $table,
            implode(' AND ', $where)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($conditions);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function insert(string $table, array $data): int {
        $this->validateTableName($table);

        $columns = [];
        $placeholders = [];

        foreach (array_keys($data) as $column) {
            $this->validateColumnName($column);
            $columns[] = $column;
            $placeholders[] = ":$column";
        }

        $sql = sprintf(
            'INSERT INTO %s (%s) VALUES (%s)',
            $table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    private function validateTableName(string $table): void {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $table)) {
            throw new InvalidArgumentException('Nom de table invalide');
        }
    }

    private function validateColumnName(string $column): void {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column)) {
            throw new InvalidArgumentException('Nom de colonne invalide');
        }
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
