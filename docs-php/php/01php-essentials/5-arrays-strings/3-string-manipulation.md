---
source_course: "php-essentials"
source_lesson: "php-essentials-string-manipulation"
---

# Manipulation de Chaînes de Caractères

Les chaînes sont l'un des types de données les plus courants. PHP offre des fonctions puissantes pour les manipuler.

## Créer des Chaînes

```php
<?php
// Guillemets simples - littéral
$litteral = 'Bonjour, $nom'; // Affiche : Bonjour, $nom

// Guillemets doubles - interpolation
$nom = 'Monde';
$salutation = "Bonjour, $nom"; // Affiche : Bonjour, Monde

// Accolades pour les expressions complexes
$salutation = "Bonjour, {$utilisateur['nom']}";

// Heredoc - pour les longues chaînes
$html = <<<HTML
<div class="container">
    <h1>Bienvenue, $nom</h1>
</div>
HTML;

// Nowdoc - comme les guillemets simples
$brut = <<<'TEXTE'
Pas $d_interpolation ici
TEXTE;
```

## Fonctions de Chaînes Courantes

```php
<?php
$str = "Bonjour, le Monde !";

strlen($str);              // Longueur
strtoupper($str);          // BONJOUR, LE MONDE !
strtolower($str);          // bonjour, le monde !
ucfirst($str);             // Bonjour, le monde !
ucwords("bonjour monde"); // Bonjour Monde

trim("  bonjour  ");      // "bonjour" - supprimer les espaces
ltrim("  bonjour");       // "bonjour" - supprimer à gauche
rtrim("bonjour  ");       // "bonjour" - supprimer à droite
```

## Rechercher & Trouver

```php
<?php
$str = "Bonjour, le Monde !";

strpos($str, 'Monde');        // Position (sensible à la casse)
stripos($str, 'monde');       // Position (insensible à la casse)
strrpos($str, 'o');           // Dernière occurrence de 'o'

str_contains($str, 'Monde');     // true (PHP 8+)
str_starts_with($str, 'Bonjour'); // true (PHP 8+)
str_ends_with($str, '!');        // true (PHP 8+)
```

## Extraire & Remplacer

```php
<?php
$str = "Bonjour, le Monde !";

substr($str, 0, 7);                      // "Bonjour"
substr($str, 9);                         // "le Monde !"

str_replace('Monde', 'PHP', $str);       // "Bonjour, le PHP !"
str_ireplace('MONDE', 'PHP', $str);      // Insensible à la casse

// Remplacer plusieurs valeurs
str_replace(
    ['Bonjour', 'Monde'],
    ['Salut', 'PHP'],
    $str
); // "Salut, le PHP !"
```

## Diviser & Joindre

```php
<?php
$str = "pomme,banane,cerise";

explode(',', $str);  // ['pomme', 'banane', 'cerise']

$arr = ['a', 'b', 'c'];
implode('-', $arr);  // "a-b-c"
join('-', $arr);     // Alias de implode

str_split('Bonjour', 2); // ['Bo', 'nj', 'ou', 'r']
```

## Formatage

```php
<?php
// Formatage de type printf
sprintf("Bonjour, %s !", "Monde");   // "Bonjour, Monde !"
sprintf("%05d", 42);                  // "00042"
sprintf("%.2f", 3.14159);             // "3.14"

// Formatage de nombres
number_format(1234567.891, 2);               // "1,234,567.89"
number_format(1234567.891, 2, ',', ' ');     // "1 234 567,89" (format français)
```

## Exemples de code

**Générateur de slug URL avec des fonctions de chaînes**

```php
<?php
function creerSlug(string $titre): string {
    // Convertir en minuscules
    $slug = strtolower($titre);

    // Remplacer les espaces par des tirets
    $slug = str_replace(' ', '-', $slug);

    // Supprimer les caractères spéciaux (garder seulement lettres, chiffres, tirets)
    $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

    // Supprimer les tirets consécutifs
    $slug = preg_replace('/-+/', '-', $slug);

    // Supprimer les tirets en début et fin
    return trim($slug, '-');
}

$titre = "Bonjour le Monde ! Voici PHP 8.4";
echo creerSlug($titre);
// Résultat : bonjour-le-monde--voici-php-84
?>
```

## Ressources

- [Fonctions de Chaînes PHP](https://www.php.net/manual/fr/ref.strings.php) — Référence complète des fonctions de chaînes PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
