---
source_course: "php-essentials"
source_lesson: "php-essentials-anonymous-functions-closures"
---

# Fonctions Anonymes & Closures

Les fonctions anonymes (aussi appelées closures) sont des fonctions sans nom. Elles sont très utiles pour les callbacks et les fonctionnalités ponctuelles.

## Fonction Anonyme de Base

```php
<?php
$saluer = function(string $nom): string {
    return "Bonjour, $nom !";
};

echo $saluer("Alice"); // Bonjour, Alice !
```

## En tant que Callbacks

```php
<?php
$nombres = [1, 2, 3, 4, 5];

// Doubler chaque nombre
$doubles = array_map(function($n) {
    return $n * 2;
}, $nombres);

print_r($doubles); // [2, 4, 6, 8, 10]

// Filtrer les nombres pairs
$pairs = array_filter($nombres, function($n) {
    return $n % 2 === 0;
});

print_r($pairs); // [2, 4]
```

## Capturer des Variables avec `use`

Les closures peuvent accéder aux variables de la portée extérieure :

```php
<?php
$multiplicateur = 3;

$multiplier = function(int $n) use ($multiplicateur): int {
    return $n * $multiplicateur;
};

echo $multiplier(5); // 15
```

## Capturer par Référence

```php
<?php
$total = 0;

$ajouter = function(int $montant) use (&$total): void {
    $total += $montant; // Modifie la variable originale
};

$ajouter(10);
$ajouter(20);
echo $total; // 30
```

## Fonctions Fléchées — Arrow Functions (PHP 7.4+)

Syntaxe courte pour les closures simples :

```php
<?php
// Closure traditionnelle
$doubler = function($n) {
    return $n * 2;
};

// Fonction fléchée (équivalent)
$doubler = fn($n) => $n * 2;

echo $doubler(5); // 10
```

## Caractéristiques des Fonctions Fléchées

1. **Capture automatique** des variables extérieures (pas besoin de `use`).
2. **Expression unique** uniquement (retour implicite).
3. **Pas d'instructions multiples**.

```php
<?php
$facteur = 3;

// La fonction fléchée capture $facteur automatiquement
$multiplier = fn($n) => $n * $facteur;

$nombres = [1, 2, 3];
$resultat = array_map(fn($n) => $n * $facteur, $nombres);
// [3, 6, 9]
```

## Syntaxe Callable de Première Classe (PHP 8.1+)

Créer une closure depuis n'importe quel callable existant :

```php
<?php
class Calculatrice {
    public function additionner(int $a, int $b): int {
        return $a + $b;
    }
}

$calc = new Calculatrice();
$addition = $calc->additionner(...);

echo $addition(2, 3); // 5
```

## Exemples de code

**Filtrage et transformation de données avec des fonctions fléchées**

```php
<?php
$produits = [
    ['nom' => 'Ordinateur', 'prix' => 999, 'categorie' => 'electronique'],
    ['nom' => 'T-Shirt',    'prix' => 29,  'categorie' => 'vetements'],
    ['nom' => 'Téléphone',  'prix' => 699, 'categorie' => 'electronique'],
    ['nom' => 'Pantalon',   'prix' => 49,  'categorie' => 'vetements'],
];

$prixMin = 50;

// Filtrer les produits électroniques au-dessus du prix minimum
$electroniquesChers = array_filter(
    $produits,
    fn($p) => $p['categorie'] === 'electronique' && $p['prix'] >= $prixMin
);

// Extraire seulement les noms
$noms = array_map(fn($p) => $p['nom'], $electroniquesChers);

print_r($noms); // ['Ordinateur', 'Téléphone']
?>
```

## Ressources

- [Fonctions Anonymes](https://www.php.net/manual/fr/functions.anonymous.php) — Guide officiel sur les closures et fonctions anonymes PHP
- [Fonctions Fléchées](https://www.php.net/manual/fr/functions.arrow.php) — Documentation des arrow functions PHP 7.4+

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
