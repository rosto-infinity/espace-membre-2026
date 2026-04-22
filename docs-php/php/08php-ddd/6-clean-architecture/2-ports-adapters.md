---
source_course: "php-ddd"
source_lesson: "php-ddd-ports-adapters"
---

# Ports et Adaptateurs (Architecture Hexagonale)

Les **Ports et Adaptateurs** sont une autre façon de visualiser l'Architecture Propre. Le cœur de l'application définit des « ports » (interfaces), et des « adaptateurs » connectent le monde extérieur.

## Le Modèle Hexagonal

```
                    ┌─────────────────┐
          API REST  │    Adaptateur   │  CLI
              ◄─────┤   (Primaire /   ├─────►
                    │   Pilotant)     │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
                    │                 │
           Port ────┤  Cœur Applic.  ├──── Port
         (Entrée)   │                 │  (Sortie)
                    │                 │
                    └────────┬────────┘
                             │
                    ┌────────▼────────┐
         Base données│   Adaptateur  │  API Externe
              ◄─────┤  (Secondaire / ├─────►
                    │   Piloté)      │
                    └─────────────────┘
```

## Ports Primaires (Pilotants/Entrée)

Ports qui pilotent l'application — actions utilisateur :

```php
<?php
namespace Application\Port\Input;

// Port : Ce que l'application peut faire
interface PlaceOrder {
    public function execute(PlaceOrderRequest $request): OrderId;
}

interface CancelOrder {
    public function execute(CancelOrderRequest $request): void;
}

interface GetOrder {
    public function execute(string $orderId): ?OrderResponse;
}
```

## Adaptateurs Primaires (Contrôleurs)

```php
<?php
namespace Infrastructure\Adapter\Input\Http;

use Application\Port\Input\PlaceOrder;

// Adaptateur : HTTP vers Application
final class OrderController {
    public function __construct(
        private PlaceOrder $placeOrder,
        private GetOrder $getOrder
    ) {}

    public function place(Request $request): Response {
        $orderId = $this->placeOrder->execute(
            new PlaceOrderRequest(
                customerId: $request->json('customer_id'),
                items: $request->json('items')
            )
        );

        return new JsonResponse(['id' => $orderId->toString()], 201);
    }
}

namespace Infrastructure\Adapter\Input\Cli;

// Adaptateur : CLI vers Application
final class PlaceOrderCommand extends Command {
    protected function execute(InputInterface $input): int {
        $orderId = $this->placeOrder->execute(
            new PlaceOrderRequest(
                customerId: $input->getArgument('customer'),
                items: json_decode($input->getArgument('items'), true)
            )
        );

        $this->output->writeln("Commande créée : {$orderId}");
        return 0;
    }
}
```

## Ports Secondaires (Pilotés/Sortie)

Ports que l'application utilise — besoins infrastructure :

```php
<?php
namespace Application\Port\Output;

// Ce dont l'application a besoin de l'extérieur
interface OrderPersistence {
    public function save(Order $order): void;
    public function find(OrderId $id): ?Order;
    public function nextIdentity(): OrderId;
}

interface PaymentProcessor {
    public function charge(Money $amount, PaymentMethod $method): PaymentResult;
    public function refund(PaymentId $paymentId): RefundResult;
}

interface NotificationSender {
    public function sendOrderConfirmation(Order $order, Customer $customer): void;
    public function sendShipmentNotification(Shipment $shipment): void;
}

interface InventoryChecker {
    public function isAvailable(ProductId $productId, Quantity $quantity): bool;
    public function reserve(ProductId $productId, Quantity $quantity): Reservation;
}
```

## Adaptateurs Secondaires (Infrastructure)

```php
<?php
namespace Infrastructure\Adapter\Output\Persistence;

use Application\Port\Output\OrderPersistence;

// Adaptateur : Application vers Base de Données
final class DoctrineOrderPersistence implements OrderPersistence {
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function save(Order $order): void {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function find(OrderId $id): ?Order {
        return $this->em->find(Order::class, $id->toString());
    }
}

namespace Infrastructure\Adapter\Output\Payment;

use Application\Port\Output\PaymentProcessor;

// Adaptateur : Application vers Stripe
final class StripePaymentProcessor implements PaymentProcessor {
    public function __construct(
        private StripeClient $stripe
    ) {}

    public function charge(Money $amount, PaymentMethod $method): PaymentResult {
        try {
            $charge = $this->stripe->charges->create([
                'amount' => $amount->cents(),
                'currency' => strtolower($amount->currency()->value),
                'source' => $method->token(),
            ]);

            return PaymentResult::success(
                PaymentId::fromString($charge->id)
            );
        } catch (StripeException $e) {
            return PaymentResult::failed($e->getMessage());
        }
    }
}
```

## Tests avec Ports et Adaptateurs

```php
<?php
namespace Tests\Application;

class PlaceOrderTest extends TestCase {
    public function test_places_order_successfully(): void {
        // Utiliser des adaptateurs de test
        $persistence = new InMemoryOrderPersistence();
        $inventory = new AlwaysAvailableInventory();
        $events = new CollectingEventDispatcher();

        $useCase = new PlaceOrderUseCase(
            $persistence,
            $inventory,
            $events
        );

        $orderId = $useCase->execute(new PlaceOrderRequest(
            customerId: 'client-1',
            items: [['product_id' => 'prod-1', 'quantity' => 2]]
        ));

        // Assertions
        $this->assertNotNull($persistence->find($orderId));
        $this->assertCount(1, $events->dispatched());
    }
}

// Doublures de test
final class InMemoryOrderPersistence implements OrderPersistence {
    private array $orders = [];

    public function save(Order $order): void {
        $this->orders[$order->id()->toString()] = $order;
    }

    public function find(OrderId $id): ?Order {
        return $this->orders[$id->toString()] ?? null;
    }
}

final class AlwaysAvailableInventory implements InventoryChecker {
    public function isAvailable(ProductId $p, Quantity $q): bool {
        return true;
    }
}
```

## Les Grimoires

- [Architecture Hexagonale](https://alistair.cockburn.us/hexagonal-architecture/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
