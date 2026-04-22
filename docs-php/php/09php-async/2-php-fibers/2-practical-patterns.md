---
source_course: "php-async"
source_lesson: "php-async-fiber-practical-patterns"
---

# Patterns Pratiques avec les Fibers

Bien que vous n'utilisiez généralement pas les Fibers directement (des bibliothèques comme ReactPHP et Amp les abstraient), comprendre les patterns courants vous aide à utiliser efficacement les bibliothèques async.

## Pattern 1 : Planificateur Simple

Un planificateur gère plusieurs Fibers, décidant lesquelles exécuter :

```php
<?php
class SimpleScheduler {
    /** @var SplQueue<Fiber> */
    private SplQueue $queue;

    public function __construct() {
        $this->queue = new SplQueue();
    }

    public function add(callable $task): void {
        $this->queue->enqueue(new Fiber($task));
    }

    public function run(): void {
        while (!$this->queue->isEmpty()) {
            $fiber = $this->queue->dequeue();

            try {
                if (!$fiber->isStarted()) {
                    $fiber->start();
                } elseif ($fiber->isSuspended()) {
                    $fiber->resume();
                }

                // Si toujours en cours (suspendue), remettre en file
                if (!$fiber->isTerminated()) {
                    $this->queue->enqueue($fiber);
                }
            } catch (Throwable $e) {
                echo "Tâche échouée : " . $e->getMessage() . "\n";
            }
        }
    }
}

// Utilisation
$scheduler = new SimpleScheduler();

$scheduler->add(function() {
    echo "Tâche 1 : Étape 1\n";
    Fiber::suspend();
    echo "Tâche 1 : Étape 2\n";
    Fiber::suspend();
    echo "Tâche 1 : Terminé\n";
});

$scheduler->add(function() {
    echo "Tâche 2 : Étape 1\n";
    Fiber::suspend();
    echo "Tâche 2 : Terminé\n";
});

$scheduler->run();
```

Sortie :

```
Tâche 1 : Étape 1
Tâche 2 : Étape 1
Tâche 1 : Étape 2
Tâche 2 : Terminé
Tâche 1 : Terminé
```

## Pattern 2 : Sommeil Asynchrone

```php
<?php
class AsyncRuntime {
    private array $timers = [];
    private SplQueue $ready;

    public function __construct() {
        $this->ready = new SplQueue();
    }

    public function delay(float $seconds): void {
        $fiber = Fiber::getCurrent();
        $resumeAt = microtime(true) + $seconds;

        $this->timers[] = [
            'fiber' => $fiber,
            'time' => $resumeAt
        ];

        // Trier par temps
        usort($this->timers, fn($a, $b) => $a['time'] <=> $b['time']);

        Fiber::suspend();
    }

    public function spawn(callable $task): void {
        $this->ready->enqueue(new Fiber($task));
    }

    public function run(): void {
        while (!$this->ready->isEmpty() || !empty($this->timers)) {
            // Traiter les fibers prêtes
            while (!$this->ready->isEmpty()) {
                $fiber = $this->ready->dequeue();

                if (!$fiber->isStarted()) {
                    $fiber->start();
                } elseif ($fiber->isSuspended()) {
                    $fiber->resume();
                }
            }

            // Vérifier les timers
            $now = microtime(true);
            foreach ($this->timers as $key => $timer) {
                if ($timer['time'] <= $now) {
                    $this->ready->enqueue($timer['fiber']);
                    unset($this->timers[$key]);
                }
            }
            $this->timers = array_values($this->timers);

            // Petite pause pour éviter que le CPU tourne à vide
            if ($this->ready->isEmpty() && !empty($this->timers)) {
                usleep(1000);
            }
        }
    }
}

// Utilisation
$runtime = new AsyncRuntime();

$runtime->spawn(function() use ($runtime) {
    echo "[" . date('H:i:s') . "] Tâche 1 : Démarrage\n";
    $runtime->delay(2);
    echo "[" . date('H:i:s') . "] Tâche 1 : Après 2 secondes\n";
});

$runtime->spawn(function() use ($runtime) {
    echo "[" . date('H:i:s') . "] Tâche 2 : Démarrage\n";
    $runtime->delay(1);
    echo "[" . date('H:i:s') . "] Tâche 2 : Après 1 seconde\n";
});

$runtime->run();
```

## Pattern 3 : Simulation Async/Await

```php
<?php
class Promise {
    private mixed $value = null;
    private ?Throwable $error = null;
    private bool $resolved = false;
    private array $callbacks = [];

    public function resolve(mixed $value): void {
        $this->value = $value;
        $this->resolved = true;
        foreach ($this->callbacks as $callback) {
            $callback($value);
        }
    }

    public function reject(Throwable $error): void {
        $this->error = $error;
        $this->resolved = true;
    }

    public function then(callable $callback): self {
        if ($this->resolved && $this->error === null) {
            $callback($this->value);
        } else {
            $this->callbacks[] = $callback;
        }
        return $this;
    }

    public function isResolved(): bool {
        return $this->resolved;
    }

    public function getValue(): mixed {
        if ($this->error) {
            throw $this->error;
        }
        return $this->value;
    }
}

function await(Promise $promise): mixed {
    $fiber = Fiber::getCurrent();

    if ($fiber === null) {
        throw new LogicException('await doit être appelé dans une Fiber');
    }

    if ($promise->isResolved()) {
        return $promise->getValue();
    }

    $promise->then(function($value) use ($fiber) {
        if ($fiber->isSuspended()) {
            $fiber->resume($value);
        }
    });

    return Fiber::suspend();
}

// Exemple d'utilisation
function fetchUserAsync(int $id): Promise {
    $promise = new Promise();
    $promise->resolve(['id' => $id, 'name' => "Utilisateur {$id}"]);
    return $promise;
}

$fiber = new Fiber(function() {
    echo "Récupération de l'utilisateur...\n";
    $user = await(fetchUserAsync(42));
    echo "Utilisateur obtenu : " . $user['name'] . "\n";
});

$fiber->start();
```

## Pattern 4 : Migration de Générateur à Fiber

```php
<?php
// ANCIEN : Basé sur les générateurs
function oldAsyncTask(): Generator {
    $result1 = yield fetchData();
    $result2 = yield processData($result1);
    return $result2;
}

// NOUVEAU : Basé sur les Fibers
function newAsyncTask(): mixed {
    $result1 = await(fetchDataAsync());
    $result2 = await(processDataAsync($result1));
    return $result2;
}
```

La version Fiber se lit plus naturellement — comme du code synchrone.

## Bonnes Pratiques

1. **N'utilisez pas les Fibers directement dans le code applicatif** — utilisez des bibliothèques comme Amp ou ReactPHP
2. **Vérifiez toujours l'état de la Fiber** avant d'appeler start/resume
3. **Gérez les exceptions** à l'intérieur et à l'extérieur des Fibers
4. **Évitez les opérations bloquantes** dans les Fibers — elles bloquent tout le thread
5. **Utilisez `Fiber::getCurrent()`** pour vérifier si vous êtes dans une Fiber

## Les Grimoires

- [Amp - Framework Asynchrone](https://amphp.org/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
