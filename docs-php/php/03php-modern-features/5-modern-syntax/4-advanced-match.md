---
source_course: "php-modern-features"
source_lesson: "php-modern-features-advanced-match"
---

# Les Patterns Avancés de l'Expression Match

L'expression `match` va bien au-delà de la simple correspondance de valeurs. Explorons les patterns avancés.

## Combiner des Conditions avec `match(true)`

```php
<?php
function classifyNumber(int $n): string
{
    return match(true) {
        $n < 0 => 'négatif',
        $n === 0 => 'zéro',
        $n > 0 && $n < 10 => 'petit positif',
        $n >= 10 && $n < 100 => 'moyen positif',
        default => 'grand positif',
    };
}

echo classifyNumber(5);   // 'petit positif'
echo classifyNumber(50);  // 'moyen positif'
echo classifyNumber(-3);  // 'négatif'
```

## Match avec des Retours Complexes

```php
<?php
enum HttpMethod { case GET; case POST; case PUT; case DELETE; }

function getRouteConfig(HttpMethod $method, string $resource): array
{
    return match([$method, $resource]) {
        [HttpMethod::GET, 'users'] => [
            'controller' => 'UserController',
            'action' => 'index',
            'middleware' => ['auth'],
        ],
        [HttpMethod::POST, 'users'] => [
            'controller' => 'UserController',
            'action' => 'store',
            'middleware' => ['auth', 'admin'],
        ],
        [HttpMethod::GET, 'posts'] => [
            'controller' => 'PostController',
            'action' => 'index',
            'middleware' => [],
        ],
        default => throw new NotFoundException(),
    };
}
```

## Match avec des Types d'Objets

```php
<?php
interface Shape {}
class Circle implements Shape { public function __construct(public float $radius) {} }
class Rectangle implements Shape { public function __construct(public float $width, public float $height) {} }
class Triangle implements Shape { public function __construct(public float $base, public float $height) {} }

function calculateArea(Shape $shape): float
{
    return match($shape::class) {
        Circle::class => pi() * $shape->radius ** 2,
        Rectangle::class => $shape->width * $shape->height,
        Triangle::class => 0.5 * $shape->base * $shape->height,
        default => throw new InvalidArgumentException('Forme inconnue'),
    };
}

$circle = new Circle(5);
echo calculateArea($circle);  // ~78.54
```

## Les Expressions Match Imbriquées

```php
<?php
function getPricing(string $plan, bool $annual): array
{
    return match($plan) {
        'basic' => [
            'name' => 'Basique',
            'price' => match($annual) {
                true => 99,
                false => 12,
            },
            'period' => $annual ? 'an' : 'mois',
        ],
        'pro' => [
            'name' => 'Professionnel',
            'price' => match($annual) {
                true => 299,
                false => 35,
            },
            'period' => $annual ? 'an' : 'mois',
        ],
        default => throw new InvalidArgumentException("Plan inconnu : $plan"),
    };
}
```

## Match avec des Callbacks

```php
<?php
$operation = 'uppercase';
$transformer = match($operation) {
    'uppercase' => fn($s) => strtoupper($s),
    'lowercase' => fn($s) => strtolower($s),
    'reverse' => fn($s) => strrev($s),
    'trim' => fn($s) => trim($s),
    default => fn($s) => $s,
};

echo $transformer('Bonjour');  // 'BONJOUR'
```

## Les Grimoires

- [L'Expression Match PHP (Documentation Officielle)](https://www.php.net/manual/en/control-structures.match.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
