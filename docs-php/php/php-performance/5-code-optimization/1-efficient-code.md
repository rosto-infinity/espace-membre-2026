---
source_course: "php-performance"
source_lesson: "php-performance-efficient-code"
---

# Écrire du Code PHP Efficace

Les petites optimisations s'accumulent. Écrivez du code efficace dès le départ.

## Opérations sur les Chaînes

```php
<?php
// MAUVAIS : La concaténation en boucle crée de nombreuses chaînes
$result = '';
for ($i = 0; $i < 10000; $i++) {
    $result .= "Ligne $i\n";
}

// BON : Construire un tableau, puis joindre
$lines = [];
for ($i = 0; $i < 10000; $i++) {
    $lines[] = "Ligne $i";
}
$result = implode("\n", $lines);

// MEILLEUR : Utiliser le tampon de sortie
ob_start();
for ($i = 0; $i < 10000; $i++) {
    echo "Ligne $i\n";
}
$result = ob_get_clean();
```

## Opérations sur les Tableaux

```php
<?php
// isset() vs array_key_exists()
// isset() est plus rapide mais retourne false pour les valeurs null
if (isset($array[$key])) {}  // Rapide
if (array_key_exists($key, $array)) {}  // Plus lent mais vérifie null

// in_array() est O(n)
// Pour les grands tableaux, utiliser array_flip + isset
$values = ['a', 'b', 'c', ...];
if (in_array($needle, $values)) {}  // O(n)

$flipped = array_flip($values);
if (isset($flipped[$needle])) {}  // O(1)

// array_map vs foreach
$squared = array_map(fn($x) => $x * $x, $numbers);  // Crée un nouveau tableau

foreach ($numbers as &$n) {  // Modifie en place
    $n = $n * $n;
}
```

## Boucles

```php
<?php
// MAUVAIS : count() appelé à chaque itération
for ($i = 0; $i < count($array); $i++) {}

// BON : Mettre en cache le count
$count = count($array);
for ($i = 0; $i < $count; $i++) {}

// Mieux : foreach pour les tableaux
foreach ($array as $item) {}

// Éviter les appels de fonctions dans les conditions
while (strlen($string) > 0) {}  // MAUVAIS
while ($string !== '') {}  // BON
```

## Efficacité Mémoire

```php
<?php
// Utiliser les générateurs pour les grands ensembles de données
function readLargeFile(string $path): Generator {
    $handle = fopen($path, 'r');
    while (($line = fgets($handle)) !== false) {
        yield $line;
    }
    fclose($handle);
}

// Une seule ligne en mémoire à la fois
foreach (readLargeFile('huge.csv') as $line) {
    processLine($line);
}

// Désaffecter les grandes variables quand on n'en a plus besoin
$largeData = fetchHugeDataset();
processData($largeData);
unset($largeData);  // Libérer la mémoire immédiatement
```

## Déclarations de Types

```php
<?php
declare(strict_types=1);

// Le code typé peut être mieux optimisé
function calculateTotal(array $items): float {
    $total = 0.0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
```

## Précalcul

```php
<?php
// MAUVAIS : Calculer à chaque appel
function getDayName(int $day): string {
    return match($day) {
        0 => 'Dimanche',
        1 => 'Lundi',
        // ...
    };
}

// BON : Utiliser un tableau constant
const DAYS = ['Dimanche', 'Lundi', 'Mardi', ...];

function getDayName(int $day): string {
    return DAYS[$day];
}
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
