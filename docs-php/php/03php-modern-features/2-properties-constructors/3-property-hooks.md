---
source_course: "php-modern-features"
source_lesson: "php-modern-features-property-hooks"
---

# Les Hooks de Propriété (PHP 8.4)

Les hooks de propriété permettent de définir un comportement **get** et **set** personnalisé directement sur les propriétés, similaire aux getters/setters d'autres langages.

## La Syntaxe de Base

```php
<?php
class User {
    public string $name {
        get => strtoupper($this->name);
        set => trim($value);
    }
}

$user = new User();
$user->name = '  jean dupont  ';  // Appelle le hook set
echo $user->name;                 // Appelle le hook get : "JEAN DUPONT"
```

## Les Propriétés Virtuelles (Calculées)

Propriétés sans stockage physique :

```php
<?php
class Rectangle {
    public function __construct(
        public float $width,
        public float $height
    ) {}

    // Propriété virtuelle - calculée à l'accès
    public float $area {
        get => $this->width * $this->height;
    }

    // Propriété virtuelle en lecture seule
    public float $perimeter {
        get => 2 * ($this->width + $this->height);
    }
}

$rect = new Rectangle(10, 5);
echo $rect->area;       // 50
echo $rect->perimeter;  // 30
```

## La Visibilité Asymétrique (PHP 8.4)

Visibilités différentes pour get et set :

```php
<?php
class BankAccount {
    // Lecture publique, écriture privée
    public private(set) float $balance = 0;

    public function deposit(float $amount): void {
        $this->balance += $amount;  // OK - écriture interne
    }
}

$account = new BankAccount();
echo $account->balance;     // OK - lecture publique
$account->balance = 100;    // Erreur ! Set privé
$account->deposit(100);     // OK - modification interne
```

## Validation dans le Hook Set

```php
<?php
class Product {
    public float $price {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException('Le prix ne peut pas être négatif');
            }
            $this->price = $value;
        }
    }

    public int $quantity {
        set(int $value) {
            $this->quantity = max(0, $value);  // Toujours >= 0
        }
    }
}
```

## Syntaxe Complète des Hooks

```php
<?php
class Temperature {
    private float $celsius;

    public float $fahrenheit {
        get => $this->celsius * 9/5 + 32;
        set {
            $this->celsius = ($value - 32) * 5/9;
        }
    }

    public function __construct(float $celsius) {
        $this->celsius = $celsius;
    }
}

$temp = new Temperature(0);
echo $temp->fahrenheit;     // 32
$temp->fahrenheit = 212;    // Définit celsius à 100
```

## Propriétés d'Interface avec des Hooks

```php
<?php
interface HasFullName {
    public string $fullName { get; }
}

class Person implements HasFullName {
    public function __construct(
        public string $firstName,
        public string $lastName
    ) {}

    public string $fullName {
        get => "$this->firstName $this->lastName";
    }
}
```

## Exemple Concret

**Une entité Commande utilisant les hooks PHP 8.4**

```php
<?php
declare(strict_types=1);

// Entité avec hooks pour validation et propriétés calculées
class Order {
    private array $items = [];

    // Visibilité asymétrique
    public private(set) string $status = 'en_attente';

    // Propriété validée
    public string $customerEmail {
        set {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email invalide');
            }
            $this->customerEmail = strtolower($value);
        }
    }

    // Propriété virtuelle calculée
    public float $total {
        get => array_sum(array_map(
            fn($item) => $item['price'] * $item['quantity'],
            $this->items
        ));
    }

    // Propriété virtuelle avec formatage
    public string $formattedTotal {
        get => number_format($this->total, 2) . ' €';
    }

    public function addItem(string $name, float $price, int $quantity): void {
        $this->items[] = compact('name', 'price', 'quantity');
    }

    public function markShipped(): void {
        $this->status = 'expédiée';
    }
}

$order = new Order();
$order->customerEmail = 'JEAN@EXAMPLE.COM';  // Stocké en minuscules
$order->addItem('Widget', 9.99, 2);
$order->addItem('Gadget', 19.99, 1);

echo $order->formattedTotal;  // 39.97 €
echo $order->status;          // en_attente
?>
```

## Les Grimoires

- [RFC Property Hooks](https://wiki.php.net/rfc/property-hooks)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
