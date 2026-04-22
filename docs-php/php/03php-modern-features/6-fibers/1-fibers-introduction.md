---
source_course: "php-modern-features"
source_lesson: "php-modern-features-fibers-introduction"
---

# Introduction aux Fibers (PHP 8.1+)

Les Fibers sont des **threads légers** qui permettent le multitâche coopératif. Elles permettent au code de se mettre en pause et de reprendre son exécution, permettant des patterns de programmation asynchrone.

## Qu'est-ce que les Fibers ?

- **Légères** : Surcharge minimale comparée aux threads OS
- **Coopératives** : Le code cède explicitement le contrôle (appel à `suspend`)
- **Async à l'apparence sync** : Écrire du code asynchrone qui ressemble à du synchrone

## Exemple de Base

```php
<?php
$fiber = new Fiber(function(): void {
    echo "1. Fiber démarrée\n";
    Fiber::suspend('première suspension');
    echo "3. Fiber reprise\n";
    Fiber::suspend('deuxième suspension');
    echo "5. Fiber qui se termine\n";
});

echo "Démarrage de la fiber...\n";
$result1 = $fiber->start();
echo "2. Reçu : $result1\n";

$result2 = $fiber->resume();
echo "4. Reçu : $result2\n";

$fiber->resume();
echo "6. Fiber terminée\n";

// Sortie :
// Démarrage de la fiber...
// 1. Fiber démarrée
// 2. Reçu : première suspension
// 3. Fiber reprise
// 4. Reçu : deuxième suspension
// 5. Fiber qui se termine
// 6. Fiber terminée
```

## Les Méthodes des Fibers

```php
<?php
$fiber = new Fiber(function() {
    $value = Fiber::suspend('en pause');
    return "Reçu : $value";
});

// Démarrer l'exécution
$suspended = $fiber->start();
echo $suspended;  // 'en pause'

// Reprendre avec une valeur
$result = $fiber->resume('bonjour');
echo $result;  // 'Reçu : bonjour'

// Vérifier l'état de la fiber
$fiber->isStarted();    // start() a-t-il été appelé ?
$fiber->isRunning();    // Est-elle en cours d'exécution ?
$fiber->isSuspended();  // Est-elle en pause ?
$fiber->isTerminated(); // Est-elle terminée ?

// Obtenir la valeur de retour (après terminaison)
$returnValue = $fiber->getReturn();
```

## Les Méthodes Statiques

```php
<?php
// À l'intérieur d'un callback de Fiber
Fiber::suspend($value);    // Mettre en pause et retourner une valeur à l'appelant
Fiber::getCurrent();       // Obtenir l'instance Fiber actuelle (ou null)

// Exemple
$fiber = new Fiber(function() {
    $current = Fiber::getCurrent();
    echo $current instanceof Fiber ? "Dans une fiber\n" : "Pas dans une fiber\n";
});
```

## Concepts Importants

1. **Une seule fiber s'exécute à la fois** — Pas de vraie parallélisation
2. **Suspension explicite requise** — Le code ne se met pas en pause automatiquement
3. **Fondation pour les bibliothèques async** — Pas typiquement utilisé directement
4. **Peut lancer des exceptions** — Depuis et vers la fiber

## Exemple Concret

**Générateur de suite de Fibonacci avec les Fibers**

```php
<?php
// Comportement similaire à un générateur avec les Fibers
function fibonacciGenerator(int $count): Fiber {
    return new Fiber(function() use ($count): void {
        $a = 0;
        $b = 1;

        for ($i = 0; $i < $count; $i++) {
            Fiber::suspend($a);
            [$a, $b] = [$b, $a + $b];
        }
    });
}

$fib = fibonacciGenerator(10);
$numbers = [];

// Collecter les 10 premiers nombres de Fibonacci
$numbers[] = $fib->start();
while (!$fib->isTerminated()) {
    $numbers[] = $fib->resume();
}

array_pop($numbers); // Supprimer le dernier null
print_r($numbers);
// [0, 1, 1, 2, 3, 5, 8, 13, 21, 34]
?>
```

## Les Grimoires

- [Vue d'ensemble des Fibers (Documentation Officielle)](https://www.php.net/manual/en/language.fibers.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
