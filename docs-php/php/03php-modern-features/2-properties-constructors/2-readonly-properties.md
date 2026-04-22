---
source_course: "php-modern-features"
source_lesson: "php-modern-features-readonly-properties"
---

# Les Propriétés Readonly et Classes Readonly

Les propriétés readonly ne peuvent être initialisées qu'**une seule fois**, puis deviennent immuables. Parfait pour les objets de valeur (Value Objects) et les DTOs.

## Les Propriétés Readonly (PHP 8.1+)

```php
<?php
class User {
    public readonly string $id;
    public readonly string $name;

    public function __construct(string $id, string $name) {
        $this->id = $id;      // OK - première assignation
        $this->name = $name;  // OK - première assignation
    }

    public function setName(string $name): void {
        $this->name = $name;  // Erreur ! Déjà initialisée
    }
}

$user = new User('1', 'Jean');
echo $user->name;       // Jean
$user->name = 'Marie';  // Erreur ! Propriété readonly ne peut pas être modifiée
```

## Les Règles pour les Propriétés Readonly

1. Doit avoir une déclaration de type
2. Ne peut être assignée qu'une seule fois
3. Ne peut être assignée que depuis la classe déclarante
4. Ne peut pas avoir de valeur par défaut (sauf en promotion de constructeur)

```php
<?php
class Example {
    // Erreur ! readonly doit avoir un type
    public readonly $invalid;

    // OK avec un type
    public readonly string $valid;

    // Erreur ! Ne peut pas avoir de valeur par défaut
    public readonly string $withDefault = 'non';

    // OK en promotion de constructeur
    public function __construct(
        public readonly string $promoted = 'default'
    ) {}
}
```

## Les Classes Readonly (PHP 8.2+)

Toutes les propriétés sont implicitement readonly :

```php
<?php
readonly class Point {
    public function __construct(
        public float $x,
        public float $y
    ) {}
}

// Équivalent à :
class Point {
    public function __construct(
        public readonly float $x,
        public readonly float $y
    ) {}
}

$point = new Point(1.0, 2.0);
$point->x = 3.0;  // Erreur ! Impossible de modifier
```

## Règles des Classes Readonly

```php
<?php
readonly class Immutable {
    // Toutes les propriétés doivent être typées
    public string $name;   // OK
    public $untyped;       // Erreur !

    // Pas de propriétés statiques
    public static string $static;  // Erreur !
}
```

## Cloner des Objets Readonly (PHP 8.3+)

```php
<?php
readonly class User {
    public function __construct(
        public string $name,
        public string $email
    ) {}

    public function withEmail(string $email): self {
        // PHP 8.3+ : Peut modifier lors du clonage
        return clone $this with {
            $this->email = $email
        };
    }
}

// Alternative : Méthode manuelle de clonage
readonly class User {
    public function __construct(
        public string $name,
        public string $email
    ) {}

    public function withEmail(string $email): self {
        return new self($this->name, $email);
    }
}
```

## Exemple Concret

**Un objet de valeur "Argent" immuable (Value Object)**

```php
<?php
declare(strict_types=1);

// Pattern Value Object avec classe readonly
readonly class Money {
    public function __construct(
        public int $amount,      // En centimes
        public string $currency
    ) {
        if ($amount < 0) {
            throw new InvalidArgumentException('Le montant ne peut pas être négatif');
        }
    }

    public function add(Money $other): self {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Devises incompatibles');
        }
        return new self($this->amount + $other->amount, $this->currency);
    }

    public function multiply(float $factor): self {
        return new self((int) round($this->amount * $factor), $this->currency);
    }

    public function format(): string {
        return sprintf('%s %.2f', $this->currency, $this->amount / 100);
    }
}

$prix = new Money(1999, 'EUR');    // 19,99 €
$tva = $prix->multiply(0.2);       // 4,00 € (nouvel objet)
$total = $prix->add($tva);         // 23,99 € (nouvel objet)

echo $total->format();  // EUR 23.99
?>
```

## Les Grimoires

- [Propriétés Readonly (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
