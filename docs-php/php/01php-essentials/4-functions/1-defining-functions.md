---
source_course: "php-essentials"
source_lesson: "php-essentials-defining-functions"
---

# Définir des Fonctions en PHP

Les fonctions sont des blocs de code réutilisables qui exécutent des tâches spécifiques. Elles vous aident à organiser votre code et à éviter les répétitions.

## Syntaxe de Base

```php
<?php
function saluer() {
    echo "Bonjour le monde !";
}

// Appel de la fonction
saluer(); // Affiche : Bonjour le monde !
```

## Fonctions avec Paramètres

```php
<?php
function saluer(string $nom) {
    echo "Bonjour, $nom !";
}

saluer("Alice"); // Bonjour, Alice !
saluer("Bob");   // Bonjour, Bob !
```

## Paramètres Multiples

```php
<?php
function creerUtilisateur(string $nom, string $email, int $age) {
    echo "Création de l'utilisateur : $nom ($email), Âge : $age";
}

creerUtilisateur("Jean", "jean@example.com", 25);
```

## Valeurs par Défaut des Paramètres

```php
<?php
function saluer(string $nom, string $greeting = "Bonjour") {
    echo "$greeting, $nom !";
}

saluer("Alice");             // Bonjour, Alice !
saluer("Bob", "Bienvenue"); // Bienvenue, Bob !
```

## Valeurs de Retour

```php
<?php
function additionner(int $a, int $b): int {
    return $a + $b;
}

$somme = additionner(5, 3);
echo $somme; // 8
```

## Retour Anticipé (Early Return)

```php
<?php
function diviser(float $a, float $b): ?float {
    if ($b === 0.0) {
        return null; // Retour anticipé pour le cas d'erreur
    }
    return $a / $b;
}
```

## Arguments Nommés (PHP 8+)

```php
<?php
function creerProduit(
    string $nom,
    float $prix,
    int $stock = 0,
    bool $vedette = false
) {
    // ...
}

// Passer directement aux paramètres souhaités par leur nom :
creerProduit(
    nom: "Ordinateur Portable",
    prix: 999.99,
    vedette: true // $stock utilise sa valeur par défaut
);
```

## Fonctions Variadiques

Accepter un nombre indéfini d'arguments :

```php
<?php
function somme(int ...$nombres): int {
    return array_sum($nombres);
}

echo somme(1, 2, 3);       // 6
echo somme(1, 2, 3, 4, 5); // 15
```

## Exemples de code

**Fonction pratique avec arguments nommés et types**

```php
<?php
declare(strict_types=1);

function calculerRemise(
    float $prix,
    float $pourcentageRemise = 10.0,
    float $remiseMax = 50.0
): float {
    $remise = $prix * ($pourcentageRemise / 100);

    // Plafonner la remise
    if ($remise > $remiseMax) {
        $remise = $remiseMax;
    }

    return $prix - $remise;
}

// Utilisation de la fonction
$original = 100.00;
$final = calculerRemise($original, pourcentageRemise: 15.0);

echo "Prix original : $original €\n";
echo "Après remise : $final €\n";
?>
```

## Ressources

- [Fonctions PHP](https://www.php.net/manual/fr/language.functions.php) — Guide complet sur les fonctions PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
