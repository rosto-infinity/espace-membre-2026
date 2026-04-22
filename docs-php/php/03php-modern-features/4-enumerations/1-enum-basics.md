---
source_course: "php-modern-features"
source_lesson: "php-modern-features-enum-basics"
---

# Les Fondamentaux des Énumérations - Enums (PHP 8.1+)

Les énumérations (enums) représentent un **ensemble fixe de valeurs possibles**. Parfaites pour les codes de statut, les suites de cartes, les jours de la semaine, etc.

## L'Enum de Base (Unit Enum)

```php
<?php
enum Status {
    case Pending;
    case Active;
    case Suspended;
    case Deleted;
}

// Utilisation
$status = Status::Active;

if ($status === Status::Active) {
    echo "L'utilisateur est actif";
}
```

## Pourquoi Utiliser les Enums ?

Avant les enums :

```php
<?php
// Ancienne approche — sujet aux erreurs !
const STATUS_PENDING = 'pending';
const STATUS_ACTIVE = 'active';

function setStatus(string $status) {
    // N'importe qui peut passer n'importe quelle chaîne !
    $user->status = $status;
}

setStatus('actif');  // Faute de frappe non détectée !
```

Avec les enums :

```php
<?php
enum Status {
    case Pending;
    case Active;
}

function setStatus(Status $status) {
    // Type-safe — seules les valeurs Status valides sont autorisées
}

setStatus(Status::Actif);  // Erreur ! Cas invalide
setStatus('active');        // Erreur ! Mauvais type
```

## Les Méthodes et Constantes des Enums

```php
<?php
enum Status {
    case Pending;
    case Active;
    case Deleted;

    // Constante
    public const DEFAULT = self::Pending;

    // Méthode d'instance
    public function label(): string {
        return match($this) {
            self::Pending => 'En attente de validation',
            self::Active => 'Actuellement actif',
            self::Deleted => 'Supprimé',
        };
    }

    // Méthode statique
    public static function activeStatuses(): array {
        return [self::Pending, self::Active];
    }
}

echo Status::Active->label();  // Actuellement actif
```

## Lister Toutes les Valeurs

```php
<?php
enum Color {
    case Red;
    case Green;
    case Blue;
}

// Obtenir tous les cas
$colors = Color::cases();
// [Color::Red, Color::Green, Color::Blue]

// Utile pour remplir des formulaires
foreach (Color::cases() as $color) {
    echo "<option value='{$color->name}'>{$color->name}</option>";
}
```

## La Propriété `name`

```php
<?php
enum Status {
    case Active;
}

$status = Status::Active;
echo $status->name;  // "Active" (chaîne de caractères)
```

## Les Grimoires

- [Les Énumérations (Documentation Officielle)](https://www.php.net/manual/en/language.enumerations.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
