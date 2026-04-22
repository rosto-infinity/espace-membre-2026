---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-solid-principles"
---

# Les Principes SOLID

Cinq principes de conception pour un code orienté objet **maintenable et flexible**.

## S — Principe de Responsabilité Unique (Single Responsibility)

**Une classe ne devrait avoir qu'une seule raison de changer.**

```php
<?php
// MAUVAIS : Plusieurs responsabilités
class UserManager {
    public function createUser(array $data): User { /* ... */ }
    public function sendWelcomeEmail(User $user): void { /* ... */ }
    public function generateReport(): string { /* ... */ }
}

// BON : Une seule responsabilité par classe
class UserRepository {
    public function create(array $data): User { /* ... */ }
}

class WelcomeEmailSender {
    public function send(User $user): void { /* ... */ }
}

class UserReportGenerator {
    public function generate(): string { /* ... */ }
}
```

## O — Principe Ouvert/Fermé (Open/Closed)

**Ouvert à l'extension, fermé à la modification.**

```php
<?php
// MAUVAIS : Doit être modifié pour ajouter de nouveaux types
class PaymentProcessor {
    public function process(string $type, float $amount): void {
        if ($type === 'credit') {
            // Traiter carte de crédit
        } elseif ($type === 'paypal') {
            // Traiter PayPal
        }
        // Doit ajouter des elseif pour de nouveaux types !
    }
}

// BON : Étendre sans modifier
interface PaymentGateway {
    public function process(float $amount): void;
}

class CreditCardGateway implements PaymentGateway {
    public function process(float $amount): void { /* ... */ }
}

class PayPalGateway implements PaymentGateway {
    public function process(float $amount): void { /* ... */ }
}

// Ajouter de nouveaux types sans modifier le code existant
class CryptoGateway implements PaymentGateway {
    public function process(float $amount): void { /* ... */ }
}
```

## L — Principe de Substitution de Liskov (Liskov Substitution)

**Les sous-types doivent être substituables à leurs types de base.**

```php
<?php
// MAUVAIS : Square viole le principe de Liskov
class Rectangle {
    protected int $width;
    protected int $height;

    public function setWidth(int $width): void {
        $this->width = $width;
    }

    public function setHeight(int $height): void {
        $this->height = $height;
    }

    public function getArea(): int {
        return $this->width * $this->height;
    }
}

class Square extends Rectangle {
    public function setWidth(int $width): void {
        $this->width = $width;
        $this->height = $width;  // Casse les attentes !
    }
}

// Le code attendant un comportement de Rectangle échouera avec Square
```

## I — Principe de Ségrégation des Interfaces (Interface Segregation)

**Les clients ne devraient pas dépendre d'interfaces qu'ils n'utilisent pas.**

```php
<?php
// MAUVAIS : Interface trop grosse
interface Worker {
    public function work(): void;
    public function eat(): void;
    public function sleep(): void;
}

// Les robots ne peuvent pas manger ni dormir !
class Robot implements Worker { /* ... */ }

// BON : Interfaces séparées
interface Workable {
    public function work(): void;
}

interface Eatable {
    public function eat(): void;
}

class Human implements Workable, Eatable { /* ... */ }
class Robot implements Workable { /* ... */ }  // Seulement ce dont il a besoin
```

## D — Principe d'Inversion des Dépendances (Dependency Inversion)

**Dépendre des abstractions, pas des concrets.**

```php
<?php
// MAUVAIS : Dépend d'une classe concrète
class OrderService {
    private MySQLDatabase $db;  // Concret !

    public function __construct() {
        $this->db = new MySQLDatabase();
    }
}

// BON : Dépend d'une abstraction
interface DatabaseInterface {
    public function query(string $sql): array;
}

class OrderService {
    public function __construct(
        private DatabaseInterface $db  // Abstrait !
    ) {}
}

// Peut injecter n'importe quelle implémentation
$service = new OrderService(new MySQLDatabase());
$service = new OrderService(new PostgreSQLDatabase());
$service = new OrderService(new InMemoryDatabase());  // Pour les tests
```

## Exemple Concret

**Un système de notification conforme aux principes SOLID**

```php
<?php
declare(strict_types=1);

// Responsabilité Unique : Chaque classe fait une chose
interface NotificationChannel {
    public function send(string $recipient, string $message): void;
}

class EmailChannel implements NotificationChannel {
    public function send(string $recipient, string $message): void {
        echo "Email à $recipient : $message\n";
    }
}

class SMSChannel implements NotificationChannel {
    public function send(string $recipient, string $message): void {
        echo "SMS à $recipient : $message\n";
    }
}

// Ouvert/Fermé : Ajouter des canaux sans modifier NotificationService
class PushChannel implements NotificationChannel {
    public function send(string $recipient, string $message): void {
        echo "Push à $recipient : $message\n";
    }
}

// Inversion des Dépendances : Dépend des abstractions
class NotificationService {
    /** @param NotificationChannel[] $channels */
    public function __construct(
        private array $channels
    ) {}

    public function notify(string $recipient, string $message): void {
        foreach ($this->channels as $channel) {
            $channel->send($recipient, $message);
        }
    }
}

// Utilisation
$notifier = new NotificationService([
    new EmailChannel(),
    new SMSChannel(),
    new PushChannel(),
]);

$notifier->notify('user@example.com', 'Bonjour !');
?>
```

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
