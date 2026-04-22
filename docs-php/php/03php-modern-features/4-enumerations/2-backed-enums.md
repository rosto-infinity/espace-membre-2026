---
source_course: "php-modern-features"
source_lesson: "php-modern-features-backed-enums"
---

# Les Enums avec Valeurs Associées (Backed Enums)

Les backed enums associent chaque cas à une **valeur scalaire** (int ou string). Indispensable pour le stockage en base de données et les réponses API.

## Enums String-Backed

```php
<?php
enum Status: string {
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
    case Deleted = 'deleted';
}

$status = Status::Active;
echo $status->value;  // 'active'
echo $status->name;   // 'Active'
```

## Enums Integer-Backed

```php
<?php
enum HttpStatus: int {
    case OK = 200;
    case Created = 201;
    case BadRequest = 400;
    case NotFound = 404;
    case ServerError = 500;
}

http_response_code(HttpStatus::NotFound->value);  // 404
```

## Créer à Partir d'une Valeur

```php
<?php
enum Status: string {
    case Pending = 'pending';
    case Active = 'active';
}

// Depuis une valeur de base de données
$dbValue = 'active';
$status = Status::from($dbValue);  // Status::Active

// Avec une valeur invalide
$status = Status::from('invalide');  // ValueError !

// Version sécurisée — retourne null si non trouvé
$status = Status::tryFrom('invalide');  // null
$status = Status::tryFrom('active');    // Status::Active
```

## Intégration Base de Données

```php
<?php
enum OrderStatus: string {
    case Pending = 'pending';
    case Processing = 'processing';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
}

class Order {
    public function __construct(
        public int $id,
        public OrderStatus $status
    ) {}
}

// Sauvegarder en base de données
$order = new Order(1, OrderStatus::Processing);
$stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$order->status->value, $order->id]);

// Charger depuis la base de données
$row = $stmt->fetch();
$status = OrderStatus::from($row['status']);
```

## Sérialisation JSON

```php
<?php
enum Priority: int {
    case Low = 1;
    case Medium = 2;
    case High = 3;
}

class Task implements JsonSerializable {
    public function __construct(
        public string $title,
        public Priority $priority
    ) {}

    public function jsonSerialize(): array {
        return [
            'title' => $this->title,
            'priority' => $this->priority->value,
        ];
    }
}

$task = new Task('Corriger le bug', Priority::High);
echo json_encode($task);
// {"title":"Corriger le bug","priority":3}
```

## Exemple Concret

**Enum de statut de paiement avec logique de machine à états**

```php
<?php
declare(strict_types=1);

// Enum complet de statut de paiement avec méthodes
enum PaymentStatus: string {
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Refunded = 'refunded';

    public function label(): string {
        return match($this) {
            self::Pending => '⏳ En attente',
            self::Processing => '🔄 En cours',
            self::Completed => '✅ Complété',
            self::Failed => '❌ Échoué',
            self::Refunded => '↩️ Remboursé',
        };
    }

    public function isFinal(): bool {
        return in_array($this, [self::Completed, self::Failed, self::Refunded]);
    }

    public function canTransitionTo(self $newStatus): bool {
        return match($this) {
            self::Pending => in_array($newStatus, [self::Processing, self::Failed]),
            self::Processing => in_array($newStatus, [self::Completed, self::Failed]),
            self::Completed => $newStatus === self::Refunded,
            default => false,
        };
    }
}

// Utilisation
$status = PaymentStatus::Pending;
echo $status->label();  // ⏳ En attente

if ($status->canTransitionTo(PaymentStatus::Processing)) {
    $status = PaymentStatus::Processing;
}
?>
```

## Les Grimoires

- [Les Backed Enums (Documentation Officielle)](https://www.php.net/manual/en/language.enumerations.backed.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
