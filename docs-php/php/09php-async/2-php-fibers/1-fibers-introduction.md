---
source_course: "php-async"
source_lesson: "php-async-fibers-introduction"
---

# Introduction aux PHP Fibers

PHP 8.1 a introduit les **Fibers**, une fonctionnalité révolutionnaire qui permet le multitâche coopératif sans la complexité des callbacks ou des générateurs. Les Fibers sont le fondement des bibliothèques PHP modernes.

## Qu'est-ce qu'une Fiber ?

Une **Fiber** est un contexte d'exécution léger qui peut être mis en pause et repris. Pensez-y comme une fonction qui peut être suspendue au milieu de son exécution et reprise plus tard exactement là où elle s'était arrêtée.

```php
<?php
// Exemple de base de Fiber
$fiber = new Fiber(function(): void {
    echo "Fiber démarrée\n";

    $value = Fiber::suspend('en pause');

    echo "Fiber reprise avec : {$value}\n";
});

// Démarrer la fiber
$result = $fiber->start();
echo "La Fiber a cédé : {$result}\n";

// Reprendre la fiber
$fiber->resume('bonjour');
```

Sortie :

```
Fiber démarrée
La Fiber a cédé : en pause
Fiber reprise avec : bonjour
```

## Le Cycle de Vie d'une Fiber

```
┌─────────────┐
│   Créée     │ ──start()──▶ ┌──────────┐
└─────────────┘               │ En cours │
                              └────┬─────┘
                                   │
              ┌────────────────────┼────────────────────┐
              │                    │                    │
              ▼                    ▼                    ▼
       ┌──────────┐         ┌──────────┐        ┌────────────┐
       │ Suspendue│◀─resume─│ En cours │        │  Terminée  │
       └────┬─────┘         └──────────┘        └────────────┘
            │
            └──────resume()──────▶ (retour à En cours)
```

## États d'une Fiber

| Méthode                     | Description                                     |
| --------------------------- | ----------------------------------------------- |
| `Fiber::getCurrent()`       | Obtenir la Fiber en cours d'exécution           |
| `$fiber->start(...$args)`   | Démarrer l'exécution, passer des arguments      |
| `$fiber->resume($value)`    | Reprendre une Fiber suspendue                   |
| `$fiber->throw($exception)` | Reprendre en lançant une exception              |
| `$fiber->isStarted()`       | La Fiber a-t-elle été démarrée ?                |
| `$fiber->isSuspended()`     | La Fiber est-elle suspendue ?                   |
| `$fiber->isRunning()`       | La Fiber est-elle en cours ?                    |
| `$fiber->isTerminated()`    | La Fiber a-t-elle terminé ?                     |
| `$fiber->getReturn()`       | Obtenir la valeur de retour (après terminaison) |

## Comment les Fibers Fonctionnent en Interne

Les Fibers utilisent le **commutation de pile** pour sauvegarder et restaurer l'état d'exécution :

```php
<?php
$fiber = new Fiber(function(): string {
    $a = 1;                    // État local préservé
    Fiber::suspend();

    $b = 2;                    // Continue avec $a = 1
    Fiber::suspend();

    return "a={$a}, b={$b}";   // Les deux variables intactes
});

$fiber->start();
$fiber->resume();
$fiber->resume();

echo $fiber->getReturn();  // "a=1, b=2"
```

Contrairement aux générateurs (qui préservent seulement l'état de la fonction courante), les Fibers préservent **toute la pile d'appels** :

```php
<?php
function innerFunction(): void {
    echo "Inner : avant la suspension\n";
    Fiber::suspend();  // Suspend à travers plusieurs frames d'appel !
    echo "Inner : après la suspension\n";
}

function outerFunction(): void {
    echo "Outer : avant inner\n";
    innerFunction();  // Pile d'appels préservée lors de la suspension
    echo "Outer : après inner\n";
}

$fiber = new Fiber(outerFunction(...));
$fiber->start();   // Sortie : Outer : avant inner, Inner : avant la suspension
$fiber->resume();  // Sortie : Inner : après la suspension, Outer : après inner
```

## Passer des Données via Suspend/Resume

Les données circulent dans les deux sens :

```php
<?php
$fiber = new Fiber(function(): int {
    // Suspend retourne ce que resume() passe
    $x = Fiber::suspend('en attente de x');
    $y = Fiber::suspend('en attente de y');

    return $x + $y;
});

$message1 = $fiber->start();    // Retourne 'en attente de x'
echo $message1 . "\n";

$message2 = $fiber->resume(10); // Passe 10, retourne 'en attente de y'
echo $message2 . "\n";

$fiber->resume(20);             // Passe 20, la fiber se termine

echo $fiber->getReturn();       // 30
```

## Gestion des Exceptions

Les Fibers gèrent les exceptions naturellement :

```php
<?php
$fiber = new Fiber(function(): void {
    try {
        Fiber::suspend();
    } catch (RuntimeException $e) {
        echo "Attrapé : " . $e->getMessage() . "\n";
    }
});

$fiber->start();

// Injecter une exception dans la fiber suspendue
$fiber->throw(new RuntimeException('Quelque chose s\'est mal passé'));
// Sortie : Attrapé : Quelque chose s'est mal passé
```

## Mémoire et Performance

Les Fibers sont légères :

- Chaque Fiber a sa propre pile (par défaut ~8 Ko)
- Créer des milliers de Fibers est faisable
- Le changement de contexte est rapide (microsecondes)
- La mémoire est libérée quand la Fiber est collectée

```php
<?php
// Créer beaucoup de fibers est efficace
$fibers = [];
for ($i = 0; $i < 10000; $i++) {
    $fibers[] = new Fiber(function() use ($i): int {
        Fiber::suspend();
        return $i * 2;
    });
}

echo "Créé 10 000 fibers\n";
echo "Mémoire : " . round(memory_get_usage() / 1024 / 1024, 2) . " Mo\n";
```

## Les Grimoires

- [Manuel PHP - Fibers](https://www.php.net/manual/en/language.fibers.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
