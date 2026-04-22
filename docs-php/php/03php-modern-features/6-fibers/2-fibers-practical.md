---
source_course: "php-modern-features"
source_lesson: "php-modern-features-fibers-practical"
---

# Les Patterns Pratiques des Fibers

Bien que les Fibers soient rarement utilisées directement, comprendre leurs patterns aide lorsqu'on travaille avec des bibliothèques asynchrones.

## Un Scheduler Simple

```php
<?php
class SimpleScheduler {
    private array $fibers = [];

    public function add(callable $task): void {
        $this->fibers[] = new Fiber($task);
    }

    public function run(): void {
        // Démarrer toutes les fibers
        foreach ($this->fibers as $fiber) {
            $fiber->start();
        }

        // Continuer jusqu'à ce que toutes soient terminées
        while ($this->hasRunning()) {
            foreach ($this->fibers as $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }
            }
        }
    }

    private function hasRunning(): bool {
        foreach ($this->fibers as $fiber) {
            if (!$fiber->isTerminated()) {
                return true;
            }
        }
        return false;
    }
}
```

## Simulation de Pause Asynchrone (Async Sleep)

```php
<?php
// Dans les vraies bibliothèques async, le sleep ne bloque pas
class AsyncRuntime {
    private array $sleeping = [];

    public function sleep(float $seconds): void {
        $fiber = Fiber::getCurrent();
        $wakeAt = microtime(true) + $seconds;

        $this->sleeping[] = [
            'fiber' => $fiber,
            'wakeAt' => $wakeAt,
        ];

        Fiber::suspend();
    }

    public function tick(): void {
        $now = microtime(true);

        foreach ($this->sleeping as $key => $item) {
            if ($item['wakeAt'] <= $now) {
                unset($this->sleeping[$key]);
                $item['fiber']->resume();
            }
        }
    }
}
```

## La Gestion des Exceptions

```php
<?php
$fiber = new Fiber(function() {
    try {
        Fiber::suspend('en attente');
    } catch (Exception $e) {
        echo "Capturé : " . $e->getMessage();
        return 'géré';
    }
});

$fiber->start();

// Lancer une exception dans la fiber
$result = $fiber->throw(new Exception('Quelque chose a mal tourné'));
echo $result;  // 'géré'
```

## Utilisation dans le Monde Réel

Les Fibers alimentent des bibliothèques asynchrones comme :

- **ReactPHP** — Programmation orientée événements
- **Amp** — Framework asynchrone
- **Revolt** — Implémentation de boucle d'événements

```php
<?php
// Comment les bibliothèques async utilisent les Fibers (conceptuel)

// L'utilisateur écrit ceci :
async function fetchData() {
    $response = await httpGet('https://api.example.com');
    return $response->body;
}

// La bibliothèque traduit en :
function fetchData() {
    $fiber = Fiber::getCurrent();

    httpGetAsync('https://api.example.com', function($response) use ($fiber) {
        $fiber->resume($response);
    });

    return Fiber::suspend();
}
```

## Quand Utiliser les Fibers ?

1. **Construire des frameworks async** — Fondation pour les boucles d'événements
2. **Multitâche coopératif** — Plusieurs opérations concurrentes
3. **Patterns de type coroutine** — Calculs interruptibles

**En général, vous utiliserez des bibliothèques construites sur les Fibers plutôt que les Fibers directement.**

## Exemple Concret

**Exécuteur de tâches concurrentes avec les Fibers**

```php
<?php
// Exécuteur de tâches concurrentes avec les Fibers
class TaskRunner {
    private array $tasks = [];
    private array $results = [];

    public function addTask(string $name, callable $task): self {
        $this->tasks[$name] = new Fiber($task);
        return $this;
    }

    public function run(): array {
        // Démarrer toutes les tâches
        foreach ($this->tasks as $name => $fiber) {
            echo "Démarrage : $name\n";
            $fiber->start();
        }

        // Traiter jusqu'à ce que toutes soient terminées
        $pending = count($this->tasks);

        while ($pending > 0) {
            foreach ($this->tasks as $name => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }

                if ($fiber->isTerminated() && !isset($this->results[$name])) {
                    $this->results[$name] = $fiber->getReturn();
                    $pending--;
                    echo "Terminée : $name\n";
                }
            }
        }

        return $this->results;
    }
}

// Utilisation
$runner = new TaskRunner();

$runner->addTask('tâche1', function() {
    for ($i = 0; $i < 3; $i++) {
        echo "Tâche 1 étape $i\n";
        Fiber::suspend();
    }
    return 'Tâche 1 terminée';
});

$runner->addTask('tâche2', function() {
    for ($i = 0; $i < 2; $i++) {
        echo "Tâche 2 étape $i\n";
        Fiber::suspend();
    }
    return 'Tâche 2 terminée';
});

$results = $runner->run();
print_r($results);
?>
```

## Les Grimoires

- [La Classe Fiber (Référence Complète)](https://www.php.net/manual/en/class.fiber.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
