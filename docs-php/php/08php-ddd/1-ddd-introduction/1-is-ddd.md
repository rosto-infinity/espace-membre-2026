---
source_course: "php-ddd"
source_lesson: "php-ddd-what-is-ddd"
---

# Qu'est-ce que le Domain-Driven Design ?

Le **Domain-Driven Design (DDD)** est une approche du développement logiciel qui centre la conception autour du domaine métier principal. Introduit par Eric Evans en 2003, le DDD aide les équipes à construire des logiciels complexes qui modélisent fidèlement les processus métier réels.

## Le Problème que Résout le DDD

Dans de nombreux projets logiciels, une déconnexion existe entre :

- **Les experts métier** qui comprennent le domaine
- **Les développeurs** qui écrivent le code

Cela conduit à :

- Des logiciels qui ne correspondent pas aux besoins métier
- Des malentendus et mauvaises communications
- Une dette technique liée à une mauvaise modélisation
- Des difficultés d'adaptation aux changements métier

## Principes Fondamentaux du DDD

### 1. Se Concentrer sur le Domaine Principal

Toutes les parties de votre système ne sont pas également importantes. Le DDD encourage à identifier et se concentrer sur :

```
┌─────────────────────────────────────────────────┐
│              Domaine Principal                   │
│  (Votre avantage concurrentiel - investissez)   │
│  Exemple : Algorithme de tarification           │
├─────────────────────────────────────────────────┤
│           Sous-domaines de Support              │
│  (Nécessaires mais non différenciants)          │
│  Exemple : Gestion des clients                  │
├─────────────────────────────────────────────────┤
│           Sous-domaines Génériques              │
│  (Problèmes communs - achetez ou réutilisez)    │
│  Exemple : Authentification, envoi d'emails     │
└─────────────────────────────────────────────────┘
```

### 2. Langage Omniprésent

Créez un vocabulaire partagé entre développeurs et experts métier :

```php
<?php
// MAUVAIS : Jargon technique que le métier ne comprend pas
class DataProcessor {
    public function executeTransaction(
        array $payload,
        string $entityId
    ): ResultDTO {
        // ...
    }
}

// BON : Langage qui correspond au domaine métier
class OrderFulfillment {
    public function shipOrder(
        Order $order,
        ShippingMethod $method
    ): Shipment {
        // ...
    }
}
```

### 3. Conception Pilotée par le Modèle

Le code doit être une expression directe du modèle du domaine :

```php
<?php
final class Order {
    private OrderId $id;
    private CustomerId $customerId;
    private OrderStatus $status;
    private Money $total;
    /** @var OrderLine[] */
    private array $lines;

    public function addItem(Product $product, Quantity $quantity): void {
        $this->ensureOrderIsModifiable();
        $this->lines[] = new OrderLine($product, $quantity);
        $this->recalculateTotal();
    }

    public function submit(): void {
        $this->ensureHasItems();
        $this->status = OrderStatus::Submitted;
        $this->recordThat(new OrderWasSubmitted($this->id));
    }

    public function cancel(CancellationReason $reason): void {
        if (!$this->status->canBeCancelled()) {
            throw new OrderCannotBeCancelled($this->id, $this->status);
        }
        $this->status = OrderStatus::Cancelled;
        $this->recordThat(new OrderWasCancelled($this->id, $reason));
    }
}
```

## Design Stratégique vs Tactique

Le DDD opère à deux niveaux :

**Design Stratégique** (haut niveau) :

- Comment diviser un grand système en parties plus petites
- Comment les équipes collaborent
- Où investir les efforts

**Design Tactique** (niveau d'implémentation) :

- Les briques comme Entités, Objets-Valeur, Agrégats
- Comment implémenter le modèle du domaine dans le code
- Patterns pour gérer la complexité

## Quand Utiliser le DDD

✅ **Bon candidat pour le DDD :**

- Logique métier complexe
- Projets long-terme avec des exigences évolutives
- Collaboration multi-équipes
- Le métier est l'avantage concurrentiel

❌ **Probablement excessif :**

- Applications CRUD simples
- Projets de courte durée
- Domaines bien compris et stables
- Systèmes techniques (non-métier)

## Les Blocs de Construction DDD

```
Patterns Stratégiques         Patterns Tactiques
───────────────────           ─────────────────
• Contexte Délimité           • Entité
• Langage Omniprésent         • Objet-Valeur
• Cartographie des Contextes  • Agrégat
• Sous-domaine                • Événement de Domaine
                              • Référentiel
                              • Service de Domaine
                              • Fabrique
```

## Un Exemple Simple

Un expert métier pourrait dire :

> « Quand un client passe une commande, nous devons vérifier le stock, réserver les articles, calculer le total avec les remises applicables, puis traiter le paiement. »

Approche DDD :

```php
<?php
final class PlaceOrderService {
    public function __construct(
        private InventoryService $inventory,
        private DiscountCalculator $discounts,
        private PaymentGateway $payments,
        private OrderRepository $orders
    ) {}

    public function placeOrder(PlaceOrderCommand $command): OrderId {
        // Vérifier et réserver le stock
        $reservations = $this->inventory->reserve(
            $command->items,
            $command->customerId
        );

        // Créer la commande avec la logique métier
        $order = Order::place(
            customerId: $command->customerId,
            items: $command->items,
            shippingAddress: $command->shippingAddress
        );

        // Appliquer les remises (logique de domaine)
        $discount = $this->discounts->calculateFor($order);
        $order->applyDiscount($discount);

        // Traiter le paiement
        $payment = $this->payments->charge(
            $order->total(),
            $command->paymentMethod
        );
        $order->confirmPayment($payment);

        // Persister
        $this->orders->save($order);

        return $order->id();
    }
}
```

Remarquez comment le code se lit presque comme la description métier.

## Les Grimoires

- [Référence Domain-Driven Design](https://www.domainlanguage.com/ddd/reference/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
