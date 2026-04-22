---
source_course: "php-essentials"
source_lesson: "php-essentials-loops"
---

# Les Boucles en PHP

Les boucles permettent d'exécuter du code de façon répétée. PHP propose plusieurs types de boucles pour différentes situations.

## La Boucle `for`

Idéale lorsqu'on connaît le nombre d'itérations à l'avance :

```php
<?php
for ($i = 0; $i < 5; $i++) {
    echo "Itération : $i\n";
}
// Affiche : 0, 1, 2, 3, 4
```

Structure : `for (initialisation; condition; incrément)`

## La Boucle `while`

Continue tant que la condition est vraie :

```php
<?php
$compteur = 0;

while ($compteur < 3) {
    echo "Compteur : $compteur\n";
    $compteur++;
}
// Affiche : 0, 1, 2
```

## La Boucle `do-while`

S'exécute au moins une fois avant de vérifier la condition :

```php
<?php
$x = 10;

do {
    echo "x vaut : $x\n";
    $x++;
} while ($x < 5);
// Affiche : "x vaut : 10" (une seule fois, même si 10 > 5)
```

## La Boucle `foreach`

Conçue pour parcourir les tableaux et les objets :

```php
<?php
$fruits = ['pomme', 'banane', 'cerise'];

// Valeur seule
foreach ($fruits as $fruit) {
    echo "$fruit\n";
}

// Clé et valeur
foreach ($fruits as $index => $fruit) {
    echo "$index: $fruit\n";
}

// Tableau associatif
$utilisateur = ['nom' => 'Jean', 'age' => 30];

foreach ($utilisateur as $cle => $valeur) {
    echo "$cle = $valeur\n";
}
```

## Contrôle de Boucle

### `break` — Sortir complètement de la boucle

```php
<?php
for ($i = 0; $i < 10; $i++) {
    if ($i === 5) {
        break; // Arrêt à 5
    }
    echo $i;
}
// Affiche : 01234
```

### `continue` — Passer à l'itération suivante

```php
<?php
for ($i = 0; $i < 5; $i++) {
    if ($i === 2) {
        continue; // Sauter 2
    }
    echo $i;
}
// Affiche : 0134
```

### Sortir de plusieurs boucles imbriquées

```php
<?php
for ($i = 0; $i < 3; $i++) {
    for ($j = 0; $j < 3; $j++) {
        if ($j === 1) {
            break 2; // Sortir de 2 niveaux de boucles
        }
    }
}
```

## Modifier des Tableaux dans un Foreach

```php
<?php
$nombres = [1, 2, 3];

// Utilisez une référence (&) pour modifier en place
foreach ($nombres as &$nombre) {
    $nombre *= 2;
}
unset($nombre); // Important ! Supprimer la référence après la boucle

print_r($nombres); // [2, 4, 6]
```

## Exemples de code

**Traitement de commandes avec foreach et continue**

```php
<?php
$commandes = [
    ['id' => 1, 'total' => 99.99, 'statut' => 'en_attente'],
    ['id' => 2, 'total' => 149.50, 'statut' => 'expediee'],
    ['id' => 3, 'total' => 25.00,  'statut' => 'en_attente'],
    ['id' => 4, 'total' => 200.00, 'statut' => 'livree'],
];

$totalEnAttente = 0;
$nombreEnAttente = 0;

foreach ($commandes as $commande) {
    if ($commande['statut'] !== 'en_attente') {
        continue; // Ignorer les commandes non en attente
    }

    $totalEnAttente += $commande['total'];
    $nombreEnAttente++;
}

echo "Commandes en attente : $nombreEnAttente\n";
echo "Total en attente : $totalEnAttente €\n";
?>
```

## Ressources

- [Boucles PHP](https://www.php.net/manual/fr/language.control-structures.php) — Documentation officielle de toutes les boucles PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
