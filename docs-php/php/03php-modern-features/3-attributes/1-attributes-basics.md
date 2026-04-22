---
source_course: "php-modern-features"
source_lesson: "php-modern-features-attributes-basics"
---

# Introduction aux Attributs (PHP 8.0+)

Les attributs sont des **métadonnées structurées** que l'on peut attacher aux classes, méthodes, propriétés, paramètres et plus encore. Ils remplacent les annotations docblock par une solution native et type-safe.

## La Syntaxe de Base

```php
<?php
#[Attribute]
class Route {
    public function __construct(
        public string $path,
        public string $method = 'GET'
    ) {}
}

#[Route('/users', 'GET')]
class UserController {
    #[Route('/users/{id}', 'GET')]
    public function show(int $id): User {}

    #[Route('/users', 'POST')]
    public function create(): User {}
}
```

## Les Attributs Intégrés à PHP

### #[Override] (PHP 8.3+)

Garantit qu'une méthode surcharge bien une méthode parente :

```php
<?php
class Animal {
    public function speak(): string {
        return 'Un son quelconque';
    }
}

class Dog extends Animal {
    #[Override]
    public function speak(): string {  // OK - surcharge le parent
        return 'Wouf !';
    }

    #[Override]
    public function bark(): string {   // Erreur ! Pas de méthode parente
        return 'Boum !';
    }
}
```

### #[Deprecated] (PHP 8.4+)

Marquer du code comme déprécié :

```php
<?php
class Api {
    #[Deprecated('Utiliser newMethod() à la place', since: '2.0')]
    public function oldMethod(): void {
        // Code legacy
    }

    public function newMethod(): void {
        // Nouvelle implémentation
    }
}
```

### #[SensitiveParameter] (PHP 8.2+)

Cacher les données sensibles dans les stack traces :

```php
<?php
function authenticate(
    string $username,
    #[SensitiveParameter] string $password
): bool {
    // Si une exception survient, $password n'apparaîtra PAS dans la stack trace
    return verify($username, $password);
}
```

## Les Cibles des Attributs

```php
<?php
#[Attribute(Attribute::TARGET_CLASS)]              // Seulement sur les classes
#[Attribute(Attribute::TARGET_METHOD)]             // Seulement sur les méthodes
#[Attribute(Attribute::TARGET_PROPERTY)]           // Seulement sur les propriétés
#[Attribute(Attribute::TARGET_PARAMETER)]          // Seulement sur les paramètres
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]     // Seulement sur les constantes
#[Attribute(Attribute::TARGET_ALL)]                // Partout (par défaut)

// Combiner des cibles
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class MyAttribute {}
```

## Les Attributs Répétables

```php
<?php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Middleware {
    public function __construct(public string $name) {}
}

class Controller {
    #[Middleware('auth')]
    #[Middleware('logging')]
    #[Middleware('cache')]
    public function index(): Response {}
}
```

## Exemple Concret

**Des attributs de validation personnalisés**

```php
<?php
declare(strict_types=1);

// Exemple d'attributs de validation
#[Attribute(Attribute::TARGET_PROPERTY)]
class Required {
    public function __construct(public string $message = 'Ce champ est requis') {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email {
    public function __construct(public string $message = 'Format email invalide') {}
}

#[Attribute(Attribute::TARGET_PROPERTY)]
class MinLength {
    public function __construct(
        public int $min,
        public string $message = 'Trop court'
    ) {}
}

// Utilisation
class UserDTO {
    #[Required]
    #[MinLength(2)]
    public string $name;

    #[Required]
    #[Email]
    public string $email;

    #[Required]
    #[MinLength(8, message: 'Le mot de passe doit faire au moins 8 caractères')]
    public string $password;
}
?>
```

## Les Grimoires

- [Vue d'ensemble des Attributs (Documentation Officielle)](https://www.php.net/manual/en/language.attributes.overview.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
