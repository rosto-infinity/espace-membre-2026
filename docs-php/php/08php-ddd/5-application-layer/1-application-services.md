---
source_course: "php-ddd"
source_lesson: "php-ddd-application-services"
---

# Services d'Application

Les **Services d'Application** orchestrent les cas d'usage en coordonnant les objets de domaine, les référentiels et les services d'infrastructure. Ils sont le point d'entrée pour les opérations de l'application.

## Rôle des Services d'Application

```
┌─────────────────────────────────────────────────────┐
│              Couche Présentation                     │
│          (Contrôleurs, CLI, API)                     │
└─────────────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────┐
│              Couche Application                      │
│  ┌─────────────────────────────────────────────┐    │
│  │        Services d'Application               │    │
│  │  • Orchestrent les cas d'usage              │    │
│  │  • Gestion des transactions                 │    │
│  │  • Sécurité / autorisation                  │    │
│  │  • Convertissent DTOs en objets de domaine  │    │
│  └─────────────────────────────────────────────┘    │
└─────────────────────────┬───────────────────────────┘
                          │
                          ▼
┌─────────────────────────────────────────────────────┐
│                 Couche Domaine                       │
│     (Entités, Objets-Valeur, Services Domaine)       │
└─────────────────────────────────────────────────────┘
```

## Implémenter des Services d'Application

```php
<?php
namespace Application\Order;

final class OrderApplicationService {
    public function __construct(
        private OrderRepository $orders,
        private CustomerRepository $customers,
        private ProductRepository $products,
        private InventoryService $inventory,
        private EventDispatcher $events,
        private TransactionManager $transaction
    ) {}

    public function placeOrder(PlaceOrderRequest $request): OrderId {
        return $this->transaction->execute(function () use ($request) {
            // 1. Charger les objets de domaine
            $customer = $this->customers->findOrFail($request->customerId);

            // 2. Valider les règles métier
            if (!$customer->canPlaceOrders()) {
                throw new CustomerCannotPlaceOrders($customer->id());
            }

            // 3. Construire les articles de commande
            $items = [];
            foreach ($request->items as $itemData) {
                $product = $this->products->findOrFail($itemData->productId);

                if (!$this->inventory->isAvailable($product->id(), $itemData->quantity)) {
                    throw new InsufficientInventory($product->id());
                }

                $items[] = [
                    'productId' => $product->id(),
                    'quantity' => new Quantity($itemData->quantity),
                    'unitPrice' => $product->price()
                ];
            }

            // 4. Exécuter la logique de domaine
            $order = Order::place(
                $this->orders->nextIdentity(),
                $customer->id(),
                $items
            );

            // 5. Persister
            $this->orders->save($order);

            // 6. Dispatcher les événements
            $this->events->dispatchAll($order->pullDomainEvents());

            return $order->id();
        });
    }

    public function cancelOrder(CancelOrderRequest $request): void {
        $this->transaction->execute(function () use ($request) {
            $order = $this->orders->findOrFail($request->orderId);

            $order->cancel(new CancellationReason($request->reason));

            $this->orders->save($order);
            $this->events->dispatchAll($order->pullDomainEvents());
        });
    }
}
```

## Objets de Transfert de Données (DTOs)

```php
<?php
namespace Application\Order\DTO;

final readonly class PlaceOrderRequest {
    public function __construct(
        public CustomerId $customerId,
        public Address $shippingAddress,
        /** @var OrderItemRequest[] */
        public array $items
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            customerId: CustomerId::fromString($data['customer_id']),
            shippingAddress: Address::fromArray($data['shipping_address']),
            items: array_map(
                fn($item) => OrderItemRequest::fromArray($item),
                $data['items']
            )
        );
    }
}

final readonly class OrderItemRequest {
    public function __construct(
        public ProductId $productId,
        public int $quantity
    ) {}

    public static function fromArray(array $data): self {
        return new self(
            productId: ProductId::fromString($data['product_id']),
            quantity: (int) $data['quantity']
        );
    }
}

// DTO de réponse
final readonly class OrderResponse {
    public function __construct(
        public string $id,
        public string $status,
        public string $total,
        public array $items,
        public string $createdAt
    ) {}

    public static function fromOrder(Order $order): self {
        return new self(
            id: $order->id()->toString(),
            status: $order->status()->value,
            total: $order->total()->format(),
            items: array_map(
                fn($line) => [
                    'product_id' => $line->productId()->toString(),
                    'quantity' => $line->quantity()->value(),
                    'unit_price' => $line->unitPrice()->format(),
                    'subtotal' => $line->subtotal()->format()
                ],
                $order->lines()
            ),
            createdAt: $order->createdAt()->format('c')
        );
    }
}
```

## Directives des Services d'Application

### 1. Services d'Application Légers

```php
<?php
// MAUVAIS : Logique métier dans le service d'application
class OrderService {
    public function placeOrder($data): void {
        $order = new Order();
        // Logique métier ici - devrait être dans le domaine !
        if (count($data['items']) > 100) {
            throw new TooManyItems();
        }
        foreach ($data['items'] as $item) {
            $order->total += $item['price'] * $item['qty'];
        }
    }
}

// BON : Déléguer au domaine
class OrderService {
    public function placeOrder(PlaceOrderRequest $request): void {
        // Le service d'application coordonne seulement
        $order = Order::place(...);
        // Les règles métier sont à l'intérieur de Order::place()
    }
}
```

### 2. Un Cas d'Usage par Méthode

```php
<?php
// Méthodes claires et focalisées
class OrderApplicationService {
    public function placeOrder(...): OrderId {}
    public function cancelOrder(...): void {}
    public function shipOrder(...): void {}
    public function addItemToOrder(...): void {}
}
```

### 3. Frontières de Transaction

```php
<?php
class OrderApplicationService {
    public function placeOrder(PlaceOrderRequest $request): OrderId {
        // Une transaction par cas d'usage
        return $this->transaction->execute(function () use ($request) {
            // Tous les changements validés ensemble ou annulés
        });
    }
}
```

## Les Grimoires

- [Application Services en DDD](https://www.domainlanguage.com/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
