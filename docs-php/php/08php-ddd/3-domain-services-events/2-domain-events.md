---
source_course: "php-ddd"
source_lesson: "php-ddd-domain-events"
---

# Événements de Domaine

Les **Événements de Domaine** capturent quelque chose d'important qui s'est produit dans le domaine. Ils sont nommés au passé et représentent des faits qui se sont produits.

## Pourquoi des Événements de Domaine ?

1. **Découpler les agrégats** — communiquer sans références directes
2. **Piste d'audit** — enregistrer ce qui s'est passé et quand
3. **Déclencher des effets de bord** — notifications, intégrations
4. **Permettre la cohérence éventuelle** — coordonner à travers les frontières

## Implémenter des Événements de Domaine

```php
<?php
namespace Domain\Shared;

interface DomainEvent {
    public function occurredAt(): \DateTimeImmutable;
    public function aggregateId(): string;
}

abstract class BaseDomainEvent implements DomainEvent {
    private \DateTimeImmutable $occurredAt;

    public function __construct() {
        $this->occurredAt = new \DateTimeImmutable();
    }

    public function occurredAt(): \DateTimeImmutable {
        return $this->occurredAt;
    }
}
```

### Événements Concrets

```php
<?php
namespace Domain\Ordering\Events;

final class OrderWasPlaced extends BaseDomainEvent {
    public function __construct(
        public readonly OrderId $orderId,
        public readonly CustomerId $customerId,
        public readonly Money $total,
        public readonly array $lineItems
    ) {
        parent::__construct();
    }

    public function aggregateId(): string {
        return $this->orderId->toString();
    }
}

final class OrderWasShipped extends BaseDomainEvent {
    public function __construct(
        public readonly OrderId $orderId,
        public readonly ShipmentId $shipmentId,
        public readonly string $trackingNumber
    ) {
        parent::__construct();
    }

    public function aggregateId(): string {
        return $this->orderId->toString();
    }
}

final class OrderWasCancelled extends BaseDomainEvent {
    public function __construct(
        public readonly OrderId $orderId,
        public readonly CancellationReason $reason
    ) {
        parent::__construct();
    }

    public function aggregateId(): string {
        return $this->orderId->toString();
    }
}
```

## Enregistrer des Événements dans les Agrégats

```php
<?php
trait RecordsEvents {
    private array $domainEvents = [];

    protected function recordThat(DomainEvent $event): void {
        $this->domainEvents[] = $event;
    }

    public function pullDomainEvents(): array {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }
}

final class Order {
    use RecordsEvents;

    public static function place(
        OrderId $id,
        CustomerId $customerId,
        array $items
    ): self {
        $order = new self($id, $customerId);

        foreach ($items as $item) {
            $order->addLine($item);
        }

        $order->recordThat(new OrderWasPlaced(
            orderId: $id,
            customerId: $customerId,
            total: $order->total(),
            lineItems: $order->lines()
        ));

        return $order;
    }

    public function cancel(CancellationReason $reason): void {
        if (!$this->status->canBeCancelled()) {
            throw CannotCancelOrder::withStatus($this->status);
        }

        $this->status = OrderStatus::Cancelled;

        $this->recordThat(new OrderWasCancelled(
            orderId: $this->id,
            reason: $reason
        ));
    }
}
```

## Gestionnaires d'Événements

```php
<?php
namespace Application\Ordering\Handlers;

final class SendOrderConfirmationEmail {
    public function __construct(
        private EmailService $emailService,
        private CustomerRepository $customers
    ) {}

    public function __invoke(OrderWasPlaced $event): void {
        $customer = $this->customers->find($event->customerId);

        $this->emailService->send(
            to: $customer->email(),
            template: 'order-confirmation',
            data: [
                'orderId' => $event->orderId->toString(),
                'total' => $event->total->format(),
                'items' => $event->lineItems
            ]
        );
    }
}

final class UpdateInventoryOnOrderPlaced {
    public function __construct(
        private InventoryService $inventory
    ) {}

    public function __invoke(OrderWasPlaced $event): void {
        foreach ($event->lineItems as $item) {
            $this->inventory->reserve(
                $item->productId,
                $item->quantity
            );
        }
    }
}

final class ReleaseInventoryOnOrderCancelled {
    public function __construct(
        private InventoryService $inventory,
        private OrderRepository $orders
    ) {}

    public function __invoke(OrderWasCancelled $event): void {
        $order = $this->orders->find($event->orderId);

        foreach ($order->lines() as $line) {
            $this->inventory->release(
                $line->productId(),
                $line->quantity()
            );
        }
    }
}
```

## Dispatcher d'Événements

```php
<?php
namespace Infrastructure\Events;

final class EventDispatcher {
    /** @var array<class-string, callable[]> */
    private array $handlers = [];

    public function subscribe(string $eventClass, callable $handler): void {
        $this->handlers[$eventClass][] = $handler;
    }

    public function dispatch(object $event): void {
        $eventClass = get_class($event);

        foreach ($this->handlers[$eventClass] ?? [] as $handler) {
            $handler($event);
        }
    }

    public function dispatchAll(array $events): void {
        foreach ($events as $event) {
            $this->dispatch($event);
        }
    }
}

// Utilisation dans le Service d'Application
final class PlaceOrderHandler {
    public function handle(PlaceOrderCommand $command): OrderId {
        $order = Order::place(
            OrderId::generate(),
            $command->customerId,
            $command->items
        );

        $this->orderRepository->save($order);

        // Dispatcher les événements de domaine
        $this->eventDispatcher->dispatchAll(
            $order->pullDomainEvents()
        );

        return $order->id();
    }
}
```

## Les Grimoires

- [Pattern Événements de Domaine](https://martinfowler.com/eaaDev/DomainEvent.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
