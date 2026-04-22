---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-reflection-api"
---

# Plongée dans l'API Reflection

La Réflexion permet d'**inspecter et de manipuler** les classes, méthodes et propriétés à l'exécution. Elle est la fondation des frameworks, ORMs, et conteneurs d'injection de dépendances.

## Inspecter les Classes

```php
<?php
class User
{
    private string $id;
    public string $name;
    protected string $email;

    public function __construct(string $name, string $email)
    {
        $this->id = uniqid();
        $this->name = $name;
        $this->email = $email;
    }

    public function getEmail(): string
    {
        return $this->email;
    }
}

$reflection = new ReflectionClass(User::class);

// Obtenir les infos de la classe
echo $reflection->getName();           // User
echo $reflection->getShortName();      // User (sans le namespace)
echo $reflection->isInstantiable();    // true
echo $reflection->isFinal();           // false
```

## Inspecter les Propriétés

```php
<?php
$properties = $reflection->getProperties();

foreach ($properties as $property) {
    echo $property->getName();               // 'id', 'name', 'email'
    echo $property->getType()->getName();    // 'string'
    echo $property->isPublic();              // false, true, false
    echo $property->isPrivate();             // true, false, false
}

// Accéder aux propriétés privées
$user = new User('Jean', 'jean@example.com');
$idProperty = $reflection->getProperty('id');
$idProperty->setAccessible(true);  // Contourner la visibilité
echo $idProperty->getValue($user); // La valeur privée de l'ID
```

## Inspecter les Méthodes

```php
<?php
$methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

foreach ($methods as $method) {
    echo $method->getName();

    // Obtenir les paramètres
    foreach ($method->getParameters() as $param) {
        echo $param->getName();
        echo $param->getType()?->getName();
        echo $param->isOptional();
        echo $param->hasDefaultValue() ? $param->getDefaultValue() : 'aucun';
    }

    // Obtenir le type de retour
    echo $method->getReturnType()?->getName();
}
```

## Câblage Automatique des Dépendances (Auto-Wiring)

```php
<?php
class SimpleContainer
{
    private array $bindings = [];

    public function bind(string $abstract, string $concrete): void
    {
        $this->bindings[$abstract] = $concrete;
    }

    public function make(string $class): object
    {
        // Vérifier les liaisons
        $concrete = $this->bindings[$class] ?? $class;

        $reflection = new ReflectionClass($concrete);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Impossible d'instancier $concrete");
        }

        $constructor = $reflection->getConstructor();

        // Pas de constructeur = pas de dépendances
        if ($constructor === null) {
            return new $concrete();
        }

        // Résoudre les dépendances du constructeur
        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new Exception("Impossible de résoudre : {$param->getName()}");
                }
            } else {
                // Résoudre les dépendances de classe récursivement
                $dependencies[] = $this->make($type->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}

// Utilisation
$container = new SimpleContainer();
$container->bind(LoggerInterface::class, FileLogger::class);

$userService = $container->make(UserService::class);
// FileLogger est injecté automatiquement !
```

## Créer des Systèmes Basés sur les Attributs

```php
<?php
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
    public function __construct(
        public string $name,
        public string $type = 'string'
    ) {}
}

class ORM
{
    public function getColumns(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $columns = [];

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Column::class);

            if (!empty($attributes)) {
                $column = $attributes[0]->newInstance();
                $columns[$property->getName()] = $column;
            }
        }

        return $columns;
    }
}
```

## Les Grimoires

- [L'API Reflection PHP (Documentation Officielle)](https://www.php.net/manual/en/book.reflection.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
