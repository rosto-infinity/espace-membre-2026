---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-encapsulation"
---

# L'Encapsulation & le Masquage de l'Information

L'encapsulation regroupe des données avec les méthodes qui les manipulent et **restreint l'accès direct à l'état interne** d'un objet.

## Les Modificateurs de Visibilité

```php
<?php
class BankAccount {
    private float $balance = 0;       // Seulement cette classe
    protected string $accountType;    // Cette classe + ses enfants
    public string $accountNumber;     // Tout le monde

    public function __construct(string $number, string $type = 'checking') {
        $this->accountNumber = $number;
        $this->accountType = $type;
    }
}
```

## Pourquoi l'Encapsulation est Importante

```php
<?php
// MAUVAIS : Les propriétés publiques permettent des états invalides
class BadAccount {
    public float $balance = 0;
}

$account = new BadAccount();
$account->balance = -1000;  // Solde négatif invalide !

// BON : Accès contrôlé via des méthodes
class GoodAccount {
    private float $balance = 0;

    public function deposit(float $amount): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif');
        }
        $this->balance += $amount;
    }

    public function withdraw(float $amount): void {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Le montant doit être positif');
        }
        if ($amount > $this->balance) {
            throw new InsufficientFundsException('Fonds insuffisants');
        }
        $this->balance -= $amount;
    }

    public function getBalance(): float {
        return $this->balance;
    }
}
```

## La Visibilité Asymétrique (PHP 8.4)

```php
<?php
class User {
    // Lecture publique, écriture privée
    public private(set) string $id;

    // Lecture publique, écriture protégée
    public protected(set) string $name;

    public function __construct(string $id, string $name) {
        $this->id = $id;
        $this->name = $name;
    }
}

$user = new User('123', 'Jean');
echo $user->id;      // OK - lecture publique
$user->id = '456';   // Erreur ! private(set)
```

## Getters/Setters VS Hooks de Propriété

```php
<?php
// Getters/setters traditionnels
class TraditionalUser {
    private string $email;

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Email invalide');
        }
        $this->email = strtolower($email);
    }
}

// PHP 8.4 — Hooks de Propriété
class ModernUser {
    public string $email {
        get => $this->email;
        set {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email invalide');
            }
            $this->email = strtolower($value);
        }
    }
}
```

## Exemple Concret

**Un panier d'achat bien encapsulé**

```php
<?php
declare(strict_types=1);

// Panier d'achat bien encapsulé
class ShoppingCart {
    private array $items = [];

    public function addItem(string $productId, int $quantity, float $price): void {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La quantité doit être positive');
        }

        if (isset($this->items[$productId])) {
            $this->items[$productId]['quantity'] += $quantity;
        } else {
            $this->items[$productId] = [
                'quantity' => $quantity,
                'price' => $price,
            ];
        }
    }

    public function removeItem(string $productId): void {
        unset($this->items[$productId]);
    }

    public function getTotal(): float {
        return array_reduce(
            $this->items,
            fn($total, $item) => $total + ($item['price'] * $item['quantity']),
            0.0
        );
    }

    public function getItemCount(): int {
        return array_sum(array_column($this->items, 'quantity'));
    }

    // Retourne une copie pour éviter les modifications externes
    public function getItems(): array {
        return $this->items;
    }
}
?>
```

## Les Grimoires

- [Visibilité des Propriétés (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.visibility.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
