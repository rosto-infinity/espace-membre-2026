---
source_course: "php-ddd"
source_lesson: "php-ddd-cqrs-pattern"
---

# CQRS : Ségrégation Responsabilité Commande-Requête

**CQRS** sépare les opérations de lecture (Requêtes) des opérations d'écriture (Commandes), permettant différents modèles optimisés pour chaque usage.

## Pourquoi CQRS ?

```
Traditionnel : Même modèle pour lectures et écritures
┌─────────────────────────────────────┐
│          Modèle Commande             │
│  - Logique de domaine complexe      │
│  - Utilisé pour écritures ET lectures│
│  - Les requêtes nécessitent le chargement des agrégats│
└─────────────────────────────────────┘

CQRS : Modèles séparés
┌─────────────────────┐  ┌─────────────────────┐
│  Modèle Commande    │  │  Modèle Requête     │
│  (Opérations écrit) │  │  (Opérations lect.) │
│                     │  │                     │
│  - Logique riche    │  │  - DTOs simples     │
│  - Agrégats         │  │  - Vues optimisées  │
│  - Règles cohérence │  │  - Dénormalisé      │
└─────────────────────┘  └─────────────────────┘
```

## Commandes

```php
<?php
namespace Application\Order\Command;

// Commande : Intention de changer l'état
final readonly class PlaceOrderCommand {
    public function __construct(
        public string $customerId,
        public array $items,
        public array $shippingAddress
    ) {}
}

final readonly class CancelOrderCommand {
    public function __construct(
        public string $orderId,
        public string $reason
    ) {}
}

// Gestionnaire de Commande
final class PlaceOrderHandler {
    public function __construct(
        private OrderRepository $orders,
        private CustomerRepository $customers,
        private EventDispatcher $events
    ) {}

    public function __invoke(PlaceOrderCommand $command): string {
        $customerId = CustomerId::fromString($command->customerId);
        $customer = $this->customers->findOrFail($customerId);

        $order = Order::place(
            $this->orders->nextIdentity(),
            $customerId,
            $this->buildItems($command->items)
        );

        $this->orders->save($order);
        $this->events->dispatchAll($order->pullDomainEvents());

        return $order->id()->toString();
    }
}
```

## Requêtes

```php
<?php
namespace Application\Order\Query;

// Requête : Demande de données
final readonly class GetOrderQuery {
    public function __construct(
        public string $orderId
    ) {}
}

final readonly class ListCustomerOrdersQuery {
    public function __construct(
        public string $customerId,
        public int $page = 1,
        public int $perPage = 20
    ) {}
}

// Gestionnaire de Requête - peut contourner le modèle de domaine pour les lectures
final class GetOrderHandler {
    public function __construct(
        private \PDO $pdo  // Accès direct à la DB pour les requêtes
    ) {}

    public function __invoke(GetOrderQuery $query): ?OrderView {
        $stmt = $this->pdo->prepare('
            SELECT
                o.id,
                o.status,
                o.total_cents,
                o.currency,
                c.name as customer_name,
                o.created_at
            FROM orders o
            JOIN customers c ON c.id = o.customer_id
            WHERE o.id = ?
        ');
        $stmt->execute([$query->orderId]);
        $data = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$data) {
            return null;
        }

        return new OrderView(
            id: $data['id'],
            status: $data['status'],
            total: number_format($data['total_cents'] / 100, 2),
            currency: $data['currency'],
            customerName: $data['customer_name'],
            createdAt: $data['created_at'],
            lines: $this->loadOrderLines($query->orderId)
        );
    }
}

// Modèle de lecture - optimisé pour l'affichage
final readonly class OrderView {
    public function __construct(
        public string $id,
        public string $status,
        public string $total,
        public string $currency,
        public string $customerName,
        public string $createdAt,
        public array $lines
    ) {}
}
```

## Bus de Commandes/Requêtes

```php
<?php
namespace Infrastructure\Bus;

interface CommandBus {
    public function dispatch(object $command): mixed;
}

interface QueryBus {
    public function ask(object $query): mixed;
}

final class SimpleCommandBus implements CommandBus {
    /** @var array<class-string, callable> */
    private array $handlers = [];

    public function register(string $commandClass, callable $handler): void {
        $this->handlers[$commandClass] = $handler;
    }

    public function dispatch(object $command): mixed {
        $class = get_class($command);

        if (!isset($this->handlers[$class])) {
            throw new HandlerNotFound($class);
        }

        return ($this->handlers[$class])($command);
    }
}

// Utilisation dans le contrôleur
final class OrderController {
    public function __construct(
        private CommandBus $commandBus,
        private QueryBus $queryBus
    ) {}

    public function place(Request $request): Response {
        $orderId = $this->commandBus->dispatch(
            new PlaceOrderCommand(
                customerId: $request->get('customer_id'),
                items: $request->get('items'),
                shippingAddress: $request->get('shipping_address')
            )
        );

        return new JsonResponse(['order_id' => $orderId], 201);
    }

    public function show(string $id): Response {
        $order = $this->queryBus->ask(new GetOrderQuery($id));

        if (!$order) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($order);
    }
}
```

## Avantages de CQRS

| Avantage        | Description                                                       |
| --------------- | ----------------------------------------------------------------- |
| **Performance** | Requêtes optimisées pour la lecture, pas de chargement d'agrégats |
| **Scalabilité** | Les côtés lecture et écriture scalent indépendamment              |
| **Simplicité**  | Chaque modèle focalisé sur une responsabilité                     |
| **Flexibilité** | Stockage différent pour lectures vs écritures                     |

## Les Grimoires

- [Pattern CQRS](https://martinfowler.com/bliki/CQRS.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
