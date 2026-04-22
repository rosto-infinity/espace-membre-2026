---
source_course: "php-modern-features"
source_lesson: "php-modern-features-named-arguments"
---

# Les Arguments Nommés (PHP 8.0+)

Les arguments nommés permettent de passer des valeurs par **nom de paramètre** au lieu de leur position, rendant le code plus lisible et plus flexible.

## La Syntaxe de Base

```php
<?php
function createUser(string $name, string $email, int $age, bool $active = true) {
    // ...
}

// Positionnel (ancienne manière)
createUser('Jean', 'jean@example.com', 25, true);

// Arguments nommés — intention claire !
createUser(
    name: 'Jean',
    email: 'jean@example.com',
    age: 25,
    active: true
);
```

## Sauter les Paramètres Optionnels

```php
<?php
function sendEmail(
    string $to,
    string $subject,
    string $body,
    string $from = 'noreply@example.com',
    array $cc = [],
    array $bcc = [],
    bool $html = false
) {}

// Spécifier uniquement ce dont on a besoin
sendEmail(
    to: 'utilisateur@example.com',
    subject: 'Bonjour',
    body: 'Contenu du message',
    html: true  // Saute $from, $cc, $bcc
);
```

## Mélanger Positionnel et Nommé

```php
<?php
// Positionnel en premier, puis nommé
createUser('Jean', 'jean@example.com', age: 25, active: true);

// Impossible d'utiliser le positionnel APRÈS le nommé !
createUser(name: 'Jean', 'jean@example.com');  // Erreur !
```

## Avec les Fonctions sur les Tableaux

```php
<?php
$numbers = [3, 1, 4, 1, 5, 9];

// Ancienne manière — que signifie 'true' ici ?
array_filter($numbers, fn($n) => $n > 2, ARRAY_FILTER_USE_KEY);

// Nommé — auto-documenté !
array_filter(
    array: $numbers,
    callback: fn($n) => $n > 2,
    mode: ARRAY_FILTER_USE_BOTH
);
```

## Avec les Constructeurs

```php
<?php
class Product {
    public function __construct(
        public string $name,
        public float $price,
        public int $stock = 0,
        public string $sku = '',
        public bool $active = true
    ) {}
}

$product = new Product(
    name: 'Widget',
    price: 29.99,
    active: false  // Saute stock et sku
);
```

## Décomposer un Tableau avec des Noms

```php
<?php
$args = [
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'age' => 30,
];

createUser(...$args);
// Équivalent à :
createUser(name: 'Alice', email: 'alice@example.com', age: 30);
```

## Les Grimoires

- [Les Arguments Nommés (Documentation Officielle)](https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
