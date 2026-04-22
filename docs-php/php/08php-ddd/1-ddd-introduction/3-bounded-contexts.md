---
source_course: "php-ddd"
source_lesson: "php-ddd-bounded-contexts"
---

# Contextes Délimités

Un **Contexte Délimité** (Bounded Context) est un pattern central en DDD qui définit des frontières claires à l'intérieur desquelles un modèle de domaine particulier s'applique. C'est là où le langage omniprésent a une signification spécifique et sans ambiguïté.

## Pourquoi des Contextes Délimités ?

Les grands systèmes ne peuvent pas avoir un unique modèle unifié :

```
"Client" dans différents contextes :

┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│    VENTES       │  │   LIVRAISON     │  │   FACTURATION   │
├─────────────────┤  ├─────────────────┤  ├─────────────────┤
│ Client :        │  │ Client :        │  │ Client :        │
│ - Nom           │  │ - Adresse       │  │ - Info paiement │
│ - Limite crédit │  │ - Téléphone     │  │ - N° fiscal     │
│ - Commercial    │  │ - Prél. livr.   │  │ - Adr. facturat.│
│ - Niveau remise │  │                 │  │ - Conditions    │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

Chaque contexte a son propre modèle de Client avec des attributs et comportements différents.

## Identifier les Contextes Délimités

Cherchez :

1. **Des significations différentes pour le même terme**
2. **Des équipes ou départements différents**
3. **Des processus métier différents**
4. **Des exigences de données différentes**

```php
<?php
// Contexte Ventes - se préoccupe du comportement d'achat
namespace Sales;

final class Customer {
    private CustomerId $id;
    private string $name;
    private Money $creditLimit;
    private DiscountTier $discountTier;
    private ?SalesRepId $assignedRep;

    public function canPurchase(Money $amount): bool {
        return $this->creditLimit->isGreaterThanOrEqual($amount);
    }

    public function getDiscountPercentage(): Percentage {
        return $this->discountTier->asPercentage();
    }
}

// Contexte Livraison - se préoccupe de la livraison
namespace Shipping;

final class Recipient {  // Nom différent, même personne réelle
    private RecipientId $id;
    private Address $shippingAddress;
    private PhoneNumber $contactPhone;
    private DeliveryInstructions $instructions;

    public function requiresSignature(): bool {
        return $this->instructions->requiresSignature();
    }
}
```

## Cartographie des Contextes

Les Contextes Délimités doivent interagir. Les Cartes de Contexte définissent ces relations :

### Partenariat

Deux équipes réussissent ou échouent ensemble :

```php
<?php
// Les deux contextes évoluent ensemble
namespace Sales;
use Shipping\ShipmentScheduler;

class OrderService {
    public function __construct(
        private ShipmentScheduler $shipping  // Dépendance directe acceptée
    ) {}
}
```

### Client-Fournisseur

Le contexte amont (fournisseur) fournit ce dont le contexte aval (client) a besoin :

```php
<?php
// Fournisseur : le contexte Stock fournit les infos de disponibilité
namespace Inventory;

interface StockQueryService {
    public function getAvailableStock(ProductId $productId): Quantity;
    public function reserveStock(ProductId $productId, Quantity $qty): Reservation;
}

// Client : le contexte Ventes utilise le stock
namespace Sales;

class OrderService {
    public function __construct(
        private \Inventory\StockQueryService $inventory
    ) {}
}
```

### Couche Anti-Corruption (ACL)

Protégez votre domaine des modèles externes/legacy :

```php
<?php
namespace Sales\Infrastructure\Acl;

use Sales\Domain\Customer;
use Sales\Domain\CustomerId;
use LegacyCRM\ClientRecord;  // Système externe

class LegacyCrmCustomerAdapter implements CustomerRepository {
    public function __construct(
        private LegacyCRM\Database $legacyDb
    ) {}

    public function findById(CustomerId $id): ?Customer {
        $record = $this->legacyDb->fetchClient($id->toString());

        if ($record === null) {
            return null;
        }

        return $this->translateToDomain($record);
    }

    private function translateToDomain(ClientRecord $record): Customer {
        return new Customer(
            id: new CustomerId($record->CLIENT_ID),
            name: $record->CLIENT_NAME ?? 'Inconnu',
            creditLimit: Money::fromCents(
                (int)($record->CREDIT_LIM * 100)
            ),
            discountTier: $this->mapDiscountCode($record->DISC_CODE)
        );
    }

    private function mapDiscountCode(?string $code): DiscountTier {
        return match($code) {
            'A' => DiscountTier::Premium,
            'B' => DiscountTier::Standard,
            default => DiscountTier::None,
        };
    }
}
```

### Langage Publié

Utiliser un format partagé et documenté pour l'intégration :

```php
<?php
namespace Contracts;

final class OrderPlacedEvent {
    public const SCHEMA = [
        'type' => 'object',
        'properties' => [
            'orderId' => ['type' => 'string', 'format' => 'uuid'],
            'customerId' => ['type' => 'string', 'format' => 'uuid'],
            'totalAmount' => [
                'type' => 'object',
                'properties' => [
                    'amount' => ['type' => 'integer'],
                    'currency' => ['type' => 'string']
                ]
            ],
            'occurredAt' => ['type' => 'string', 'format' => 'date-time']
        ],
        'required' => ['orderId', 'customerId', 'totalAmount', 'occurredAt']
    ];
}
```

## Structure de Projet PHP

```
src/
├── Sales/                      # Contexte Délimité
│   ├── Domain/
│   │   ├── Customer.php
│   │   ├── Order.php
│   │   └── OrderRepository.php
│   ├── Application/
│   │   └── PlaceOrderService.php
│   └── Infrastructure/
│       ├── Persistence/
│       └── Acl/
│
├── Shipping/                   # Contexte Délimité
│   ├── Domain/
│   │   ├── Recipient.php
│   │   └── Shipment.php
│   └── ...
│
├── Billing/                    # Contexte Délimité
│   └── ...
│
└── SharedKernel/               # Code partagé entre contextes
    ├── Domain/
    │   ├── Money.php
    │   └── EventInterface.php
    └── Infrastructure/
        └── EventBus.php
```

## Noyau Partagé (Shared Kernel)

Code partagé entre contextes — utilisez-le avec parcimonie :

```php
<?php
namespace SharedKernel\Domain;

// Partagé à travers tous les contextes
final readonly class Money {
    public function __construct(
        private int $cents,
        private Currency $currency
    ) {}

    public static function USD(int $cents): self {
        return new self($cents, Currency::USD);
    }

    public function add(Money $other): self {
        $this->ensureSameCurrency($other);
        return new self(
            $this->cents + $other->cents,
            $this->currency
        );
    }
}
```

⚠️ Le Noyau Partagé crée du couplage. Gardez-le minimal et stable.

## Les Grimoires

- [Bounded Context Expliqué](https://martinfowler.com/bliki/BoundedContext.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
