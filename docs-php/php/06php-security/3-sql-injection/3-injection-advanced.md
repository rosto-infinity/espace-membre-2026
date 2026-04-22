---
source_course: "php-security"
source_lesson: "php-security-sql-injection-advanced"
---

# Prévention Avancée de l'Injection SQL

Au-delà des requêtes préparées de base, apprenez les **techniques pour les requêtes complexes et les cas limites**.

## Sélection Dynamique de Colonnes

```php
<?php
class SafeQueryBuilder
{
    private const ALLOWED_COLUMNS = [
        'users' => ['id', 'name', 'email', 'created_at', 'status'],
        'orders' => ['id', 'user_id', 'total', 'status', 'created_at'],
    ];

    private const ALLOWED_ORDER = ['ASC', 'DESC'];

    public function select(string $table, array $columns): string
    {
        // Valider la table
        if (!isset(self::ALLOWED_COLUMNS[$table])) {
            throw new InvalidArgumentException('Table invalide');
        }

        // Valider les colonnes
        $allowedCols = self::ALLOWED_COLUMNS[$table];
        $safeCols = array_intersect($columns, $allowedCols);

        if (empty($safeCols)) {
            $safeCols = $allowedCols;  // Par défaut, toutes les colonnes autorisées
        }

        return sprintf(
            'SELECT %s FROM %s',
            implode(', ', array_map(fn($c) => "`$c`", $safeCols)),
            "`$table`"
        );
    }

    public function orderBy(string $column, string $direction): string
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, self::ALLOWED_ORDER, true)) {
            $direction = 'ASC';
        }

        // La colonne doit être validée contre la liste autorisée ailleurs
        return "ORDER BY `$column` $direction";
    }
}
```

## Clause IN Sécurisée

```php
<?php
function findByIds(PDO $pdo, array $ids): array
{
    // Filtrer seulement les entiers
    $ids = array_filter($ids, 'is_int');

    if (empty($ids)) {
        return [];
    }

    // Créer des placeholders
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id IN ($placeholders)");
    $stmt->execute(array_values($ids));

    return $stmt->fetchAll();
}

// Version avec paramètres nommés
function findByIdsNamed(PDO $pdo, array $ids): array
{
    $params = [];
    $placeholders = [];

    foreach ($ids as $i => $id) {
        $key = ":id$i";
        $placeholders[] = $key;
        $params[$key] = $id;
    }

    $sql = 'SELECT * FROM users WHERE id IN (' . implode(',', $placeholders) . ')';
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}
```

## Requêtes LIKE Sécurisées

```php
<?php
function searchUsers(PDO $pdo, string $term): array
{
    // Échapper les caractères spéciaux de LIKE
    $term = addcslashes($term, '%_\\');

    $stmt = $pdo->prepare(
        'SELECT * FROM users WHERE name LIKE :term ESCAPE "\\"'
    );
    $stmt->execute(['term' => '%' . $term . '%']);

    return $stmt->fetchAll();
}
```

## Prévenir l'Injection SQL de Second Ordre

```php
<?php
// Second ordre : Les données malveillantes sont stockées, puis utilisées de manière non sécurisée

// MAUVAIS : Données de la base de données utilisées sans paramétrage
$user = $pdo->query("SELECT * FROM users WHERE id = 1")->fetch();
$orders = $pdo->query("SELECT * FROM orders WHERE user_name = '{$user['name']}'");
// Si user.name contient une injection, elle s'exécute !

// BON : Toujours utiliser les requêtes préparées, même pour les données "de confiance"
$stmt = $pdo->prepare('SELECT * FROM orders WHERE user_name = ?');
$stmt->execute([$user['name']]);
```

## Paramètres de Sécurité PDO

```php
<?php
$pdo = new PDO($dsn, $user, $pass, [
    // Lever des exceptions sur les erreurs
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    // Désactiver les préparations émulées (utiliser de vraies requêtes préparées)
    PDO::ATTR_EMULATE_PREPARES => false,

    // Retourner les entiers comme int, pas comme string
    PDO::ATTR_STRINGIFY_FETCHES => false,
]);

// Pourquoi désactiver les préparations émulées ?
// Émulé : PHP échappe les valeurs, envoie la requête complète
// Réel : La base de données reçoit la requête + paramètres séparément
// Les vraies requêtes préparées sont plus sûres et peuvent être réutilisées
```

## Procédures Stockées (Couche Supplémentaire)

```sql
-- Procédure stockée MySQL
DELIMITER //
CREATE PROCEDURE GetUserByEmail(IN p_email VARCHAR(255))
BEGIN
    SELECT id, name, email FROM users WHERE email = p_email;
END //
DELIMITER ;
```

```php
<?php
// Appel depuis PHP
$stmt = $pdo->prepare('CALL GetUserByEmail(:email)');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();
```

## Les Grimoires

- [Requêtes Préparées PDO (Documentation Officielle)](https://www.php.net/manual/en/pdo.prepared-statements.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
