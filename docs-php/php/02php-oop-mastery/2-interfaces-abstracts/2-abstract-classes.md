---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-abstract-classes"
---

# Les Classes Abstraites

Les classes abstraites fournissent une **implémentation partielle** tout en obligeant les sous-classes à implémenter des méthodes spécifiques.

## Classe Abstraite de Base

```php
<?php
abstract class Shape {
    public function __construct(
        protected string $color
    ) {}

    // Méthode abstraite - DOIT être implémentée
    abstract public function getArea(): float;

    // Méthode concrète - héritée telle quelle
    public function getColor(): string {
        return $this->color;
    }

    // Méthode concrète utilisant la méthode abstraite
    public function describe(): string {
        return "Une forme {$this->color} d'aire " . $this->getArea();
    }
}

class Rectangle extends Shape {
    public function __construct(
        string $color,
        private float $width,
        private float $height
    ) {
        parent::__construct($color);
    }

    public function getArea(): float {
        return $this->width * $this->height;
    }
}

class Circle extends Shape {
    public function __construct(
        string $color,
        private float $radius
    ) {
        parent::__construct($color);
    }

    public function getArea(): float {
        return pi() * $this->radius ** 2;
    }
}
```

## Le Pattern Template Method (Méthode Modèle)

```php
<?php
abstract class DataExporter {
    // Méthode modèle - définit l'algorithme
    final public function export(array $data): string {
        $this->validate($data);
        $formatted = $this->format($data);
        return $this->output($formatted);
    }

    // Étape concrète
    protected function validate(array $data): void {
        if (empty($data)) {
            throw new InvalidArgumentException('Les données ne peuvent pas être vides');
        }
    }

    // Étapes abstraites - les sous-classes DOIVENT les implémenter
    abstract protected function format(array $data): string;
    abstract protected function output(string $content): string;
}

class CsvExporter extends DataExporter {
    protected function format(array $data): string {
        $lines = [];
        foreach ($data as $row) {
            $lines[] = implode(',', $row);
        }
        return implode("\n", $lines);
    }

    protected function output(string $content): string {
        return $content;  // Retourner tel quel
    }
}

class JsonExporter extends DataExporter {
    protected function format(array $data): string {
        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function output(string $content): string {
        return $content;
    }
}
```

## Abstract VS Interface

| Fonctionnalité | Interface                | Classe Abstraite             |
| -------------- | ------------------------ | ---------------------------- |
| Méthodes       | Seulement les signatures | Abstraites et concrètes      |
| Propriétés     | Constantes seulement     | N'importe quelles propriétés |
| Multiple       | Oui                      | Non (héritage simple)        |
| Constructeur   | Non                      | Oui                          |
| Visibilité     | Publique uniquement      | Toute visibilité             |
| Utiliser quand | Définir un contrat       | Partager une implémentation  |

## Quand Utiliser les Classes Abstraites ?

```php
<?php
// Utiliser une classe abstraite quand :
// 1. Vous avez une implémentation partagée
// 2. Vous avez besoin de membres protégés
// 3. Vous voulez le pattern Template Method

abstract class Repository {
    public function __construct(
        protected PDO $pdo  // Dépendance partagée
    ) {}

    // Implémentation partagée
    protected function execute(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    // Les sous-classes définissent leur entité
    abstract protected function getTableName(): string;
    abstract protected function hydrate(array $row): object;
}
```

## Exemple Concret

**Un processeur de paiement abstrait avec le pattern Template Method**

```php
<?php
declare(strict_types=1);

// Processeur de paiement abstrait avec méthode modèle
abstract class PaymentProcessor {
    abstract protected function validatePayment(float $amount): void;
    abstract protected function processTransaction(float $amount): string;
    abstract protected function sendReceipt(string $transactionId): void;

    // Méthode modèle
    final public function processPayment(float $amount): string {
        $this->validatePayment($amount);
        $transactionId = $this->processTransaction($amount);
        $this->sendReceipt($transactionId);
        return $transactionId;
    }

    // Méthode hook - peut être surchargée
    protected function logTransaction(string $id): void {
        echo "Transaction enregistrée : $id\n";
    }
}

class StripeProcessor extends PaymentProcessor {
    protected function validatePayment(float $amount): void {
        if ($amount < 0.50) {
            throw new InvalidArgumentException('Montant minimum : 0,50 €');
        }
    }

    protected function processTransaction(float $amount): string {
        // Appel à l'API Stripe
        return 'stripe_' . bin2hex(random_bytes(8));
    }

    protected function sendReceipt(string $transactionId): void {
        echo "Reçu Stripe envoyé pour : $transactionId\n";
    }
}

$processor = new StripeProcessor();
$txId = $processor->processPayment(99.99);
?>
```

## Les Grimoires

- [Abstraction des Classes (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.abstract.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
