---
source_course: "php-essentials"
source_lesson: "php-essentials-variables-basics"
---

# Comprendre les Variables

Les variables en PHP sont des conteneurs permettant de stocker des données. Elles sont à la base de tout programme PHP.

## Syntaxe des Variables

En PHP, toutes les variables commencent par un signe dollar (`$`) :

```php
<?php
$nom = "Jean";       // Chaîne de caractères (String)
$age = 25;           // Entier (Integer)
$prix = 19.99;       // Nombre à virgule (Float)
$estActif = true;    // Booléen (Boolean)
```

## Règles de Nommage

1. Doit commencer par une lettre ou un underscore (`_`).
2. Peut contenir des lettres, des nombres et des underscores.
3. Sensible à la casse (`$nom` ≠ `$Nom`).
4. Ne peut pas commencer par un chiffre.

```php
<?php
// Noms de variables VALIDES
$nomUtilisateur = "Alice";
$nom_utilisateur = "Bob";
$_prive = "secret";
$article1 = "premier";

// Noms de variables INVALIDES
$1article = "faux";     // Ne peut pas commencer par un nombre
$nom-utilisateur = "faux"; // Les tirets ne sont pas autorisés
```

## Assignation de Variables

Utilisez l'opérateur d'assignation (`=`) pour donner une valeur à une variable :

```php
<?php
$message = "Bonjour";        // Assigne une valeur
$message = "Au revoir";      // Réassigne (écrase la valeur précédente)

$a = $b = $c = 10;           // Assignation multiple
```

## Variables Dynamiques (Variable Variables)

PHP permet de créer des noms de variables dynamiquement (à utiliser avec parcimonie) :

```php
<?php
$nomVar = "salutation";
$$nomVar = "Bonjour !";  // Crée la variable $salutation

echo $salutation;  // Affiche : Bonjour !
```

## Vérification des Variables

```php
<?php
$nom = "Alice";

isset($nom);       // true - la variable existe et n'est pas nulle
isset($inconnu);   // false - la variable n'existe pas

empty("");         // true - une chaîne vide est considérée comme "vide"
empty(0);          // true - zéro est considéré comme "vide"
empty("hello");    // false - contient des données

unset($nom);       // Détruit la variable
isset($nom);       // false - n'existe plus
```

## Exemples de code

**Exemple concret de différents types de variables**

```php
<?php
// Déclaration et utilisation de variables
$nomProduit = "Ordinateur Portable";
$prixProduit = 999.99;
$enStock = true;
$quantite = 5;

echo "Produit : $nomProduit\n";
echo "Prix : $prixProduit €\n";
echo "En Stock : " . ($enStock ? "Oui" : "Non") . "\n";
echo "Disponible : $quantite unités";
?>
```

## Ressources

- [Variables PHP](https://www.php.net/manual/fr/language.variables.php) — Guide complet sur les variables PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
