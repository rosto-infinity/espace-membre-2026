---
source_course: "php-ddd"
source_lesson: "php-ddd-persistence-strategies"
---

# Stratégies de Persistance

La façon dont vous persistez les objets de domaine impacte significativement votre architecture. Explorons différentes approches.

## Active Record vs Data Mapper

### Active Record

L'entité sait comment se persister elle-même :

```php
<?php
// Active Record - entité couplée à la persistance
class Order extends Model {  // Style Laravel Eloquent
    public function save(): void {
        $this->connection->update(...);
    }
}

$order->total = 100;
$order->save();
```

❌ **Problème** : Domaine mélangé avec l'infrastructure

### Data Mapper (Préféré en DDD)

Des classes séparées gèrent la persistance :

```php
<?php
// Data Mapper - le domaine reste pur
final class Order {  // Objet de domaine pur
    // Pas de code de persistance ici
}

class OrderRepository {
    public function save(Order $order): void {
        // Persistance gérée séparément
    }
}
```

✅ **Avantage** : Modèle de domaine propre

## Mapping Manuel

```php
<?php
final class PdoOrderRepository implements OrderRepository {
    public function __construct(
        private \PDO $pdo
    ) {}

    public function find(OrderId $id): ?Order {
        $stmt = $this->pdo->prepare('
            SELECT o.*, GROUP_CONCAT(l.id) as line_ids
            FROM orders o
            LEFT JOIN order_lines l ON l.order_id = o.id
            WHERE o.id = ?
            GROUP BY o.id
        ');
        $stmt->execute([$id->toString()]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return $this->hydrate($data);
    }

    public function save(Order $order): void {
        $this->pdo->beginTransaction();

        try {
            // Sauvegarder la commande
            $this->pdo->prepare('
                INSERT INTO orders (id, customer_id, status, total_cents, created_at)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status),
                    total_cents = VALUES(total_cents)
            ')->execute([
                $order->id()->toString(),
                $order->customerId()->toString(),
                $order->status()->value,
                $order->total()->cents(),
                $order->createdAt()->format('Y-m-d H:i:s')
            ]);

            // Sauvegarder les lignes
            $this->saveLines($order);

            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    private function hydrate(array $data): Order {
        $lines = $this->loadLines($data['id']);

        return Order::reconstitute(
            id: OrderId::fromString($data['id']),
            customerId: CustomerId::fromString($data['customer_id']),
            status: OrderStatus::from($data['status']),
            total: Money::fromCents((int)$data['total_cents']),
            lines: $lines,
            createdAt: new \DateTimeImmutable($data['created_at'])
        );
    }
}
```

## Utiliser Doctrine ORM

```php
<?php
namespace Domain\Ordering;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'orders')]
final class Order {
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 36)]
    private string $customerId;

    #[ORM\Column(type: 'string', enumType: OrderStatus::class)]
    private OrderStatus $status;

    #[ORM\Embedded(class: Money::class)]
    private Money $total;

    #[ORM\OneToMany(targetEntity: OrderLine::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $lines;

    // Méthodes de domaine...
}

// Mapping d'Objet-Valeur
#[ORM\Embeddable]
final class Money {
    #[ORM\Column(type: 'integer')]
    private int $cents;

    #[ORM\Column(type: 'string', length: 3)]
    private string $currency;
}
```

## Event Sourcing (Avancé)

Au lieu de stocker l'état actuel, stocker tous les événements :

```php
<?php
final class EventSourcedOrderRepository implements OrderRepository {
    public function __construct(
        private EventStore $eventStore
    ) {}

    public function find(OrderId $id): ?Order {
        $events = $this->eventStore->getEventsFor($id->toString());

        if (empty($events)) {
            return null;
        }

        // Reconstruire la commande depuis les événements
        return Order::fromHistory($events);
    }

    public function save(Order $order): void {
        $events = $order->pullDomainEvents();
        $this->eventStore->append($order->id()->toString(), $events);
    }
}

// Event Store
final class SqlEventStore implements EventStore {
    public function append(string $aggregateId, array $events): void {
        foreach ($events as $event) {
            $this->pdo->prepare('
                INSERT INTO events (aggregate_id, event_type, payload, occurred_at)
                VALUES (?, ?, ?, ?)
            ')->execute([
                $aggregateId,
                get_class($event),
                json_encode($event),
                $event->occurredAt()->format('Y-m-d H:i:s.u')
            ]);
        }
    }
}
```

## Les Grimoires

- [Documentation Doctrine ORM](https://www.doctrine-project.org/projects/doctrine-orm/en/current/index.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
