---
source_course: "php-essentials"
source_lesson: "php-essentials-match-expression"
---

# L'Expression Match

PHP 8 a introduit l'expression `match` comme alternative plus puissante au `switch`. Elle est plus concise et plus sûre.

## Syntaxe de Base

```php
<?php
$statut = 200;

$message = match($statut) {
    200 => 'OK',
    201 => 'Créé',
    400 => 'Requête Incorrecte',
    404 => 'Non Trouvé',
    500 => 'Erreur Serveur',
};

echo $message;  // 'OK'
```

## Match vs Switch

| Fonctionnalité       | `match`               | `switch`           |
| -------------------- | --------------------- | ------------------ |
| Comparaison          | Stricte (`===`)       | Lâche (`==`)       |
| Retourne une valeur  | Oui                   | Non                |
| Fall-through         | Non                   | Oui (sans `break`) |
| `default` requis     | Oui (sinon exception) | Non                |
| Conditions multiples | Oui                   | Oui                |

## Conditions Multiples

```php
<?php
$jour = 'Samedi';

$type = match($jour) {
    'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi' => 'Jour de semaine',
    'Samedi', 'Dimanche' => 'Week-end',
};

echo $type;  // 'Week-end'
```

## Le Cas par Défaut

```php
<?php
$code = 418;

$message = match($code) {
    200 => 'OK',
    404 => 'Non Trouvé',
    default => 'Statut Inconnu',
};

echo $message;  // 'Statut Inconnu'
```

## Match avec Expressions Complexes

```php
<?php
$age = 25;

$categorie = match(true) {
    $age < 13  => 'enfant',
    $age < 20  => 'adolescent',
    $age < 65  => 'adulte',
    default    => 'sénior',
};

echo $categorie;  // 'adulte'
```

## Quand Utiliser Match

1. **Retourner des valeurs** selon des conditions.
2. **Comparaisons strictes** nécessaires.
3. **Plusieurs valeurs** mappant vers le même résultat.
4. Quand le **fall-through** de `switch` est indésirable.

## Gestion des Erreurs

```php
<?php
$valeur = 'inconnue';

// Lance UnhandledMatchError si aucune branche ne correspond !
$resultat = match($valeur) {
    'a' => 1,
    'b' => 2,
    // Pas de default = UnhandledMatchError
};

// Toujours inclure un default pour la sécurité
$resultat = match($valeur) {
    'a' => 1,
    'b' => 2,
    default => 0,
};
```

## Exemples de code

**Gestion des codes HTTP avec match**

```php
<?php
function getInfoStatut(int $code): array {
    return match($code) {
        200, 201, 204 => [
            'type' => 'succes',
            'icone' => '✓'
        ],
        301, 302, 307 => [
            'type' => 'redirection',
            'icone' => '→'
        ],
        400, 401, 403, 404 => [
            'type' => 'erreur_client',
            'icone' => '⚠'
        ],
        500, 502, 503 => [
            'type' => 'erreur_serveur',
            'icone' => '✗'
        ],
        default => [
            'type' => 'inconnu',
            'icone' => '?'
        ],
    };
}

print_r(getInfoStatut(404));
// ['type' => 'erreur_client', 'icone' => '⚠']
?>
```

## Ressources

- [Expression Match](https://www.php.net/manual/fr/control-structures.match.php) — Documentation officielle de l'expression match PHP 8

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
