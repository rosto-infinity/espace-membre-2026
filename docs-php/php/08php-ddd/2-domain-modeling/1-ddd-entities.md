---
source_course: "php-ddd"
source_lesson: "php-ddd-entities"
---

# Entités

Une **Entité** est un objet de domaine défini par son identité plutôt que par ses attributs. Deux entités avec les mêmes attributs mais des identités différentes sont considérées comme des objets différents.

## Identité vs Égalité

Considérez un `Customer` :

```php
<?php
// Deux clients avec le même nom mais des IDs différents
$customer1 = new Customer(
    id: new CustomerId('cust-001'),
    name: 'Jean Dupont'
);

$customer2 = new Customer(
    id: new CustomerId('cust-002'),
    name: 'Jean Dupont'
);

// Ce sont des entités DIFFÉRENTES (identité différente)
$customer1->equals($customer2);  // false

// Même si on change les attributs, l'identité reste
$customer1->changeName('Jonathan Dupont');
$customer1->id();  // Toujours 'cust-001'
```

## Implémenter des Entités

```php
<?php
declare(strict_types=1);

namespace Domain\Order;

final class Order {
    private OrderId $id;
    private CustomerId $customerId;
    private OrderStatus $status;
    private Money $total;
    /** @var OrderLine[] */
    private array $lines = [];
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $submittedAt = null;

    private function __construct(
        OrderId $id,
        CustomerId $customerId
    ) {
        $this->id = $id;
        $this->customerId = $customerId;
        $this->status = OrderStatus::Draft;
        $this->total = Money::zero();
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Constructeur nommé - exprime l'intention
     */
    public static function create(
        OrderId $id,
        CustomerId $customerId
    ): self {
        return new self($id, $customerId);
    }

    /**
     * Reconstituer depuis la persistance - sans validation
     */
    public static function reconstitute(
        OrderId $id,
        CustomerId $customerId,
        OrderStatus $status,
        array $lines,
        \DateTimeImmutable $createdAt,
        ?\DateTimeImmutable $submittedAt
    ): self {
        $order = new self($id, $customerId);
        $order->status = $status;
        $order->lines = $lines;
        $order->createdAt = $createdAt;
        $order->submittedAt = $submittedAt;
        $order->recalculateTotal();
        return $order;
    }

    public function id(): OrderId {
        return $this->id;
    }

    public function addLine(
        ProductId $productId,
        Quantity $quantity,
        Money $unitPrice
    ): void {
        $this->assertDraft();

        $this->lines[] = new OrderLine(
            productId: $productId,
            quantity: $quantity,
            unitPrice: $unitPrice
        );

        $this->recalculateTotal();
    }

    public function submit(): void {
        $this->assertDraft();
        $this->assertHasLines();

        $this->status = OrderStatus::Submitted;
        $this->submittedAt = new \DateTimeImmutable();
    }

    public function cancel(): void {
        if (!$this->status->canBeCancelled()) {
            throw CannotCancelOrder::becauseStatusIs($this->status);
        }

        $this->status = OrderStatus::Cancelled;
    }

    public function equals(Order $other): bool {
        return $this->id->equals($other->id);
    }

    private function assertDraft(): void {
        if (!$this->status->isDraft()) {
            throw InvalidOrderOperation::cannotModifyNonDraft();
        }
    }

    private function assertHasLines(): void {
        if (empty($this->lines)) {
            throw InvalidOrderOperation::cannotSubmitEmpty();
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

## Caractéristiques des Entités

| Caractéristique  | Description                                                    |
| ---------------- | -------------------------------------------------------------- |
| **Identité**     | Identifiant unique qui ne change jamais                        |
| **Continuité**   | Même entité dans le temps malgré les changements d'attributs   |
| **Mutabilité**   | Les attributs peuvent changer via des méthodes de comportement |
| **Cycle de vie** | Créée, modifiée, potentiellement supprimée                     |
| **Égalité**      | Basée sur l'identité, pas les attributs                        |

## Identité des Entités

```php
<?php
// Objet-Valeur pour l'identité
final readonly class OrderId {
    private function __construct(
        private string $value
    ) {
        if (empty($value)) {
            throw new InvalidArgumentException('OrderId ne peut pas être vide');
        }
    }

    public static function generate(): self {
        return new self(Uuid::uuid4()->toString());
    }

    public static function fromString(string $id): self {
        return new self($id);
    }

    public function toString(): string {
        return $this->value;
    }

    public function equals(OrderId $other): bool {
        return $this->value === $other->value;
    }
}
```

## Directives de Conception des Entités

### 1. Encapsuler les Changements d'État

```php
<?php
// MAUVAIS : Exposer l'état interne
class Order {
    public OrderStatus $status;  // Peut être changé de l'extérieur !
    public array $lines;          // Peut être modifié directement !
}

// BON : Changements contrôlés via le comportement
final class Order {
    private OrderStatus $status;
    private array $lines;

    public function submit(): void {  // Méthode de comportement
        $this->status = OrderStatus::Submitted;
    }

    public function addLine(OrderLine $line): void {  // Modification contrôlée
        $this->lines[] = $line;
    }
}
```

### 2. Appliquer les Invariants

```php
<?php
final class Order {
    // Invariant : Une commande doit toujours avoir un total valide
    // Invariant : Une commande ne peut pas être modifiée après soumission

    public function addLine(OrderLine $line): void {
        if ($this->status !== OrderStatus::Draft) {
            throw new DomainException(
                'Impossible d\'ajouter des lignes à une commande non-brouillon'
            );
        }

        $this->lines[] = $line;
        $this->recalculateTotal();  // Maintenir l'invariant
    }
}
```

### 3. Utiliser des Constructeurs Nommés

```php
<?php
final class Customer {
    private function __construct(
        private CustomerId $id,
        private string $name,
        private CustomerType $type
    ) {}

    // Intention claire : enregistrer un nouveau client
    public static function register(
        CustomerId $id,
        string $name,
        string $email
    ): self {
        $customer = new self($id, $name, CustomerType::Regular);
        $customer->recordEvent(new CustomerRegistered($id, $name, $email));
        return $customer;
    }

    // Intention claire : importer depuis un système legacy
    public static function importFromLegacy(
        CustomerId $id,
        array $legacyData
    ): self {
        return new self(
            $id,
            $legacyData['name'],
            CustomerType::fromLegacyCode($legacyData['type_code'])
        );
    }
}
```

## Les Grimoires

- [Manuel PHP - Classes et Objets](https://www.php.net/manual/en/language.oop5.php)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
