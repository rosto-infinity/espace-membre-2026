---
source_course: "php-ddd"
source_lesson: "php-ddd-value-objects"
---

# Objets-Valeur

Un **Objet-Valeur** est un objet de domaine défini entièrement par ses attributs. Deux objets-valeur avec les mêmes attributs sont considérés comme égaux et interchangeables.

## Objets-Valeur vs Entités

```php
<?php
// OBJET-VALEUR : Money - défini par le montant et la devise
$money1 = new Money(100, 'USD');
$money2 = new Money(100, 'USD');
$money1->equals($money2);  // true - même valeur

// ENTITÉ : Customer - définie par l'identité
$customer1 = new Customer(new CustomerId('1'), 'Jean');
$customer2 = new Customer(new CustomerId('2'), 'Jean');
$customer1->equals($customer2);  // false - identité différente
```

## Implémenter des Objets-Valeur

```php
<?php
declare(strict_types=1);

namespace Domain\Shared;

final readonly class Money {
    public function __construct(
        private int $cents,
        private Currency $currency
    ) {
        if ($cents < 0) {
            throw new InvalidArgumentException(
                'L\'argent ne peut pas être négatif'
            );
        }
    }

    public static function USD(int $cents): self {
        return new self($cents, Currency::USD);
    }

    public static function EUR(int $cents): self {
        return new self($cents, Currency::EUR);
    }

    public static function zero(Currency $currency = Currency::USD): self {
        return new self(0, $currency);
    }

    public function add(Money $other): self {
        $this->assertSameCurrency($other);
        return new self(
            $this->cents + $other->cents,
            $this->currency
        );
    }

    public function subtract(Money $other): self {
        $this->assertSameCurrency($other);
        $result = $this->cents - $other->cents;

        if ($result < 0) {
            throw new InvalidArgumentException(
                'La soustraction résulterait en un montant négatif'
            );
        }

        return new self($result, $this->currency);
    }

    public function multiply(float $factor): self {
        return new self(
            (int) round($this->cents * $factor),
            $this->currency
        );
    }

    public function isGreaterThan(Money $other): bool {
        $this->assertSameCurrency($other);
        return $this->cents > $other->cents;
    }

    public function isGreaterThanOrEqual(Money $other): bool {
        $this->assertSameCurrency($other);
        return $this->cents >= $other->cents;
    }

    public function cents(): int {
        return $this->cents;
    }

    public function currency(): Currency {
        return $this->currency;
    }

    public function format(): string {
        $dollars = $this->cents / 100;
        return $this->currency->symbol() . number_format($dollars, 2);
    }

    public function equals(Money $other): bool {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }

    private function assertSameCurrency(Money $other): void {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException(
                $this->currency,
                $other->currency
            );
        }
    }
}
```

## Objets-Valeur Courants

### Adresse Email

```php
<?php
final readonly class EmailAddress {
    private string $value;

    public function __construct(string $email) {
        $email = strtolower(trim($email));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidEmailAddress($email);
        }

        $this->value = $email;
    }

    public function toString(): string {
        return $this->value;
    }

    public function domain(): string {
        return substr($this->value, strpos($this->value, '@') + 1);
    }

    public function equals(EmailAddress $other): bool {
        return $this->value === $other->value;
    }
}
```

### Adresse Postale

```php
<?php
final readonly class Address {
    public function __construct(
        private string $street,
        private string $city,
        private string $state,
        private string $postalCode,
        private Country $country
    ) {
        if (empty($street) || empty($city)) {
            throw new InvalidAddress('La rue et la ville sont obligatoires');
        }
    }

    public function withStreet(string $street): self {
        return new self(
            $street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country
        );
    }

    public function format(): string {
        return implode("\n", [
            $this->street,
            "{$this->city}, {$this->state} {$this->postalCode}",
            $this->country->name()
        ]);
    }

    public function equals(Address $other): bool {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }
}
```

### Plage de Dates

```php
<?php
final readonly class DateRange {
    public function __construct(
        private \DateTimeImmutable $start,
        private \DateTimeImmutable $end
    ) {
        if ($end < $start) {
            throw new InvalidDateRange(
                'La date de fin doit être après la date de début'
            );
        }
    }

    public static function fromStrings(string $start, string $end): self {
        return new self(
            new \DateTimeImmutable($start),
            new \DateTimeImmutable($end)
        );
    }

    public function contains(\DateTimeImmutable $date): bool {
        return $date >= $this->start && $date <= $this->end;
    }

    public function overlaps(DateRange $other): bool {
        return $this->start <= $other->end
            && $this->end >= $other->start;
    }

    public function lengthInDays(): int {
        return $this->start->diff($this->end)->days;
    }
}
```

## Caractéristiques des Objets-Valeur

| Caractéristique         | Description                                      |
| ----------------------- | ------------------------------------------------ |
| **Immuable**            | L'état ne change jamais après création           |
| **Auto-validant**       | Un état invalide est impossible                  |
| **Égalité par valeur**  | Mêmes attributs = objets égaux                   |
| **Sans effets de bord** | Les opérations retournent de nouvelles instances |
| **Remplaçable**         | Peut être échangé avec des objets-valeur égaux   |

## Quand Utiliser des Objets-Valeur

```php
<?php
// MAUVAIS : Obsession des primitives
class Order {
    private string $customerId;  // Juste une chaîne ?
    private float $total;         // Problèmes de précision ?
    private string $currency;     // Quelles devises sont valides ?
    private string $status;       // Quelles valeurs sont autorisées ?
}

// BON : Objets-valeur riches
class Order {
    private CustomerId $customerId;    // Validé, type-safe
    private Money $total;              // Gère la devise correctement
    private OrderStatus $status;       // Enum avec états définis
}
```

Utilisez des objets-valeur quand :

- Un concept a plusieurs attributs liés
- Des règles de validation s'appliquent
- Des opérations ont du sens (additionner de l'argent, comparer des dates)
- Vous voulez éviter l'obsession des primitives

## Les Grimoires

- [Classes Readonly PHP 8.2](https://www.php.net/manual/en/language.oop5.basic.php#language.oop5.basic.class.readonly)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
