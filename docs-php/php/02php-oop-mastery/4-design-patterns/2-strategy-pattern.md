---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-strategy-pattern"
---

# Le Pattern Stratégie (Strategy Pattern)

Le pattern Stratégie **définit une famille d'algorithmes**, encapsule chacun d'eux et les rend interchangeables.

## Implémentation de Base

```php
<?php
interface PaymentStrategy {
    public function pay(float $amount): bool;
    public function getName(): string;
}

class CreditCardPayment implements PaymentStrategy {
    public function __construct(
        private string $cardNumber,
        private string $cvv
    ) {}

    public function pay(float $amount): bool {
        echo "Débit de {$amount} € sur la carte de crédit\n";
        return true;
    }

    public function getName(): string {
        return 'Carte de Crédit';
    }
}

class PayPalPayment implements PaymentStrategy {
    public function __construct(
        private string $email
    ) {}

    public function pay(float $amount): bool {
        echo "Transfert de {$amount} € via PayPal\n";
        return true;
    }

    public function getName(): string {
        return 'PayPal';
    }
}

class CryptoPayment implements PaymentStrategy {
    public function __construct(
        private string $walletAddress
    ) {}

    public function pay(float $amount): bool {
        echo "Envoi de {$amount} € en crypto\n";
        return true;
    }

    public function getName(): string {
        return 'Cryptomonnaie';
    }
}

// Contexte
class ShoppingCart {
    private array $items = [];
    private ?PaymentStrategy $paymentMethod = null;

    public function addItem(string $name, float $price): void {
        $this->items[] = ['name' => $name, 'price' => $price];
    }

    public function setPaymentMethod(PaymentStrategy $method): void {
        $this->paymentMethod = $method;
    }

    public function checkout(): bool {
        if ($this->paymentMethod === null) {
            throw new RuntimeException('Aucun moyen de paiement sélectionné');
        }

        $total = array_sum(array_column($this->items, 'price'));
        echo "Payer avec : {$this->paymentMethod->getName()}\n";
        return $this->paymentMethod->pay($total);
    }
}

// Utilisation
$cart = new ShoppingCart();
$cart->addItem('Widget', 29.99);
$cart->addItem('Gadget', 49.99);

// Choisir la stratégie à l'exécution
$cart->setPaymentMethod(new CreditCardPayment('4111...', '123'));
$cart->checkout();

// Ou passer à une autre stratégie
$cart->setPaymentMethod(new PayPalPayment('user@example.com'));
$cart->checkout();
```

## Exemple de Stratégie de Tri

```php
<?php
interface SortStrategy {
    public function sort(array $data): array;
}

class QuickSort implements SortStrategy {
    public function sort(array $data): array {
        sort($data);  // Quicksort natif de PHP
        return $data;
    }
}

class MergeSort implements SortStrategy {
    public function sort(array $data): array {
        return $this->mergeSort($data);
    }

    private function mergeSort(array $arr): array {
        // ...
    }
}

class Sorter {
    public function __construct(
        private SortStrategy $strategy
    ) {}

    public function setStrategy(SortStrategy $strategy): void {
        $this->strategy = $strategy;
    }

    public function sort(array $data): array {
        return $this->strategy->sort($data);
    }
}
```

## Exemple Concret

**Calcul de remise avec le pattern Stratégie**

```php
<?php
declare(strict_types=1);

// Pattern Stratégie pour les remises
interface DiscountStrategy {
    public function calculate(float $total): float;
    public function getDescription(): string;
}

class NoDiscount implements DiscountStrategy {
    public function calculate(float $total): float {
        return $total;
    }

    public function getDescription(): string {
        return 'Aucune remise';
    }
}

class PercentageDiscount implements DiscountStrategy {
    public function __construct(
        private float $percent
    ) {}

    public function calculate(float $total): float {
        return $total * (1 - $this->percent / 100);
    }

    public function getDescription(): string {
        return "{$this->percent}% de remise";
    }
}

class FixedDiscount implements DiscountStrategy {
    public function __construct(
        private float $amount
    ) {}

    public function calculate(float $total): float {
        return max(0, $total - $this->amount);
    }

    public function getDescription(): string {
        return "{$this->amount} € de remise";
    }
}

class Order {
    private DiscountStrategy $discount;

    public function __construct(
        private float $subtotal
    ) {
        $this->discount = new NoDiscount();
    }

    public function applyDiscount(DiscountStrategy $discount): void {
        $this->discount = $discount;
    }

    public function getTotal(): float {
        return $this->discount->calculate($this->subtotal);
    }

    public function getSummary(): string {
        return sprintf(
            "Sous-total : %.2f €\nRemise : %s\nTotal : %.2f €",
            $this->subtotal,
            $this->discount->getDescription(),
            $this->getTotal()
        );
    }
}

$order = new Order(100.00);
echo $order->getSummary() . "\n\n";

$order->applyDiscount(new PercentageDiscount(20));
echo $order->getSummary();
?>
```

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
