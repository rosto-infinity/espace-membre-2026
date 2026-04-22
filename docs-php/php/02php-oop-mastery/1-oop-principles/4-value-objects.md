---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-value-objects"
---

# Le Pattern Value Object (Objet de Valeur)

Les Value Objects sont de petits objets **immuables** qui représentent un aspect descriptif du domaine. Ils sont définis par leurs attributs, et non par une identité.

## Entité VS Value Object

| Entité                | Value Object              |
| --------------------- | ------------------------- |
| A une identité unique | Identifié par ses valeurs |
| Mutable               | Immuable                  |
| Même ID = même chose  | Mêmes valeurs = égaux     |
| Exemple : User, Order | Exemple : Money, Email    |

## Implémenter un Value Object

```php
<?php
readonly class Email
{
    public function __construct(
        public string $address
    ) {
        if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Adresse email invalide');
        }
    }

    public function getDomain(): string
    {
        return substr($this->address, strpos($this->address, '@') + 1);
    }

    public function equals(Email $other): bool
    {
        return strtolower($this->address) === strtolower($other->address);
    }

    public function __toString(): string
    {
        return $this->address;
    }
}
```

## Le Value Object Money (Argent)

```php
<?php
readonly class Money
{
    public function __construct(
        public int $amount,      // En plus petite unité (centimes)
        public string $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être négatif');
        }

        if (!in_array($currency, ['USD', 'EUR', 'GBP'], true)) {
            throw new InvalidArgumentException('Devise non supportée');
        }
    }

    public function add(Money $other): self
    {
        $this->assertSameCurrency($other);
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->assertSameCurrency($other);

        if ($other->amount > $this->amount) {
            throw new InsufficientFundsException();
        }

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(float $factor): self
    {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount
            && $this->currency === $other->currency;
    }

    public function format(): string
    {
        $symbols = ['USD' => '$', 'EUR' => '€', 'GBP' => '£'];
        return $symbols[$this->currency] . number_format($this->amount / 100, 2);
    }

    private function assertSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new CurrencyMismatchException();
        }
    }
}
```

## Le Value Object DateRange (Plage de Dates)

```php
<?php
readonly class DateRange
{
    public function __construct(
        public DateTimeImmutable $start,
        public DateTimeImmutable $end
    ) {
        if ($end < $start) {
            throw new InvalidArgumentException('La date de fin doit être après la date de début');
        }
    }

    public function contains(DateTimeImmutable $date): bool
    {
        return $date >= $this->start && $date <= $this->end;
    }

    public function overlaps(DateRange $other): bool
    {
        return $this->start <= $other->end && $this->end >= $other->start;
    }

    public function getDays(): int
    {
        return $this->start->diff($this->end)->days;
    }

    public function extend(DateInterval $interval): self
    {
        return new self($this->start, $this->end->add($interval));
    }
}
```

## Quand Utiliser des Value Objects ?

- **Adresses email** : Validation, formatage
- **Argent/Devise** : Arithmétique en toute sécurité
- **Coordonnées GPS** : Lat/long avec calculs
- **Adresses postales** : Formatage, validation
- **Mesures** : Unités, conversions

## Les Grimoires

- [Value Objects (Martin Fowler)](https://martinfowler.com/bliki/ValueObject.html)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
