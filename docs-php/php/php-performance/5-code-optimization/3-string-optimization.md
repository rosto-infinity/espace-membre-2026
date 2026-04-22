---
source_course: "php-performance"
source_lesson: "php-performance-string-optimization"
---

# Optimisation des Chaînes et de la Mémoire

Les chaînes et la gestion de la mémoire peuvent considérablement impacter les performances PHP. Apprenez les patterns qui réduisent l'utilisation mémoire et accélèrent les opérations sur les chaînes.

## Concaténation de Chaînes

```php
<?php
// MAUVAIS : Crée de nombreuses chaînes intermédiaires
$result = '';
for ($i = 0; $i < 10000; $i++) {
    $result = $result . "Ligne $i\n";  // Crée une nouvelle chaîne à chaque fois
}

// MIEUX : Utiliser l'opérateur .= (légèrement optimisé)
$result = '';
for ($i = 0; $i < 10000; $i++) {
    $result .= "Ligne $i\n";
}

// MEILLEUR : Collecter dans un tableau, joindre une fois
$lines = [];
for ($i = 0; $i < 10000; $i++) {
    $lines[] = "Ligne $i";
}
$result = implode("\n", $lines);

// OU : Utiliser le tampon de sortie
ob_start();
for ($i = 0; $i < 10000; $i++) {
    echo "Ligne $i\n";
}
$result = ob_get_clean();
```

## Fonctions de Chaînes Efficaces

```php
<?php
// Choisir la bonne fonction pour le travail

// Vérifier si une chaîne commence par un préfixe
// MAUVAIS
if (substr($string, 0, 7) === 'Bonjour') {}

// BON (PHP 8+)
if (str_starts_with($string, 'Bonjour')) {}

// Vérifier si une chaîne contient une sous-chaîne
// MAUVAIS
if (strpos($string, 'needle') !== false) {}

// BON (PHP 8+)
if (str_contains($string, 'needle')) {}

// Comparaison insensible à la casse
// MAUVAIS
if (strtolower($a) === strtolower($b)) {}

// BON
if (strcasecmp($a, $b) === 0) {}
```

## Traitement de Fichiers Économe en Mémoire

```php
<?php
// MAUVAIS : Charge tout le fichier en mémoire
$content = file_get_contents('large.csv');
$lines = explode("\n", $content);
foreach ($lines as $line) {
    process($line);
}
// Mémoire : O(taille_fichier)

// BON : Flux ligne par ligne
$handle = fopen('large.csv', 'r');
while (($line = fgets($handle)) !== false) {
    process($line);
}
fclose($handle);
// Mémoire : O(1) — une seule ligne à la fois

// ENCORE MIEUX : Utiliser SplFileObject
$file = new SplFileObject('large.csv');
foreach ($file as $line) {
    process($line);
}
```

## Réduire la Mémoire avec les Références

```php
<?php
// MAUVAIS : Copie le tableau à chaque itération
function processItems(array $items): void
{
    foreach ($items as $item) {  // Pas de copie nécessaire ici
        echo $item;
    }
}

// Pour modifier, utiliser une référence pour éviter la copie
function normalizeItems(array &$items): void
{
    foreach ($items as &$item) {
        $item = strtolower(trim($item));
    }
    unset($item);  // Casser la référence !
}

// ATTENTION : Désaffecter la référence après foreach !
```

## WeakMap pour le Cache (PHP 8+)

```php
<?php
// Le tableau ordinaire garde des références fortes (fuite mémoire potentielle)
class Cache
{
    private array $data = [];  // Les objets ne sont jamais libérés !

    public function compute(object $key, callable $fn): mixed
    {
        $hash = spl_object_hash($key);
        return $this->data[$hash] ??= $fn();
    }
}

// WeakMap : les entrées sont auto-supprimées quand la clé est garbage-collectée
class WeakCache
{
    private WeakMap $data;

    public function __construct()
    {
        $this->data = new WeakMap();
    }

    public function compute(object $key, callable $fn): mixed
    {
        return $this->data[$key] ??= $fn();
    }
}

// Quand $user est désaffecté ailleurs, l'entrée WeakMap est auto-supprimée
$cache = new WeakCache();
$user = new User(1);
$cache->compute($user, fn() => expensiveCalculation());
unset($user);  // Entrée du cache libérée automatiquement
```

## Les Grimoires

- [WeakMap](https://www.php.net/manual/en/class.weakmap.php)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
