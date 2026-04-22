---
source_course: "php-essentials"
source_lesson: "php-essentials-arrays-fundamentals"
---

# Fondamentaux des Tableaux

Les tableaux en PHP sont des cartes ordonnées pouvant contenir plusieurs valeurs. C'est l'une des structures de données les plus polyvalentes de PHP.

## Créer des Tableaux

```php
<?php
// Syntaxe courte (recommandée)
$fruits = ['pomme', 'banane', 'cerise'];

// Syntaxe historique
$couleurs = array('rouge', 'vert', 'bleu');

// Tableau vide
$vide = [];
```

## Tableaux Indexés

Indexés numériquement à partir de 0 :

```php
<?php
$fruits = ['pomme', 'banane', 'cerise'];

echo $fruits[0]; // pomme
echo $fruits[1]; // banane
echo $fruits[2]; // cerise

// Ajouter en fin de tableau
$fruits[] = 'datte';
// Résultat : ['pomme', 'banane', 'cerise', 'datte']
```

## Tableaux Associatifs

Paires clé-valeur :

```php
<?php
$utilisateur = [
    'nom'    => 'Jean Dupont',
    'email'  => 'jean@example.com',
    'age'    => 30,
    'actif'  => true,
];

echo $utilisateur['nom'];   // Jean Dupont
echo $utilisateur['email']; // jean@example.com

// Ajouter/mettre à jour
$utilisateur['telephone'] = '06 12 34 56 78';
$utilisateur['age'] = 31; // Mise à jour
```

## Tableaux Multi-dimensionnels

```php
<?php
$matrice = [
    [1, 2, 3],
    [4, 5, 6],
    [7, 8, 9],
];

echo $matrice[1][2]; // 6

$utilisateurs = [
    ['nom' => 'Alice', 'age' => 25],
    ['nom' => 'Bob',   'age' => 30],
];

echo $utilisateurs[0]['nom']; // Alice
```

## Déstructuration de Tableau (PHP 7.1+)

```php
<?php
$coordonnees = [10, 20, 30];
[$x, $y, $z] = $coordonnees;
echo "$x, $y, $z"; // 10, 20, 30

// Ignorer des valeurs
[, , $z] = $coordonnees;

// Avec des clés
$utilisateur = ['nom' => 'Jean', 'email' => 'jean@test.com'];
['nom' => $nom, 'email' => $email] = $utilisateur;
```

## L'Opérateur Spread

```php
<?php
$premier = [1, 2, 3];
$second  = [4, 5, 6];

$combine = [...$premier, ...$second];
// [1, 2, 3, 4, 5, 6]

// Avec tableaux associatifs (PHP 8.1+)
$defauts  = ['couleur' => 'bleu', 'taille' => 'M'];
$perso    = ['couleur' => 'rouge'];
$fusionne = [...$defauts, ...$perso];
// ['couleur' => 'rouge', 'taille' => 'M']
```

## Exemples de code

**Construire un panier d'achat avec des tableaux**

```php
<?php
$panier = [];

// Ajouter des articles
$panier[] = [
    'id'       => 101,
    'nom'      => 'Ordinateur Portable',
    'prix'     => 999.99,
    'quantite' => 1
];

$panier[] = [
    'id'       => 205,
    'nom'      => 'Souris',
    'prix'     => 29.99,
    'quantite' => 2
];

// Calculer le total
$total = 0;
foreach ($panier as $article) {
    $total += $article['prix'] * $article['quantite'];
}

echo "Total du panier : " . number_format($total, 2) . " €";
// Total du panier : 1 059,97 €
?>
```

## Ressources

- [Tableaux PHP](https://www.php.net/manual/fr/language.types.array.php) — Guide complet sur les tableaux PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
