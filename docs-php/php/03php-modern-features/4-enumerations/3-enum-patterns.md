---
source_course: "php-modern-features"
source_lesson: "php-modern-features-enum-patterns"
---

# Les Patterns Avancés des Enums

Les enums supportent des patterns avancés : machines à états, implémentation d'interfaces, et utilisation de traits.

## Machine à États avec les Enums

```php
<?php
enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self $next): bool
    {
        return match ($this) {
            self::Pending => in_array($next, [self::Confirmed, self::Cancelled]),
            self::Confirmed => in_array($next, [self::Shipped, self::Cancelled]),
            self::Shipped => $next === self::Delivered,
            self::Delivered, self::Cancelled => false,
        };
    }

    public function transitionTo(self $next): self
    {
        if (!$this->canTransitionTo($next)) {
            throw new InvalidArgumentException(
                "Impossible de passer de {$this->value} à {$next->value}"
            );
        }
        return $next;
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::Delivered, self::Cancelled]);
    }
}

// Utilisation
$status = OrderStatus::Pending;
$status = $status->transitionTo(OrderStatus::Confirmed); // OK
$status = $status->transitionTo(OrderStatus::Shipped);   // OK
```

## Les Enums Implémentant des Interfaces

```php
<?php
interface Describable
{
    public function describe(): string;
    public function getIcon(): string;
}

enum Permission: string implements Describable
{
    case Read = 'read';
    case Write = 'write';
    case Delete = 'delete';
    case Admin = 'admin';

    public function describe(): string
    {
        return match ($this) {
            self::Read => 'Voir et lire le contenu',
            self::Write => 'Créer et modifier le contenu',
            self::Delete => 'Supprimer le contenu définitivement',
            self::Admin => 'Accès administrateur complet',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Read => 'eye',
            self::Write => 'pencil',
            self::Delete => 'trash',
            self::Admin => 'crown',
        };
    }
}
```

## Enum avec Traits

```php
<?php
trait EnumToArray
{
    public static function names(): array
    {
        return array_column(self::cases(), 'name');
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function toArray(): array
    {
        return array_combine(self::names(), self::values());
    }
}

enum Color: string
{
    use EnumToArray;

    case Red = '#FF0000';
    case Green = '#00FF00';
    case Blue = '#0000FF';
}

Color::toArray();
// ['Red' => '#FF0000', 'Green' => '#00FF00', 'Blue' => '#0000FF']
```

## Validation et Formulaires avec les Enums

```php
<?php
enum Country: string
{
    case FR = 'fr';
    case UK = 'uk';
    case DE = 'de';

    public function label(): string
    {
        return match ($this) {
            self::FR => 'France',
            self::UK => 'Royaume-Uni',
            self::DE => 'Allemagne',
        };
    }

    public static function forSelect(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }

    public static function isValid(string $value): bool
    {
        return self::tryFrom($value) !== null;
    }
}
```

## Les Grimoires

- [Les Énumérations PHP (Documentation Officielle)](https://www.php.net/manual/en/language.enumerations.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
