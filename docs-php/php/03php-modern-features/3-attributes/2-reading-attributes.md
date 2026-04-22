---
source_course: "php-modern-features"
source_lesson: "php-modern-features-reading-attributes"
---

# Lire les Attributs avec la Réflexion

Les attributs n'ont de sens que si du code les lit. Utilisez l'API Reflection de PHP pour accéder aux métadonnées des attributs.

## Lecture Basique d'Attribut

```php
<?php
#[Attribute]
class Route {
    public function __construct(
        public string $path,
        public string $method = 'GET'
    ) {}
}

#[Route('/home')]
class HomeController {}

// Lire l'attribut
$reflection = new ReflectionClass(HomeController::class);
$attributes = $reflection->getAttributes(Route::class);

foreach ($attributes as $attribute) {
    $route = $attribute->newInstance();
    echo "Chemin : " . $route->path;   // /home
    echo "Méthode : " . $route->method; // GET
}
```

## Lire les Attributs de Méthodes

```php
<?php
class UserController {
    #[Route('/users', 'GET')]
    public function index(): array { return []; }

    #[Route('/users/{id}', 'GET')]
    public function show(int $id): array { return []; }
}

$reflection = new ReflectionClass(UserController::class);

foreach ($reflection->getMethods() as $method) {
    $attributes = $method->getAttributes(Route::class);

    foreach ($attributes as $attr) {
        $route = $attr->newInstance();
        echo "{$route->method} {$route->path} -> {$method->getName()}\n";
    }
}
// Sortie :
// GET /users -> index
// GET /users/{id} -> show
```

## Lire les Attributs de Propriétés

```php
<?php
#[Attribute]
class Column {
    public function __construct(
        public string $name,
        public string $type = 'string'
    ) {}
}

class User {
    #[Column('user_id', 'integer')]
    public int $id;

    #[Column('user_name')]
    public string $name;
}

$reflection = new ReflectionClass(User::class);

foreach ($reflection->getProperties() as $property) {
    $columns = $property->getAttributes(Column::class);

    foreach ($columns as $attr) {
        $column = $attr->newInstance();
        echo "{$property->getName()} -> {$column->name} ({$column->type})\n";
    }
}
```

## Filtrer les Attributs

```php
<?php
// Obtenir uniquement les attributs d'une classe spécifique
$routeAttrs = $reflection->getAttributes(Route::class);

// Obtenir tous les attributs
$allAttrs = $reflection->getAttributes();

// Filtrer par classe parente (inclut les sous-classes)
$validatorAttrs = $reflection->getAttributes(
    Validator::class,
    ReflectionAttribute::IS_INSTANCEOF
);
```

## Exemple Complet de Routeur

```php
<?php
function registerRoutes(string $controllerClass): array {
    $routes = [];
    $reflection = new ReflectionClass($controllerClass);

    foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
        foreach ($method->getAttributes(Route::class) as $attr) {
            $route = $attr->newInstance();
            $routes[] = [
                'path' => $route->path,
                'method' => $route->method,
                'handler' => [$controllerClass, $method->getName()]
            ];
        }
    }

    return $routes;
}
```

## Exemple Concret

**Construire un validateur avec les attributs et la réflexion**

```php
<?php
declare(strict_types=1);

// Validateur basé sur les attributs
#[Attribute(Attribute::TARGET_PROPERTY)]
class Required {}

#[Attribute(Attribute::TARGET_PROPERTY)]
class Email {}

class Validator {
    public function validate(object $object): array {
        $errors = [];
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            $name = $property->getName();

            // Vérifier Required
            if ($property->getAttributes(Required::class)) {
                if (empty($value)) {
                    $errors[$name][] = "$name est requis";
                }
            }

            // Vérifier Email
            if ($property->getAttributes(Email::class)) {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$name][] = "$name doit être un email valide";
                }
            }
        }

        return $errors;
    }
}

class UserDTO {
    #[Required]
    public string $name = '';

    #[Required]
    #[Email]
    public string $email = '';
}

$dto = new UserDTO();
$dto->name = '';
$dto->email = 'invalide';

$validator = new Validator();
$errors = $validator->validate($dto);
print_r($errors);
?>
```

## Les Grimoires

- [Lire les Attributs (Documentation Officielle)](https://www.php.net/manual/en/language.attributes.reflection.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
