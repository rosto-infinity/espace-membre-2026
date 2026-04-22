---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-inheritance-composition"
---

# Héritage VS Composition

Deux façons fondamentales de **réutiliser le code** et de construire des relations entre les classes.

## L'Héritage (Relation EST-UN)

```php
<?php
class Animal {
    protected string $name;

    public function __construct(string $name) {
        $this->name = $name;
    }

    public function speak(): string {
        return 'Un son quelconque';
    }
}

class Dog extends Animal {
    public function speak(): string {
        return 'Wouf !';
    }

    public function fetch(): void {
        echo "{$this->name} va chercher !";
    }
}

$dog = new Dog('Rex');
echo $dog->speak();  // Wouf !
$dog->fetch();       // Rex va chercher !
```

## Les Problèmes de l'Héritage Profond

```php
<?php
// Le problème de la classe de base fragile
class Vehicle {
    public function start(): void { /* ... */ }
    public function stop(): void { /* ... */ }
}

class Car extends Vehicle {
    public function honk(): void { /* ... */ }
}

class ElectricCar extends Car {
    // Les changements dans Vehicle ou Car peuvent casser ceci !
}

// Couplage fort, difficile à tester, peu flexible
```

## La Composition (Relation A-UN)

```php
<?php
// Au lieu de l'héritage, utilisez la composition
class Engine {
    public function start(): void {
        echo "Moteur démarré\n";
    }

    public function stop(): void {
        echo "Moteur arrêté\n";
    }
}

class Car {
    public function __construct(
        private Engine $engine
    ) {}

    public function start(): void {
        $this->engine->start();
    }

    public function stop(): void {
        $this->engine->stop();
    }
}

// Facile de changer de moteur !
class ElectricEngine {
    public function start(): void {
        echo "Moteur électrique en marche\n";
    }

    public function stop(): void {
        echo "Moteur électrique arrêté\n";
    }
}
```

## Préférer la Composition à l'Héritage

```php
<?php
// Approche par héritage (rigide)
class Logger {
    public function log(string $message): void { /* ... */ }
}

class FileLogger extends Logger {
    // Lié à l'implémentation de Logger
}

// Approche par composition (flexible)
interface LoggerInterface {
    public function log(string $message): void;
}

class Application {
    public function __construct(
        private LoggerInterface $logger  // Injection de dépendance
    ) {}

    public function doSomething(): void {
        $this->logger->log('Faire quelque chose');
    }
}

// Peut utiliser n'importe quelle implémentation de LoggerInterface
$app = new Application(new FileLogger());
$app = new Application(new DatabaseLogger());
$app = new Application(new NullLogger());  // Pour les tests
```

## Quand Utiliser Lequel ?

| Utiliser l'Héritage Quand             | Utiliser la Composition Quand |
| ------------------------------------- | ----------------------------- |
| Vraie relation EST-UN                 | Relation A-UN ou UTILISE-UN   |
| Partager une implémentation           | Partager comportement/contrat |
| Polymorphisme de sous-type requis     | Flexibilité requise           |
| Hiérarchie peu profonde (1-2 niveaux) | Hiérarchies profondes         |
| Points d'extension de framework       | Logique métier                |

## Les Grimoires

- [Héritage en PHP (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.inheritance.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
