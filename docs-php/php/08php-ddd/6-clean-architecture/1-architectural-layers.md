---
source_course: "php-ddd"
source_lesson: "php-ddd-architectural-layers"
---

# Couches Architecturales en DDD

L'**Architecture Propre** (aussi appelée Oignon ou Hexagonale) organise le code en couches concentriques avec des règles de dépendance strictes.

## La Règle de Dépendance

> Les dépendances ne peuvent pointer que **vers l'intérieur**. Les couches internes ne savent rien des couches externes.

```
┌─────────────────────────────────────────────────────────────┐
│              Couche Infrastructure                           │
│  (Frameworks, Base de Données, Services Externes, UI)        │
│  ┌─────────────────────────────────────────────────────┐    │
│  │           Couche Application                         │    │
│  │  (Cas d'Usage, Services d'Application, DTOs)         │    │
│  │  ┌─────────────────────────────────────────────┐    │    │
│  │  │           Couche Domaine                    │    │    │
│  │  │  (Entités, Objets-Valeur, Services Domaine) │    │    │
│  │  │  ┌─────────────────────────────────────┐   │    │    │
│  │  │  │   Cœur du Modèle de Domaine         │   │    │    │
│  │  │  │  (Règles Métier, Invariants)         │   │    │    │
│  │  │  └─────────────────────────────────────┘   │    │    │
│  │  └─────────────────────────────────────────────┘    │    │
│  └─────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘

Dépendances : Infrastructure → Application → Domaine
```

## Responsabilités des Couches

### Couche Domaine (Interne)

```php
<?php
namespace Domain\Order;

// Logique métier pure - pas de dépendances framework
final class Order {
    public function place(...): void { /* règles métier */ }
    public function cancel(...): void { /* règles métier */ }
}

final readonly class Money {
    public function add(Money $other): self { /* calcul pur */ }
}

// Interface définie dans le domaine, implémentée ailleurs
interface OrderRepository {
    public function save(Order $order): void;
}
```

### Couche Application

```php
<?php
namespace Application\Order;

// Orchestration de cas d'usage
final class PlaceOrderHandler {
    public function __construct(
        private OrderRepository $orders,  // Interface du domaine
        private EventDispatcher $events   // Interface application
    ) {}

    public function __invoke(PlaceOrderCommand $command): string {
        // Orchestrer les objets de domaine
        $order = Order::place(...);
        $this->orders->save($order);
        $this->events->dispatch($order->pullDomainEvents());
        return $order->id()->toString();
    }
}
```

### Couche Infrastructure (Externe)

```php
<?php
namespace Infrastructure\Persistence;

use Domain\Order\OrderRepository;
use Domain\Order\Order;

// Implémente l'interface de domaine
final class DoctrineOrderRepository implements OrderRepository {
    public function __construct(
        private EntityManagerInterface $em  // Dépendance framework OK ici
    ) {}

    public function save(Order $order): void {
        $this->em->persist($order);
        $this->em->flush();
    }
}

namespace Infrastructure\Http;

use Symfony\Component\HttpFoundation\Request;  // Framework OK ici

final class OrderController {
    public function place(Request $request): Response {
        // Convertir HTTP vers la couche application
        $command = new PlaceOrderCommand(...);
        $orderId = $this->commandBus->dispatch($command);
        return new JsonResponse(['id' => $orderId]);
    }
}
```

## Structure de Répertoires

```
src/
├── Domain/                          # Logique métier centrale
│   ├── Order/
│   │   ├── Order.php                # Racine d'agrégat
│   │   ├── OrderLine.php            # Entité
│   │   ├── OrderId.php              # Objet-valeur
│   │   ├── OrderStatus.php          # Enum
│   │   ├── OrderRepository.php      # Interface
│   │   └── Events/
│   │       ├── OrderPlaced.php
│   │       └── OrderCancelled.php
│   ├── Customer/
│   └── Shared/
│       ├── Money.php
│       └── DomainEvent.php
│
├── Application/                     # Cas d'usage
│   ├── Order/
│   │   ├── Command/
│   │   │   ├── PlaceOrderCommand.php
│   │   │   └── PlaceOrderHandler.php
│   │   ├── Query/
│   │   │   ├── GetOrderQuery.php
│   │   │   └── GetOrderHandler.php
│   │   └── DTO/
│   │       └── OrderResponse.php
│   └── Shared/
│       ├── CommandBus.php
│       └── QueryBus.php
│
└── Infrastructure/                  # Préoccupations externes
    ├── Persistence/
    │   ├── Doctrine/
    │   │   ├── DoctrineOrderRepository.php
    │   │   └── Mapping/
    │   └── InMemory/
    │       └── InMemoryOrderRepository.php
    ├── Http/
    │   ├── Controller/
    │   │   └── OrderController.php
    │   └── Middleware/
    ├── Event/
    │   ├── SymfonyEventDispatcher.php
    │   └── Listeners/
    └── External/
        ├── PaymentGateway/
        └── EmailService/
```

## Injection de Dépendances

```php
<?php
// services.yaml (Symfony) ou configuration DI similaire

// Interfaces du domaine liées aux implémentations infrastructure
return [
    // Liaisons des référentiels
    Domain\Order\OrderRepository::class =>
        Infrastructure\Persistence\Doctrine\DoctrineOrderRepository::class,

    Domain\Customer\CustomerRepository::class =>
        Infrastructure\Persistence\Doctrine\DoctrineCustomerRepository::class,

    // Interfaces application
    Application\Shared\EventDispatcher::class =>
        Infrastructure\Event\SymfonyEventDispatcher::class,

    Application\Shared\CommandBus::class =>
        Infrastructure\Bus\TacticianCommandBus::class,
];
```

## Avantages

| Avantage                      | Comment Atteint                                   |
| ----------------------------- | ------------------------------------------------- |
| **Testabilité**               | Le domaine peut être testé sans infrastructure    |
| **Flexibilité**               | Changer d'implémentation sans modifier le domaine |
| **Maintenabilité**            | Des frontières claires évitent le code spaghetti  |
| **Indépendance du Framework** | Le domaine ne dépend pas des frameworks           |

## Les Grimoires

- [Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
