---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-interfaces-deep"
---

# Plongée dans les Interfaces

Les interfaces définissent des **contrats que les classes doivent respecter**. Elles spécifient CE QU'une classe doit faire, pas COMMENT.

## Interface de Base

```php
<?php
interface Printable {
    public function print(): string;
}

class Invoice implements Printable {
    public function __construct(
        private float $amount
    ) {}

    public function print(): string {
        return "Facture : {$this->amount} €";
    }
}

class Report implements Printable {
    public function __construct(
        private string $title
    ) {}

    public function print(): string {
        return "Rapport : {$this->title}";
    }
}
```

## Implémenter Plusieurs Interfaces

```php
<?php
interface Serializable {
    public function serialize(): string;
}

interface Cacheable {
    public function getCacheKey(): string;
    public function getTtl(): int;
}

interface Loggable {
    public function getLogContext(): array;
}

// Implémenter plusieurs interfaces
class UserSession implements Serializable, Cacheable, Loggable {
    public function __construct(
        private string $userId,
        private array $data
    ) {}

    public function serialize(): string {
        return json_encode($this->data);
    }

    public function getCacheKey(): string {
        return "session:{$this->userId}";
    }

    public function getTtl(): int {
        return 3600;
    }

    public function getLogContext(): array {
        return ['user_id' => $this->userId];
    }
}
```

## L'Héritage d'Interfaces

```php
<?php
interface Readable {
    public function read(): string;
}

interface Writable {
    public function write(string $data): void;
}

// Interface qui étend plusieurs interfaces
interface ReadWritable extends Readable, Writable {
    public function isOpen(): bool;
}

class FileStream implements ReadWritable {
    public function read(): string { /* ... */ }
    public function write(string $data): void { /* ... */ }
    public function isOpen(): bool { /* ... */ }
}
```

## Les Constantes d'Interface

```php
<?php
interface HttpStatus {
    public const OK = 200;
    public const NOT_FOUND = 404;
    public const SERVER_ERROR = 500;
}

class Response implements HttpStatus {
    public function __construct(
        private int $status = self::OK
    ) {}
}
```

## Typage avec les Interfaces

```php
<?php
interface Logger {
    public function log(string $message): void;
}

class Application {
    public function __construct(
        private Logger $logger  // Accepte n'importe quelle implémentation de Logger
    ) {}

    public function run(): void {
        $this->logger->log('Application démarrée');
    }
}

// Injection de dépendance avec les interfaces
$app = new Application(new FileLogger());
$app = new Application(new ConsoleLogger());
$app = new Application(new NullLogger());  // Pour les tests
```

## Les Grimoires

- [Les Interfaces PHP (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.interfaces.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
