---
source_course: "php-ddd"
source_lesson: "php-ddd-domain-services"
---

# Services de Domaine

Un **Service de Domaine** encapsule de la logique métier qui ne s'intègre pas naturellement dans une seule Entité ou Objet-Valeur. Il représente une opération ou un processus du langage omniprésent.

## Quand Utiliser les Services de Domaine

Utilisez un Service de Domaine quand :

1. **L'opération implique plusieurs agrégats**
2. **Calcul ou transformation sans état**
3. **Le comportement n'appartient à aucune entité**

```php
<?php
// MAUVAIS : Forcer le comportement dans une entité
class Order {
    public function calculateShipping(
        Address $from,
        ShippingCarrier $carrier
    ): Money {
        // Order ne devrait pas connaître le calcul des frais d'expédition !
    }
}

// BON : Service de Domaine pour les préoccupations transversales
final class ShippingCostCalculator {
    public function calculate(
        Order $order,
        Address $destination,
        ShippingMethod $method
    ): Money {
        $weight = $order->totalWeight();
        $distance = $this->distanceCalculator->between(
            $this->warehouse->address(),
            $destination
        );

        return $method->calculateCost($weight, $distance);
    }
}
```

## Exemples de Services de Domaine

### Calculateur de Prix

```php
<?php
namespace Domain\Pricing;

final class PricingService {
    public function __construct(
        private DiscountPolicyRepository $policies,
        private TaxCalculator $taxCalculator
    ) {}

    public function calculatePrice(
        Product $product,
        Customer $customer,
        Quantity $quantity
    ): PricingResult {
        $basePrice = $product->price()->multiply($quantity->value());

        // Appliquer les remises client
        $discount = $this->calculateDiscount(
            $customer,
            $product,
            $basePrice
        );

        $discountedPrice = $basePrice->subtract($discount);

        // Calculer la taxe
        $tax = $this->taxCalculator->calculate(
            $discountedPrice,
            $product->taxCategory(),
            $customer->taxJurisdiction()
        );

        return new PricingResult(
            basePrice: $basePrice,
            discount: $discount,
            tax: $tax,
            total: $discountedPrice->add($tax)
        );
    }

    private function calculateDiscount(
        Customer $customer,
        Product $product,
        Money $basePrice
    ): Money {
        $policies = $this->policies->findApplicable(
            $customer->tier(),
            $product->category()
        );

        $totalDiscount = Money::zero();

        foreach ($policies as $policy) {
            $discount = $policy->calculate($basePrice);
            $totalDiscount = $totalDiscount->add($discount);
        }

        return $totalDiscount;
    }
}
```

### Service de Transfert

```php
<?php
namespace Domain\Banking;

final class MoneyTransferService {
    public function transfer(
        Account $source,
        Account $destination,
        Money $amount
    ): TransferResult {
        // Valider les règles métier
        if (!$source->canWithdraw($amount)) {
            return TransferResult::failed(
                TransferFailure::InsufficientFunds
            );
        }

        if (!$destination->canReceive($amount)) {
            return TransferResult::failed(
                TransferFailure::DestinationRestricted
            );
        }

        // Créer l'enregistrement de transfert
        $transfer = Transfer::initiate(
            TransferId::generate(),
            $source->id(),
            $destination->id(),
            $amount
        );

        // Note : Les modifications de compte se font via des événements
        // pour maintenir les frontières des agrégats

        return TransferResult::initiated($transfer);
    }
}
```

## Caractéristiques des Services de Domaine

| Caractéristique             | Description                                                  |
| --------------------------- | ------------------------------------------------------------ |
| **Sans état**               | Pas d'état d'instance, opère sur les paramètres              |
| **Langage de domaine**      | Nommé avec le langage omniprésent                            |
| **Basé sur interface**      | Souvent défini comme interface, implémenté en infrastructure |
| **Logique de domaine pure** | Aucune préoccupation infrastructurelle                       |

## Services de Domaine vs Services d'Application

```php
<?php
// SERVICE DE DOMAINE : Logique métier pure
namespace Domain\Ordering;

final class OrderPricingService {
    public function calculateTotal(Order $order): Money {
        // Calcul pur, pas d'E/S
    }
}

// SERVICE D'APPLICATION : Orchestre le cas d'usage
namespace Application\Ordering;

final class PlaceOrderHandler {
    public function __construct(
        private OrderRepository $orders,
        private OrderPricingService $pricing,
        private EventBus $events
    ) {}

    public function handle(PlaceOrderCommand $command): OrderId {
        // Coordonne : chargement, logique domaine, sauvegarde, événements
        $order = Order::place(...);
        $total = $this->pricing->calculateTotal($order);
        $this->orders->save($order);
        $this->events->dispatch($order->pullDomainEvents());
        return $order->id();
    }
}
```

## Les Grimoires

- [Services de Domaine vs Services d'Application](https://enterprisecraftsmanship.com/posts/domain-vs-application-services/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
