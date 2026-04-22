---
source_course: "php-essentials"
source_lesson: "php-essentials-data-types"
---

# Les Types de Données PHP

PHP supporte dix types de données primitifs, regroupés en trois catégories.

## Types Scalaires (Valeurs Uniques)

### 1. String (Chaîne de caractères)

Une séquence de caractères :

```php
<?php
$simpleQuote = 'Bonjour';              // Guillemets simples (littéral)
$doubleQuote = "Bonjour, $nom";       // Guillemets doubles (interpolation)
$heredoc = <<<EOT
Chaîne sur
plusieurs lignes
EOT;
```

### 2. Integer (Entier)

Nombres entiers (positifs ou négatifs) :

```php
<?php
$decimal = 42;           // Décimal
$negatif = -17;          // Négatif
$octal = 0755;           // Octal (commence par 0)
$hex = 0xFF;             // Hexadécimal (commence par 0x)
$binaire = 0b1010;       // Binaire (commence par 0b)
$lisible = 1_000_000;    // PHP 7.4+ : séparateur de milliers
```

### 3. Float (Nombre à virgule flottante)

Nombres décimaux :

```php
<?php
$prix = 19.99;
$scientifique = 1.2e3;  // 1200
$negatif = -0.5;
```

### 4. Boolean (Booléen)

Vrai ou faux :

```php
<?php
$estValide = true;
$aUneErreur = false;

// Valeurs considérées comme "fausses" (falsy) en PHP :
// false, 0, 0.0, "", "0", [], null
```

## Types Composés

### 5. Array (Tableau)

```php
<?php
$indexe = [1, 2, 3];
$associatif = ["nom" => "Jean", "age" => 30];
```

### 6. Object (Objet)

```php
<?php
class Utilisateur {
    public string $nom;
}
$user = new Utilisateur();
```

### 7. Callable (Appelable)

Fonctions ou closures pouvant être appelées ultérieurement :

```php
<?php
$callback = function($x) { return $x * 2; };
```

### 8. Iterable (Itérable)

Tout ce qui peut être parcouru en boucle (tableaux, objets `Traversable`).

## Types Spéciaux

### 9. NULL

Représente l'absence de valeur :

```php
<?php
$rien = null;
$nonInitialise;  // Vaut null si on y accède (avec un avertissement)
```

### 10. Resource (Ressource)

Référence à des ressources externes (connexion de fichier, connexion BDD).

## Vérification des Types

```php
<?php
gettype($var);      // Retourne le type sous forme de chaîne
is_string($var);    // Vérification booléenne
is_int($var);
is_float($var);
is_bool($var);
is_array($var);
is_null($var);
```

## Exemples de code

**Démonstration des fonctions de vérification de type**

```php
<?php
$valeurs = [
    "Bonjour",
    42,
    3.14,
    true,
    [1, 2, 3],
    null
];

foreach ($valeurs as $valeur) {
    echo gettype($valeur) . " : ";
    var_dump($valeur);
    echo "\n";
}
?>
```

## Ressources

- [Types PHP](https://www.php.net/manual/fr/language.types.php) — Référence complète de tous les types de données PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
