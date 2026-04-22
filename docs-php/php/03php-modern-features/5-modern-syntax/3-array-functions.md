---
source_course: "php-modern-features"
source_lesson: "php-modern-features-new-array-functions"
---

# Les Nouvelles Fonctions de Tableaux en PHP 8.4

PHP 8.4 introduit des fonctions de tableaux puissantes qui réduisent le code répétitif et améliorent la lisibilité.

## `array_find()` — Trouver le Premier Élément

Trouve le premier élément correspondant à une condition :

```php
<?php
$users = [
    ['id' => 1, 'name' => 'Alice', 'admin' => false],
    ['id' => 2, 'name' => 'Bob', 'admin' => true],
    ['id' => 3, 'name' => 'Charlie', 'admin' => false],
];

// Trouver le premier admin
$admin = array_find($users, fn($user) => $user['admin'] === true);
// ['id' => 2, 'name' => 'Bob', 'admin' => true]

// Retourne null si non trouvé
$notFound = array_find($users, fn($user) => $user['id'] === 999);
// null
```

## `array_find_key()` — Trouver la Clé

Retourne la clé du premier élément correspondant :

```php
<?php
$products = [
    'widget' => ['price' => 10],
    'gadget' => ['price' => 25],
    'gizmo' => ['price' => 15],
];

$key = array_find_key($products, fn($p) => $p['price'] > 20);
// 'gadget'
```

## `array_any()` — Au Moins Un Correspond ?

Vérifie si AU MOINS UN élément correspond à une condition :

```php
<?php
$numbers = [1, 2, 3, 4, 5];

$hasEven = array_any($numbers, fn($n) => $n % 2 === 0);
// true

$hasNegative = array_any($numbers, fn($n) => $n < 0);
// false

// Utile pour la validation
$hasErrors = array_any($responses, fn($r) => $r['status'] >= 400);
if ($hasErrors) {
    // Gérer le cas d'erreur
}
```

## `array_all()` — Tous Correspondent ?

Vérifie si TOUS les éléments correspondent à une condition :

```php
<?php
$scores = [85, 90, 78, 92, 88];

$allPassed = array_all($scores, fn($score) => $score >= 60);
// true

$allExcellent = array_all($scores, fn($score) => $score >= 90);
// false

// Valider tous les éléments
$allValid = array_all($items, fn($item) => $item['quantity'] > 0);
```

## Comparaison avec l'Approche Traditionnelle

```php
<?php
// Avant PHP 8.4 — écrire les fonctions soi-même
function findFirst(array $array, callable $callback): mixed {
    foreach ($array as $item) {
        if ($callback($item)) {
            return $item;
        }
    }
    return null;
}

function any(array $array, callable $callback): bool {
    foreach ($array as $item) {
        if ($callback($item)) {
            return true;
        }
    }
    return false;
}

// PHP 8.4 — fonctions natives !
$found = array_find($array, $callback);
$hasMatch = array_any($array, $callback);
```

## Exemples Pratiques

```php
<?php
$orders = [
    ['id' => 1, 'status' => 'shipped', 'total' => 99.99],
    ['id' => 2, 'status' => 'pending', 'total' => 149.50],
    ['id' => 3, 'status' => 'delivered', 'total' => 75.00],
];

// Trouver la commande en attente
$pendingOrder = array_find($orders, fn($o) => $o['status'] === 'pending');

// Vérifier si une commande dépasse la limite
$hasLargeOrder = array_any($orders, fn($o) => $o['total'] > 100);

// Vérifier que toutes les commandes restent dans le budget
$allUnderBudget = array_all($orders, fn($o) => $o['total'] < 200);
```

## Les Grimoires

- [Fonctions de Tableaux PHP (Référence Complète)](https://www.php.net/manual/en/ref.array.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
