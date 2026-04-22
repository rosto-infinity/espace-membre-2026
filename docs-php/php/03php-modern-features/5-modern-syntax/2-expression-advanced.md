---
source_course: "php-modern-features"
source_lesson: "php-modern-features-match-expression-advanced"
---

# Les Patterns Avancés de l'Expression Match

L'expression `match` (PHP 8.0+) est un `switch` bien plus puissant. Découvrons les patterns d'utilisation avancés.

## Match Retourne des Valeurs

```php
<?php
$status = 'active';

$message = match($status) {
    'active' => "L'utilisateur est actuellement actif",
    'pending' => "En attente d'activation",
    'banned' => "L'utilisateur a été banni",
    default => 'Statut inconnu',
};

echo $message;
```

## Plusieurs Valeurs Par Bras

```php
<?php
$day = 'Samedi';

$type = match($day) {
    'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi' => 'Jour de semaine',
    'Samedi', 'Dimanche' => 'Week-end',
};
```

## Match avec des Expressions

```php
<?php
$age = 25;

$category = match(true) {
    $age < 13 => 'enfant',
    $age < 20 => 'adolescent',
    $age < 30 => 'jeune adulte',
    $age < 60 => 'adulte',
    default => 'senior',
};
```

## Retourner des Valeurs Complexes

```php
<?php
$action = 'create';

[$method, $template] = match($action) {
    'create' => ['POST', 'form.html'],
    'edit' => ['PUT', 'form.html'],
    'delete' => ['DELETE', 'confirm.html'],
    'view' => ['GET', 'show.html'],
    default => throw new InvalidArgumentException("Action inconnue : $action"),
};
```

## Lancer une Exception dans un Match

```php
<?php
function getStatusCode(string $status): int {
    return match($status) {
        'ok' => 200,
        'created' => 201,
        'not_found' => 404,
        'error' => 500,
        default => throw new ValueError("Statut invalide : $status"),
    };
}
```

## Comparaison Match VS Switch

```php
<?php
// Switch — comparaison lâche, "fall-through"
switch ($value) {
    case 0:
    case '0':    // Les deux correspondent à cause de la comparaison lâche !
        $result = 'zero';
        break;
    default:
        $result = 'autre';
}

// Match — comparaison stricte (===), pas de "fall-through"
$result = match($value) {
    0 => 'zéro entier',
    '0' => 'zéro chaîne',
    default => 'autre',
};
```

## Match avec les Enums

```php
<?php
enum Status {
    case Active;
    case Inactive;
    case Pending;
}

$status = Status::Active;

$color = match($status) {
    Status::Active => 'vert',
    Status::Inactive => 'gris',
    Status::Pending => 'jaune',
};
```

## Exemple Concret

**Constructeur de réponse HTTP avec expressions match imbriquées**

```php
<?php
declare(strict_types=1);

// Constructeur de réponse HTTP avec match
class ResponseBuilder {
    public static function fromStatusCode(int $code): array {
        return match(true) {
            $code >= 200 && $code < 300 => [
                'type' => 'success',
                'message' => match($code) {
                    200 => 'OK',
                    201 => 'Créé',
                    204 => 'Pas de Contenu',
                    default => 'Succès',
                },
            ],
            $code >= 400 && $code < 500 => [
                'type' => 'client_error',
                'message' => match($code) {
                    400 => 'Mauvaise Requête',
                    401 => 'Non Autorisé',
                    403 => 'Interdit',
                    404 => 'Introuvable',
                    422 => 'Erreur de Validation',
                    default => 'Erreur Client',
                },
            ],
            $code >= 500 => [
                'type' => 'server_error',
                'message' => match($code) {
                    500 => 'Erreur Interne du Serveur',
                    502 => 'Mauvaise Passerelle',
                    503 => 'Service Indisponible',
                    default => 'Erreur Serveur',
                },
            ],
            default => [
                'type' => 'unknown',
                'message' => 'Statut Inconnu',
            ],
        };
    }
}

print_r(ResponseBuilder::fromStatusCode(404));
// ['type' => 'client_error', 'message' => 'Introuvable']
?>
```

## Les Grimoires

- [L'Expression Match (Documentation Officielle)](https://www.php.net/manual/en/control-structures.match.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
