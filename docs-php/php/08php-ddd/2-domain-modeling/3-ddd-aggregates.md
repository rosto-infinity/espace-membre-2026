---
source_course: "php-ddd"
source_lesson: "php-ddd-aggregates"
---

# Agrégats

Un **Agrégat** est un ensemble d'objets de domaine traités comme une seule unité pour les changements de données. Chaque agrégat a une entité racine (la **Racine d'Agrégat**) qui contrôle tout accès à l'agrégat.

## Pourquoi des Agrégats ?

Sans agrégats, maintenir la cohérence est difficile :

```php
<?php
// PROBLÈME : État incohérent
$order = $orderRepo->find($orderId);
$orderLine = $orderLineRepo->find($lineId);

// Qui s'assure que le total de la commande reste correct ?
$orderLine->changeQuantity(5);
$orderLineRepo->save($orderLine);
// Le total de la commande est maintenant faux !
```

Avec des agrégats :

```php
<?php
// SOLUTION : L'agrégat contrôle la cohérence
$order = $orderRepo->find($orderId);

// Tous les changements passent par la racine d'agrégat
$order->updateLineQuantity($lineId, 5);
// L'agrégat s'assure que le total est recalculé

$orderRepo->save($order);  // Sauvegarder l'agrégat entier
```

## Conception d'un Agrégat

```
┌─────────────────────────────────────────────┐
│           AGRÉGAT COMMANDE                  │
│  ┌───────────────────────────────────────┐  │
│  │    Order (Racine d'Agrégat)           │  │
│  │  - id: OrderId                        │  │
│  │  - status: OrderStatus                │  │
│  │  - total: Money                       │  │
│  └───────────────────────────────────────┘  │
│       │                                     │
│       │ contient                            │
│       ▼                                     │
│  ┌─────────────────┐  ┌─────────────────┐   │
│  │   OrderLine     │  │   OrderLine     │   │
│  │  - productId    │  │  - productId    │   │
│  │  - quantity     │  │  - quantity     │   │
│  │  - unitPrice    │  │  - unitPrice    │   │
│  └─────────────────┘  └─────────────────┘   │
│                                             │
│  Frontière : Seul Order est accessible     │
│              depuis l'extérieur            │
└─────────────────────────────────────────────┘
```

## Implémenter un Agrégat

```php
<?php
declare(strict_types=1);

namespace Domain\Ordering;

/**
 * Racine d'Agrégat Order
 */
final class Order {
    private OrderId $id;
    private CustomerId $customerId;
    private OrderStatus $status;
    private Money $total;
    /** @var array<string, OrderLine> */
    private array $lines = [];
    private array $domainEvents = [];

    private function __construct(
        OrderId $id,
        CustomerId $customerId
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->status = OrderStatus::Draft;
        $this->total = Money::zero();
    }

    public static function place(
        OrderId $id,
        CustomerId $customerId,
        array $items
    ): self {
        $order = new self($id, $customerId);

        foreach ($items as $item) {
            $order->addLine(
                $item['productId'],
                $item['quantity'],
                $item['unitPrice']
            );
        }

        $order->recordEvent(new OrderWasPlaced($id, $customerId));

        return $order;
    }

    /**
     * Ajouter une ligne à la commande
     */
    public function addLine(
        ProductId $productId,
        Quantity $quantity,
        Money $unitPrice
    ): void {
        $this->assertModifiable();

        $line = new OrderLine($productId, $quantity, $unitPrice);
        $this->lines[$productId->toString()] = $line;

        $this->recalculateTotal();
    }

    /**
     * Mettre à jour la quantité d'une ligne existante
     */
    public function updateLineQuantity(
        ProductId $productId,
        Quantity $newQuantity
    ): void {
        $this->assertModifiable();

        $key = $productId->toString();
        if (!isset($this->lines[$key])) {
            throw new LineNotFound($productId);
        }

        if ($newQuantity->isZero()) {
            $this->removeLine($productId);
            return;
        }

        $this->lines[$key] = $this->lines[$key]->withQuantity($newQuantity);
        $this->recalculateTotal();
    }

    /**
     * Supprimer une ligne
     */
    public function removeLine(ProductId $productId): void {
        $this->assertModifiable();

        $key = $productId->toString();
        if (!isset($this->lines[$key])) {
            throw new LineNotFound($productId);
        }

        unset($this->lines[$key]);
        $this->recalculateTotal();
    }

    /**
     * Soumettre la commande pour traitement
     */
    public function submit(): void {
        $this->assertModifiable();

        if (empty($this->lines)) {
            throw CannotSubmitOrder::becauseItIsEmpty();
        }

        $this->status = OrderStatus::Submitted;
        $this->recordEvent(new OrderWasSubmitted($this->id, $this->total));
    }

    /**
     * Annuler la commande
     */
    public function cancel(CancellationReason $reason): void {
        if (!$this->status->canBeCancelled()) {
            throw CannotCancelOrder::becauseStatusIs($this->status);
        }

        $this->status = OrderStatus::Cancelled;
        $this->recordEvent(new OrderWasCancelled($this->id, $reason));
    }

    // Accesseurs
    public function id(): OrderId { return $this->id; }
    public function status(): OrderStatus { return $this->status; }
    public function total(): Money { return $this->total; }

    /** @return OrderLine[] */
    public function lines(): array {
        return array_values($this->lines);
    }

    // Gestion des événements
    public function pullDomainEvents(): array {
        $events = $this->domainEvents;
        $this->domainEvents = [];
        return $events;
    }

    private function recordEvent(object $event): void {
        $this->domainEvents[] = $event;
    }

    // Application des invariants
    private function assertModifiable(): void {
        if ($this->status !== OrderStatus::Draft) {
            throw OrderNotModifiable::becauseStatusIs($this->status);
        }
    }

    private function recalculateTotal(): void {
        $this->total = array_reduce(
            $this->lines,
            fn(Money $sum, OrderLine $line) => $sum->add($line->subtotal()),
            Money::zero()
        );
    }
}
```

## Règles des Agrégats

### 1. Référencer par Identité

```php
<?php
// MAUVAIS : Référence directe à un autre agrégat
class Order {
    private Customer $customer;  // Objet Customer complet
}

// BON : Référencer par identité
class Order {
    private CustomerId $customerId;  // Juste l'ID
}
```

### 2. Une Transaction = Un Agrégat

```php
<?php
// MAUVAIS : Modifier plusieurs agrégats dans une transaction
public function transferMoney(Account $from, Account $to, Money $amount): void {
    $from->withdraw($amount);
    $to->deposit($amount);
    // Et si la sauvegarde de $to échoue ? $from est déjà débité !
}

// BON : Utiliser des événements de domaine pour la cohérence éventuelle
public function withdraw(Account $account, Money $amount): void {
    $account->withdraw($amount);
    $this->accountRepo->save($account);
    // Dispatcher l'événement : MoneyWithdrawn
    // Un autre gestionnaire déposera dans le compte cible
}
```

### 3. Garder les Agrégats Petits

```php
<?php
// MAUVAIS : Agrégat géant avec tout
class Order {
    private Customer $customer;
    private array $lines;
    private array $payments;
    private array $shipments;
    private array $reviews;
    // Trop !
}

// BON : Agrégat focalisé
class Order {
    private CustomerId $customerId;  // Référence seulement
    private array $lines;
    // Paiements, Expéditions sont des agrégats séparés
}
```

## La Ligne de Commande (Entité Interne)

```php
<?php
/**
 * OrderLine - Entité au sein de l'Agrégat Order
 * Ne peut pas exister en dehors de l'agrégat Order
 */
final readonly class OrderLine {
    public function __construct(
        private ProductId $productId,
        private Quantity $quantity,
        private Money $unitPrice
    ) {}

    public function productId(): ProductId {
        return $this->productId;
    }

    public function quantity(): Quantity {
        return $this->quantity;
    }

    public function subtotal(): Money {
        return $this->unitPrice->multiply($this->quantity->value());
    }

    public function withQuantity(Quantity $quantity): self {
        return new self(
            $this->productId,
            $quantity,
            $this->unitPrice
        );
    }
}
```

## Les Grimoires

- [Effective Aggregate Design](https://www.dddcommunity.org/library/vernon_2011/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
