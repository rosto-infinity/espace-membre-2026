---
source_course: "php-ddd"
source_lesson: "php-ddd-ddd-best-practices"
---

# Bonnes Pratiques DDD & Pièges Courants

Après avoir appris les patterns, discutons de la sagesse pratique pour une adoption réussie du DDD.

## Bonnes Pratiques

### 1. Commencer par le Domaine, Pas la Base de Données

```php
<?php
// À ÉVITER : Concevoir la base de données d'abord, puis le modèle
// CREATE TABLE orders (...);   -- Base de données d'abord
// class Order extends Model {} -- Entité ORM ensuite

// À FAIRE : Concevoir le modèle de domaine d'abord
final class Order {  // Modèle de domaine riche
    public function submit(): void { ... }
}
// Puis décider de la persistance
```

### 2. Rendre les Concepts Implicites Explicites

```php
<?php
// IMPLICITE : Caché dans les types primitifs
class Order {
    private string $status;   // Quelles valeurs sont valides ?
    private float $discount;  // Pourcentage ? Montant ? Devise ?
}

// EXPLICITE : Concepts nommés et appliqués
class Order {
    private OrderStatus $status;      // Enum avec états valides
    private DiscountPolicy $discount; // Typé, avec règles
}

enum OrderStatus: string {
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Confirmed = 'confirmed';

    public function canBeCancelled(): bool {
        return $this !== self::Confirmed;
    }
}
```

### 3. Garder les Agrégats Petits

```php
<?php
// TROP GROS : Tout dans un agrégat
class Order {
    private Customer $customer;   // Devrait être ID seulement
    private array $payments;      // Agrégat séparé
    private array $shipments;     // Agrégat séparé
    private array $returns;       // Agrégat séparé
    private array $reviews;       // Contexte différent
}

// BONNE TAILLE : Focalisé sur les invariants essentiels
class Order {
    private CustomerId $customerId;  // Référence seulement
    private array $lines;            // Partie des invariants de commande
    private Money $total;            // Maintenu par l'agrégat
}
```

### 4. Utiliser les Événements de Domaine pour la Communication Inter-Agrégats

```php
<?php
// MAUVAIS : Couplage direct entre agrégats
class OrderService {
    public function submit(Order $order): void {
        $order->submit();

        // Manipulation directe d'autres agrégats
        $this->inventory->reduce(...);
        $this->customer->addPoints(...);
        $this->analytics->track(...);
    }
}

// BON : Les événements découplent les agrégats
class OrderService {
    public function submit(Order $order): void {
        $order->submit();
        $this->orders->save($order);

        // Événements gérés par des gestionnaires séparés
        $this->events->dispatch($order->pullDomainEvents());
    }
}

// Les gestionnaires traitent les effets de bord
class ReduceInventoryOnOrderSubmitted {
    public function __invoke(OrderSubmitted $event): void {
        // Gérer dans une transaction séparée
    }
}
```

### 5. Protéger les Invariants dans les Agrégats

```php
<?php
class Order {
    // Invariant : Le total doit toujours être correct
    // Invariant : Impossible de modifier après soumission

    public function addLine(OrderLine $line): void {
        $this->assertModifiable();  // Vérifier l'invariant
        $this->lines[] = $line;
        $this->recalculateTotal();  // Maintenir l'invariant
    }

    private function recalculateTotal(): void {
        // Toujours synchronisé
        $this->total = array_reduce(
            $this->lines,
            fn($sum, $line) => $sum->add($line->subtotal()),
            Money::zero()
        );
    }
}
```

## Pièges Courants

### 1. Modèle de Domaine Anémique

```php
<?php
// ANÉMIQUE : Pas de comportement, juste des données
class Order {
    public string $status;
    public array $lines;
    public float $total;
}

class OrderService {
    public function submit(Order $order): void {
        if (empty($order->lines)) {
            throw new Exception('...');
        }
        $order->status = 'submitted';  // Logique hors de l'entité
    }
}

// RICHE : Comportement encapsulé
class Order {
    public function submit(): void {
        $this->assertHasLines();
        $this->status = OrderStatus::Submitted;
    }
}
```

### 2. Sur-Ingénierie

```php
<?php
// TROP COMPLEXE : DDD pour du CRUD simple
class UserSettings {
    // Est-ce que ça nécessite vraiment du DDD ?
    // Parfois un modèle simple est suffisant
}

// Appliquez le DDD là où la complexité existe
// Utilisez des patterns plus simples pour les fonctionnalités simples
```

### 3. Ignorer les Contextes Délimités

```php
<?php
// MAUVAIS : Un seul modèle pour tout
class Product {
    // Préoccupations catalogue
    private string $description;
    private array $images;

    // Préoccupations stock
    private int $stockLevel;
    private string $warehouseLocation;

    // Préoccupations tarification
    private Money $basePrice;
    private array $discountRules;
}

// BON : Modèles différents par contexte
namespace Catalog { class Product { /* infos d'affichage */ } }
namespace Inventory { class StockItem { /* infos stock */ } }
namespace Pricing { class PricedProduct { /* tarification */ } }
```

## Quand NE PAS Utiliser le DDD

- Applications CRUD simples
- Prototypes et MVPs
- Domaines bien compris et stables
- Petites équipes avec des besoins simples
- Projets de courte durée

## Checklist Récapitulative

✅ Experts métier impliqués dans la modélisation
✅ Langage omniprésent documenté et appliqué
✅ Contextes délimités identifiés
✅ Les agrégats maintiennent la cohérence des invariants
✅ Les référentiels abstraient la persistance
✅ Événements de domaine pour la communication inter-agrégats
✅ Services d'application orchestrent les cas d'usage
✅ L'infrastructure dépend du domaine, pas l'inverse

## Les Grimoires

- [Domain-Driven Design Quickly](https://www.infoq.com/minibooks/domain-driven-design-quickly/)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
