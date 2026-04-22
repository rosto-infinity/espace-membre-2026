---
source_course: "php-ddd"
source_lesson: "php-ddd-ubiquitous-language"
---

# Le Langage Omniprésent

Le **Langage Omniprésent** est un vocabulaire partagé utilisé par toute l'équipe — développeurs, experts métier, chefs de produit et parties prenantes. Il forme le fondement d'un DDD efficace.

## Pourquoi le Langage Est Important

Les mauvaises communications sont coûteuses :

```
Expert métier : « Le compte client est suspendu. »
Le développeur pense : Le client ne peut pas se connecter
Le métier veut dire : Le client peut se connecter mais ne peut pas faire d'achats

→ Mauvaise fonctionnalité → Temps perdu → Utilisateurs frustrés
```

## Construire le Langage

### 1. Découverte par la Conversation

```
Développeur : « Donc quand un utilisateur crée une commande... »
Expert : « En fait, on les appelle "clients", et ils "passent" des commandes,
          ils ne les "créent" pas. »
Développeur : « Compris ! Et que se passe-t-il quand ils passent une commande ? »
Expert : « La commande est "en attente" jusqu'à ce qu'on la "confirme". »
```

### 2. Documenter le Langage

Créer un glossaire que tout le monde consulte :

```markdown
## Glossaire de Gestion des Commandes

**Client** - Une personne ou organisation qui achète des produits.
Pas "utilisateur" ou "client" (anglais).

**Commande** - Une demande d'un client pour acheter un ou plusieurs produits.

- Peut être : En attente, Confirmée, Expédiée, Livrée, Annulée

**Passer une Commande** - L'acte d'un client soumettant une commande.
Pas "créer" ou "soumettre".

**Confirmer une Commande** - Quand le stock est réservé et le paiement
est autorisé. Pas "approuver" ou "traiter".

**Ligne de Commande** - Une entrée produit unique dans une commande avec quantité.
Pas "article de commande" ou "ligne produit".
```

### 3. Appliquer dans le Code

```php
<?php
// Violations du langage - termes confus et incorrects
class User {  // Devrait être Customer
    public function createOrder() {}  // Devrait être placeOrder
}

class OrderItem {}  // Devrait être LineItem

function processOrder() {}  // Que signifie "traiter" ?

// Code conforme au langage
final class Customer {
    public function placeOrder(Cart $cart): Order {
        return Order::place(
            customerId: $this->id,
            lineItems: $cart->toLineItems()
        );
    }
}

final class LineItem {
    public function __construct(
        public readonly ProductId $productId,
        public readonly Quantity $quantity,
        public readonly Money $unitPrice
    ) {}
}
```

## Le Langage dans Différents Contextes

Le même mot peut signifier différentes choses dans différents contextes :

```php
<?php
// Dans le contexte Ventes
namespace Sales\Domain;

final class Customer {
    private CustomerId $id;
    private CustomerName $name;
    private CreditLimit $creditLimit;
    private PaymentTerms $paymentTerms;

    public function canPlaceOrder(Money $orderTotal): bool {
        return $this->creditLimit->covers($orderTotal);
    }
}

// Dans le contexte Livraison - même "Client" mais données différentes
namespace Shipping\Domain;

final class Customer {
    private CustomerId $id;
    private ShippingAddress $address;
    private DeliveryPreferences $preferences;

    public function preferredDeliverySlot(): TimeSlot {
        return $this->preferences->preferredSlot();
    }
}
```

## Patterns pour Maintenir le Langage

### Utiliser l'Analyse Statique

```php
<?php
namespace App\PHPStan;

use PHPStan\Rules\Rule;

class ForbiddenTermsRule implements Rule {
    private const FORBIDDEN_TERMS = [
        'User' => 'Utilisez "Customer" à la place',
        'createOrder' => 'Utilisez "placeOrder" à la place',
        'OrderItem' => 'Utilisez "LineItem" à la place',
    ];

    // Implémentation...
}
```

### Revues de Code

Inclure des vérifications linguistiques dans votre processus de revue :

- Cette PR utilise-t-elle les termes de notre glossaire ?
- Y a-t-il de nouveaux concepts de domaine à ajouter ?
- Un expert métier comprendrait-il ce code ?

### Documentation Vivante

```php
<?php
/**
 * Représente la demande d'un Client d'acheter des produits.
 *
 * Règles Métier :
 * - Une Commande doit avoir au moins une Ligne de Commande
 * - Une Commande ne peut être annulée qu'avant expédition
 * - Les remises s'appliquent au niveau de la Commande, pas de la Ligne
 *
 * @see docs/glossary.md#commande
 */
final class Order {
    // ...
}
```

## Anti-Patterns Courants du Langage

### 1. Fuite Technique

```php
<?php
// MAUVAIS : Termes techniques dans le code de domaine
class OrderEntity {  // "Entity" est technique
    private int $id;  // Devrait être un objet-valeur OrderId

    public function persist(): void {}  // Le domaine ne persiste pas !
}

// BON : Langage de domaine pur
final class Order {
    private OrderId $id;

    public function confirm(): void {
        // Comportement de domaine seulement
    }
}
```

### 2. Noms Génériques

```php
<?php
// MAUVAIS : Noms génériques et sans signification
class Manager {}
class Handler {}
class Processor {}
class Helper {}

// BON : Termes de domaine spécifiques
class OrderFulfillmentService {}
class PricingCalculator {}
class InventoryAllocator {}
```

### 3. Termes Abrégés

```php
<?php
// MAUVAIS : Les abréviations nuisent à la compréhension
$custOrdLn = new CustOrdLn();
$po = $repo->getPO($id);

// BON : Termes complets et clairs
$lineItem = new LineItem();
$purchaseOrder = $repository->findPurchaseOrder($id);
```

## Évolution du Langage

Le langage omniprésent n'est pas statique — il évolue :

1. **De nouveaux concepts émergent** à mesure que le métier grandit
2. **Les termes se précisent** avec une meilleure compréhension
3. **Les anciens termes disparaissent** quand le métier change

```php
<?php
// Version 1 : Statuts simples
enum OrderStatus: string {
    case Pending = 'pending';
    case Complete = 'complete';
}

// Version 2 : Le métier a appris qu'il faut plus de nuance
enum OrderStatus: string {
    case Draft = 'draft';      // Nouveau : les commandes peuvent être sauvegardées
    case Placed = 'placed';    // Renommé depuis Pending
    case Confirmed = 'confirmed'; // Nouveau : étape de confirmation séparée
    case Shipped = 'shipped';  // Nouveau : suivi de l'expédition
    case Delivered = 'delivered'; // Renommé depuis Complete
    case Cancelled = 'cancelled';
}
```

## Les Grimoires

- [Développer le Langage Omniprésent](https://martinfowler.com/bliki/UbiquitousLanguage.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
