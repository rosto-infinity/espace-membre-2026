---
source_course: "php-databases"
source_lesson: "php-databases-n-plus-one"
---

# Résoudre le Problème N+1

Le problème N+1 survient quand vous exécutez **une requête pour une liste, puis N requêtes supplémentaires** pour les données liées.

## Le Problème

```php
<?php
// 1 requête pour obtenir les utilisateurs
$users = $pdo->query('SELECT * FROM users LIMIT 100')->fetchAll();

foreach ($users as $user) {
    // N requêtes (100 !) pour obtenir les commandes
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
}

// Total : 101 requêtes !
```

## Solution 1 : JOIN

```php
<?php
$stmt = $pdo->query('
    SELECT u.*, o.id as order_id, o.total, o.created_at as order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    ORDER BY u.id, o.created_at
');

$results = $stmt->fetchAll();

// Regrouper par utilisateur en PHP
$users = [];
foreach ($results as $row) {
    $userId = $row['id'];
    if (!isset($users[$userId])) {
        $users[$userId] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'orders' => [],
        ];
    }
    if ($row['order_id']) {
        $users[$userId]['orders'][] = [
            'id' => $row['order_id'],
            'total' => $row['total'],
        ];
    }
}

// Total : 1 requête !
```

## Solution 2 : Chargement Eager avec IN

```php
<?php
// Requête 1 : Obtenir les utilisateurs
$users = $pdo->query('SELECT * FROM users LIMIT 100')->fetchAll();
$userIds = array_column($users, 'id');

// Requête 2 : Obtenir toutes les commandes pour ces utilisateurs
$placeholders = implode(',', array_fill(0, count($userIds), '?'));
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id IN ($placeholders)");
$stmt->execute($userIds);
$allOrders = $stmt->fetchAll();

// Regrouper les commandes par user_id
$ordersByUser = [];
foreach ($allOrders as $order) {
    $ordersByUser[$order['user_id']][] = $order;
}

// Attacher aux utilisateurs
foreach ($users as &$user) {
    $user['orders'] = $ordersByUser[$user['id']] ?? [];
}

// Total : 2 requêtes (quel que soit le nombre d'utilisateurs)
```

## Helper de Chargement Eager

```php
<?php
class EagerLoader {
    public function __construct(private PDO $pdo) {}

    public function loadRelation(
        array &$items,
        string $relation,
        string $table,
        string $foreignKey,
        string $localKey = 'id'
    ): void {
        $ids = array_unique(array_column($items, $localKey));

        if (empty($ids)) {
            return;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT * FROM $table WHERE $foreignKey IN ($placeholders)"
        );
        $stmt->execute($ids);
        $related = $stmt->fetchAll();

        $grouped = [];
        foreach ($related as $row) {
            $grouped[$row[$foreignKey]][] = $row;
        }

        foreach ($items as &$item) {
            $item[$relation] = $grouped[$item[$localKey]] ?? [];
        }
    }
}

// Utilisation
$loader = new EagerLoader($pdo);
$users = $pdo->query('SELECT * FROM users')->fetchAll();
$loader->loadRelation($users, 'orders', 'orders', 'user_id');
$loader->loadRelation($users, 'posts', 'posts', 'author_id');
```

## Exemple Concret

**Repository avec support du chargement eager**

```php
<?php
declare(strict_types=1);

// Repository complet avec chargement eager
class UserRepository {
    public function __construct(private PDO $pdo) {}

    /**
     * @param array<string> $with Relations à charger eagerly
     * @return array<User>
     */
    public function findAllWithRelations(array $with = []): array {
        $users = $this->pdo
            ->query('SELECT * FROM users ORDER BY name')
            ->fetchAll();

        if (empty($users)) {
            return [];
        }

        $userIds = array_column($users, 'id');

        if (in_array('orders', $with, true)) {
            $this->loadOrders($users, $userIds);
        }

        if (in_array('profile', $with, true)) {
            $this->loadProfiles($users, $userIds);
        }

        return $users;
    }

    private function loadOrders(array &$users, array $userIds): void {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT o.*,
                   GROUP_CONCAT(oi.product_name) as products
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id IN ($placeholders)
            GROUP BY o.id
            ORDER BY o.created_at DESC
        ");
        $stmt->execute($userIds);
        $orders = $stmt->fetchAll();

        $grouped = [];
        foreach ($orders as $order) {
            $order['products'] = $order['products'] ? explode(',', $order['products']) : [];
            $grouped[$order['user_id']][] = $order;
        }

        foreach ($users as &$user) {
            $user['orders'] = $grouped[$user['id']] ?? [];
            $user['order_count'] = count($user['orders']);
            $user['total_spent'] = array_sum(array_column($user['orders'], 'total'));
        }
    }

    private function loadProfiles(array &$users, array $userIds): void {
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        $stmt = $this->pdo->prepare("
            SELECT * FROM user_profiles WHERE user_id IN ($placeholders)
        ");
        $stmt->execute($userIds);
        $profiles = $stmt->fetchAll();

        $indexed = array_column($profiles, null, 'user_id');

        foreach ($users as &$user) {
            $user['profile'] = $indexed[$user['id']] ?? null;
        }
    }
}

// Utilisation : Seulement 3 requêtes au total (users + orders + profiles)
$repo = new UserRepository($pdo);
$users = $repo->findAllWithRelations(['orders', 'profile']);

foreach ($users as $user) {
    echo "{$user['name']} a {$user['order_count']} commande(s)\n";
    echo "Total dépensé : {$user['total_spent']} €\n";
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
