---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-trait-best-practices"
---

# Bonnes Pratiques des Traits

Les Traits peuvent être puissants pour la réutilisation de code, mais ils peuvent aussi mener à des cauchemars de maintenance s'ils sont mal utilisés. Suivez ces bonnes pratiques.

## Bons Cas d'Usage

### 1. Les Préoccupations Transversales (Cross-Cutting Concerns)

```php
<?php
// Préoccupation de logging partagée entre classes non liées
trait Loggable
{
    protected function log(string $message, string $level = 'info'): void
    {
        $context = [
            'class' => static::class,
            'timestamp' => date('c'),
        ];

        error_log("[$level] $message " . json_encode($context));
    }

    protected function logError(Throwable $e): void
    {
        $this->log($e->getMessage(), 'error');
    }
}

class UserService
{
    use Loggable;

    public function createUser(array $data): User
    {
        $this->log('Création utilisateur : ' . $data['email']);
        // ...
    }
}

class PaymentService
{
    use Loggable;

    public function processPayment(float $amount): void
    {
        $this->log("Traitement du paiement : $amount €");
        // ...
    }
}
```

### 2. Les Méthodes Utilitaires Sans État

```php
<?php
trait StringHelpers
{
    protected function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }

    protected function truncate(string $text, int $length, string $suffix = '...'): string
    {
        if (strlen($text) <= $length) {
            return $text;
        }
        return substr($text, 0, $length - strlen($suffix)) . $suffix;
    }
}
```

## Les Anti-Patterns à Éviter

### 1. Les Traits Comme Substitut à l'Héritage

```php
<?php
// MAUVAIS : Utiliser un trait pour partager un état qui devrait être hérité
trait BaseEntity
{
    protected int $id;
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;

    // Ce devrait probablement être une classe abstraite !
}

// MIEUX : Utiliser une classe abstraite pour l'état partagé
abstract class Entity
{
    protected int $id;
    protected DateTimeImmutable $createdAt;
    protected DateTimeImmutable $updatedAt;
}
```

### 2. Les Traits avec des Dépendances Cachées

```php
<?php
// MAUVAIS : Le trait assume que des propriétés existent
trait Publishable
{
    public function publish(): void
    {
        $this->status = 'published';     // D'où vient $status ?
        $this->publishedAt = new DateTime();  // Magie noire !
    }
}

// MIEUX : Rendre les dépendances explicites
trait Publishable
{
    abstract protected function getStatus(): string;
    abstract protected function setStatus(string $status): void;
    abstract protected function setPublishedAt(DateTimeInterface $date): void;

    public function publish(): void
    {
        $this->setStatus('published');
        $this->setPublishedAt(new DateTimeImmutable());
    }
}
```

### 3. L'Explosion de Traits

```php
<?php
// MAUVAIS : Trop de traits rendent le code difficile à suivre
class User
{
    use Timestamps;
    use SoftDeletes;
    use HasUuid;
    use Searchable;
    use Cacheable;
    use Loggable;
    use Validatable;
    use Serializable;
    // D'où vient n'importe quelle méthode ?!
}

// MIEUX : Limiter les traits, préférer la composition
class User
{
    use Timestamps;  // Juste les timestamps

    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger
    ) {}
}
```

## Tester les Traits

```php
<?php
// Créer une classe anonyme pour tester le trait
class TimestampableTest extends TestCase
{
    public function testSetsCreatedAt(): void
    {
        $instance = new class {
            use Timestampable;
        };

        $instance->setCreatedAt();

        $this->assertInstanceOf(
            DateTimeImmutable::class,
            $instance->getCreatedAt()
        );
    }
}
```

## Les Grimoires

- [Les Traits PHP (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.traits.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
