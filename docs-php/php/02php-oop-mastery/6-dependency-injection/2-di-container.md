---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-di-container"
---

# Construire un Conteneur DI Simple

Un Conteneur DI **gère automatiquement la création des objets et la résolution des dépendances**.

## Le Conteneur de Base

```php
<?php
class Container {
    private array $bindings = [];
    private array $instances = [];

    // Lier une interface à une classe concrète
    public function bind(string $abstract, string|callable $concrete): void {
        $this->bindings[$abstract] = $concrete;
    }

    // Lier un singleton
    public function singleton(string $abstract, string|callable $concrete): void {
        $this->bind($abstract, function($container) use ($abstract, $concrete) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = is_callable($concrete)
                    ? $concrete($container)
                    : $container->make($concrete);
            }
            return $this->instances[$abstract];
        });
    }

    // Résoudre une classe
    public function make(string $abstract): object {
        // Vérifier si on a une liaison
        if (isset($this->bindings[$abstract])) {
            $concrete = $this->bindings[$abstract];

            if (is_callable($concrete)) {
                return $concrete($this);
            }

            return $this->make($concrete);
        }

        // Résoudre automatiquement via la réflexion
        return $this->resolve($abstract);
    }

    private function resolve(string $class): object {
        $reflection = new ReflectionClass($class);

        if (!$reflection->isInstantiable()) {
            throw new Exception("Impossible d'instancier $class");
        }

        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $class();
        }

        $dependencies = [];

        foreach ($constructor->getParameters() as $param) {
            $type = $param->getType();

            if ($type === null || $type->isBuiltin()) {
                if ($param->isDefaultValueAvailable()) {
                    $dependencies[] = $param->getDefaultValue();
                } else {
                    throw new Exception("Impossible de résoudre le param : {$param->getName()}");
                }
            } else {
                $dependencies[] = $this->make($type->getName());
            }
        }

        return $reflection->newInstanceArgs($dependencies);
    }
}
```

## Utiliser le Conteneur

```php
<?php
$container = new Container();

// Lier les interfaces aux implémentations
$container->bind(UserRepositoryInterface::class, MySQLUserRepository::class);
$container->bind(MailerInterface::class, SmtpMailer::class);

// Lier avec une fonction fabrique
$container->singleton(PDO::class, function() {
    return new PDO(
        'mysql:host=localhost;dbname=app',
        'user',
        'pass',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
});

// Résolution automatique avec les dépendances
$userService = $container->make(UserService::class);
// Le conteneur injecte automatiquement :
// - UserRepositoryInterface (résolu en MySQLUserRepository)
// - MailerInterface (résolu en SmtpMailer)
```

## L'Anti-Pattern Service Locator (À Éviter)

```php
<?php
// MAUVAIS : Anti-pattern Service Locator
class BadService {
    public function __construct(private Container $container) {}

    public function doSomething(): void {
        $repo = $this->container->make(UserRepository::class);  // Dépendance cachée !
    }
}

// BON : Dépendances explicites
class GoodService {
    public function __construct(private UserRepository $repo) {}  // Visible !
}
```

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
