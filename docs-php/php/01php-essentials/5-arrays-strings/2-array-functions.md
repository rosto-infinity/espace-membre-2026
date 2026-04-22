---
source_course: "php-essentials"
source_lesson: "php-essentials-array-functions"
---

# Fonctions Essentielles des Tableaux

PHP fournit des dizaines de fonctions intégrées pour la manipulation des tableaux.

## Compter & Vérifier

```php
<?php
$arr = [1, 2, 3, 4, 5];

count($arr);                      // 5
sizeof($arr);                     // 5 (alias)

in_array(3, $arr);                // true - la valeur existe
array_key_exists('nom', $user);   // vérifier si une clé existe
```

## Ajouter & Supprimer

```php
<?php
$pile = [1, 2, 3];

array_push($pile, 4, 5);  // [1,2,3,4,5] - ajouter en fin
array_pop($pile);          // retourne 5, pile devient [1,2,3,4]

array_unshift($pile, 0);   // [0,1,2,3,4] - ajouter en début
array_shift($pile);        // retourne 0, pile devient [1,2,3,4]

unset($pile[1]);           // Supprimer par clé
```

## Rechercher

```php
<?php
$fruits = ['pomme', 'banane', 'cerise'];

array_search('banane', $fruits); // 1 (index)

// PHP 8.4+ : Nouvelles fonctions array_find
$utilisateurs = [
    ['id' => 1, 'nom' => 'Alice'],
    ['id' => 2, 'nom' => 'Bob'],
];

$bob = array_find($utilisateurs, fn($u) => $u['nom'] === 'Bob');
// ['id' => 2, 'nom' => 'Bob']
```

## Transformer

```php
<?php
$nombres = [1, 2, 3, 4, 5];

// Map - transformer chaque élément
$doubles = array_map(fn($n) => $n * 2, $nombres);
// [2, 4, 6, 8, 10]

// Filter - garder les éléments correspondants
$pairs = array_filter($nombres, fn($n) => $n % 2 === 0);
// [2, 4]

// Reduce - réduire à une valeur unique
$somme = array_reduce($nombres, fn($acc, $n) => $acc + $n, 0);
// 15
```

## Trier

```php
<?php
$arr = [3, 1, 4, 1, 5];

sort($arr);          // [1, 1, 3, 4, 5] - croissant
rsort($arr);         // [5, 4, 3, 1, 1] - décroissant

// Conserver les clés
asort($arr);         // Trier par valeur, garder les clés
ksort($arr);         // Trier par clé

// Tri personnalisé
usort($arr, fn($a, $b) => $b - $a); // Décroissant
```

## Fusionner & Combiner

```php
<?php
$a = ['x' => 1, 'y' => 2];
$b = ['y' => 3, 'z' => 4];

array_merge($a, $b);            // ['x'=>1, 'y'=>3, 'z'=>4]
array_merge_recursive($a, $b);  // Conserve les deux valeurs

$cles   = ['a', 'b', 'c'];
$valeurs = [1, 2, 3];
array_combine($cles, $valeurs); // ['a'=>1, 'b'=>2, 'c'=>3]
```

## Extraire

```php
<?php
$arr = [1, 2, 3, 4, 5];

array_slice($arr, 2);      // [3, 4, 5] - à partir de l'index 2
array_slice($arr, 1, 2);   // [2, 3] - 2 éléments à partir de l'index 1

array_keys($arr);          // [0, 1, 2, 3, 4]
array_values($arr);        // Réindexer le tableau
```

## Exemples de code

**Pipeline de traitement de données avec des fonctions de tableau**

```php
<?php
$commandes = [
    ['id' => 1, 'total' => 150, 'statut' => 'completee'],
    ['id' => 2, 'total' => 75,  'statut' => 'en_attente'],
    ['id' => 3, 'total' => 200, 'statut' => 'completee'],
    ['id' => 4, 'total' => 50,  'statut' => 'annulee'],
    ['id' => 5, 'total' => 300, 'statut' => 'completee'],
];

// Pipeline : Filter -> Map -> Reduce
$revenuCompletes = array_reduce(
    array_map(
        fn($c) => $c['total'],
        array_filter(
            $commandes,
            fn($c) => $c['statut'] === 'completee'
        )
    ),
    fn($somme, $total) => $somme + $total,
    0
);

echo "Revenu des commandes complètes : $revenuCompletes €";
// Revenu des commandes complètes : 650 €
?>
```

## Ressources

- [Fonctions de Tableau PHP](https://www.php.net/manual/fr/ref.array.php) — Référence complète des fonctions tableau PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
