---
source_course: "php-performance"
source_lesson: "php-performance-query-optimization"
---

# Techniques d'Optimisation des Requêtes

Les requêtes base de données sont souvent le plus grand goulot d'étranglement.

## Stratégie d'Indexation

```sql
-- Index simple
CREATE INDEX idx_users_email ON users(email);

-- Index composite (l'ordre est important !)
CREATE INDEX idx_orders_user_status ON orders(user_id, status);

-- Index couvrant (inclut toutes les colonnes nécessaires)
CREATE INDEX idx_users_search ON users(email, name, created_at);

-- Index partiel (PostgreSQL)
CREATE INDEX idx_active_users ON users(email) WHERE status = 'active';
```

## Analyse EXPLAIN

```php
<?php
function explainQuery(PDO $pdo, string $sql): array
{
    $stmt = $pdo->query('EXPLAIN ' . $sql);
    return $stmt->fetchAll();
}

// Surveiller :
// - type: 'ALL' (scan complet — mauvais)
// - type: 'ref', 'eq_ref', 'const' (utilise un index — bien)
// - rows: Plus petit est mieux
// - Extra: 'Using index' (index couvrant — excellent)
```

## Éviter les Scans Complets

```php
<?php
// MAUVAIS : Fonction sur colonne indexée empêche l'utilisation de l'index
$pdo->query('SELECT * FROM users WHERE YEAR(created_at) = 2024');

// BON : Requête par plage utilise l'index
$pdo->query('SELECT * FROM users WHERE created_at >= "2024-01-01" AND created_at < "2025-01-01"');

// MAUVAIS : Joker en tête
$pdo->query('SELECT * FROM users WHERE email LIKE "%@gmail.com"');

// BON : Joker en fin peut utiliser l'index
$pdo->query('SELECT * FROM users WHERE email LIKE "jean%"');

// MAUVAIS : Conversion de type implicite
$pdo->query('SELECT * FROM users WHERE id = "123"');  // id est INT

// BON : Type correct
$pdo->query('SELECT * FROM users WHERE id = 123');
```

## Sélectionner Uniquement ce Dont Vous Avez Besoin

```php
<?php
// MAUVAIS : Tout sélectionner
$pdo->query('SELECT * FROM users');

// BON : Sélectionner seulement les colonnes nécessaires
$pdo->query('SELECT id, name, email FROM users');

// Encore mieux avec un index couvrant
// Index : (status, id, name)
$pdo->query('SELECT id, name FROM users WHERE status = "active"');
// Satisfait entièrement par l'index !
```

## Limiter les Résultats

```php
<?php
// MAUVAIS : Tout récupérer, puis limiter en PHP
$allUsers = $pdo->query('SELECT * FROM users')->fetchAll();
$pageUsers = array_slice($allUsers, 0, 20);

// BON : Limiter en SQL
$pdo->query('SELECT * FROM users ORDER BY id LIMIT 20 OFFSET 0');

// Mieux pour la pagination profonde : basée sur cursor
$pdo->query('SELECT * FROM users WHERE id > :lastId ORDER BY id LIMIT 20');
```

## Opérations par Lots

```php
<?php
// MAUVAIS : Nombreuses insertions individuelles
foreach ($users as $user) {
    $pdo->prepare('INSERT INTO users (name, email) VALUES (?, ?)')
        ->execute([$user['name'], $user['email']]);
}

// BON : Insertion par lot
$values = [];
$params = [];
foreach ($users as $i => $user) {
    $values[] = "(:name$i, :email$i)";
    $params["name$i"] = $user['name'];
    $params["email$i"] = $user['email'];
}

$sql = 'INSERT INTO users (name, email) VALUES ' . implode(', ', $values);
$pdo->prepare($sql)->execute($params);
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
