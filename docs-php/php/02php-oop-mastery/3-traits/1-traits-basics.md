---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-traits-basics"
---

# Comprendre les Traits

Les Traits sont un mécanisme de réutilisation de code dans les langages à héritage simple. Ils permettent de **partager des méthodes entre classes non liées**.

## Un Trait de Base

```php
<?php
trait Timestampable {
    private ?DateTimeImmutable $createdAt = null;
    private ?DateTimeImmutable $updatedAt = null;

    public function setCreatedAt(): void {
        $this->createdAt = new DateTimeImmutable();
    }

    public function setUpdatedAt(): void {
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getCreatedAt(): ?DateTimeImmutable {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeImmutable {
        return $this->updatedAt;
    }
}

class User {
    use Timestampable;

    public function __construct(
        public string $name
    ) {
        $this->setCreatedAt();
    }
}

class Article {
    use Timestampable;

    public function __construct(
        public string $title
    ) {
        $this->setCreatedAt();
    }
}
```

## Utiliser Plusieurs Traits

```php
<?php
trait Loggable {
    public function log(string $message): void {
        echo "[LOG] $message\n";
    }
}

trait Serializable {
    public function toArray(): array {
        return get_object_vars($this);
    }

    public function toJson(): string {
        return json_encode($this->toArray());
    }
}

class Product {
    use Loggable, Serializable;

    public function __construct(
        public string $name,
        public float $price
    ) {}
}

$product = new Product('Widget', 9.99);
$product->log('Produit créé');
echo $product->toJson();
```

## Résolution de Conflits

```php
<?php
trait A {
    public function hello(): string {
        return 'Bonjour depuis A';
    }
}

trait B {
    public function hello(): string {
        return 'Bonjour depuis B';
    }
}

class MyClass {
    use A, B {
        A::hello insteadof B;  // Utiliser la version de A
        B::hello as helloB;    // Alias pour la version de B
    }
}

$obj = new MyClass();
echo $obj->hello();   // "Bonjour depuis A"
echo $obj->helloB();  // "Bonjour depuis B"
```

## Changer la Visibilité

```php
<?php
trait Secret {
    private function getSecret(): string {
        return 'secret';
    }
}

class Exposed {
    use Secret {
        getSecret as public;  // La rendre publique
    }
}

$obj = new Exposed();
echo $obj->getSecret();  // Fonctionne - maintenant publique
```

## Traits avec Méthodes Abstraites

```php
<?php
trait Sluggable {
    public function getSlug(): string {
        return $this->slugify($this->getSlugSource());
    }

    private function slugify(string $text): string {
        return strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
    }

    // Les classes utilisant ce trait DOIVENT implémenter ceci
    abstract public function getSlugSource(): string;
}

class Article {
    use Sluggable;

    public function __construct(
        public string $title
    ) {}

    public function getSlugSource(): string {
        return $this->title;
    }
}
```

## Exemple Concret

**Traits de modèle pour les suppressions douces et les UUIDs**

```php
<?php
declare(strict_types=1);

// Traits courants pour les modèles type Eloquent
trait SoftDeletes {
    private ?DateTimeImmutable $deletedAt = null;

    public function delete(): void {
        $this->deletedAt = new DateTimeImmutable();
    }

    public function restore(): void {
        $this->deletedAt = null;
    }

    public function isDeleted(): bool {
        return $this->deletedAt !== null;
    }

    public function getDeletedAt(): ?DateTimeImmutable {
        return $this->deletedAt;
    }
}

trait HasUuid {
    private string $uuid;

    public function initializeUuid(): void {
        $this->uuid = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function getUuid(): string {
        return $this->uuid;
    }
}

class Document {
    use SoftDeletes, HasUuid;

    public function __construct(
        public string $title
    ) {
        $this->initializeUuid();
    }
}

$doc = new Document('Mon Document');
echo $doc->getUuid() . "\n";
$doc->delete();
echo $doc->isDeleted() ? 'Supprimé' : 'Actif';
?>
```

## Les Grimoires

- [Les Traits PHP (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.traits.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
