---
source_course: "php-testing"
source_lesson: "php-testing-tdd-cycle"
---

# Le Cycle TDD

Le Développement Piloté par les Tests (TDD) suit un **cycle simple : Rouge → Vert → Refactoring**.

## Le Cycle

```
┌─────────────────────────────────────────────────────┐
│                                                     │
│    🔴 ROUGE            🟢 VERT           🔵 REFACTOR  │
│    Écrire test   →   Faire passer  →   Nettoyer    │
│    échouant          le test                        │
│         ↑                                    │      │
│         └────────────────────────────────────┘      │
│                                                     │
└─────────────────────────────────────────────────────┘
```

## 1. ROUGE : Écrire un Test Échouant

```php
<?php
// Commencer par ce que vous VOULEZ que le code fasse
class ShoppingCartTest extends TestCase
{
    public function testNewCartIsEmpty(): void
    {
        $cart = new ShoppingCart();

        $this->assertEquals(0, $cart->getItemCount());
        $this->assertEquals(0.0, $cart->getTotal());
    }
}

// Exécuter le test : ÉCHEC (la classe ShoppingCart n'existe pas)
```

## 2. VERT : Faire Passer (Code Minimum)

```php
<?php
class ShoppingCart
{
    public function getItemCount(): int
    {
        return 0;
    }

    public function getTotal(): float
    {
        return 0.0;
    }
}

// Exécuter le test : SUCCÈS
```

## 3. REFACTOR : Améliorer Sans Casser

```php
<?php
class ShoppingCart
{
    private array $items = [];

    public function getItemCount(): int
    {
        return count($this->items);
    }

    public function getTotal(): float
    {
        return array_sum(array_column($this->items, 'subtotal'));
    }
}

// Exécuter le test : Toujours SUCCÈS
```

## Continuer le Cycle

```php
<?php
// Nouveau test (ROUGE)
public function testAddItem(): void
{
    $cart = new ShoppingCart();

    $cart->addItem(new Product('Widget', 10.00), quantity: 2);

    $this->assertEquals(1, $cart->getItemCount());
    $this->assertEquals(20.00, $cart->getTotal());
}

// Implémenter (VERT)
public function addItem(Product $product, int $quantity): void
{
    $this->items[] = [
        'product' => $product,
        'quantity' => $quantity,
        'subtotal' => $product->price * $quantity,
    ];
}

// Refactoring si nécessaire...
```

## Avantages du TDD

- **Conception** : Les tests guident une conception propre et modulaire
- **Documentation** : Les tests expliquent le comportement attendu
- **Confiance** : Refactoriser sans crainte
- **Concentration** : Travailler sur une chose à la fois
- **Couverture** : Haute couverture de tests par défaut

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
