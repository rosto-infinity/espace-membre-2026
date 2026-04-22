---
source_course: "php-modern-features"
source_lesson: "php-modern-features-fibers-event-loop"
---

# Construire une Boucle d'Événements avec les Fibers

Comprendre comment construire une boucle d'événements aide à démystifier les bibliothèques PHP asynchrones.

## Boucle d'Événements Simple

```php
<?php
class EventLoop
{
    private array $fibers = [];
    private array $timers = [];
    private array $pending = [];

    public function defer(callable $callback): void
    {
        $this->pending[] = $callback;
    }

    public function delay(float $seconds, callable $callback): void
    {
        $this->timers[] = [
            'time' => microtime(true) + $seconds,
            'callback' => $callback,
        ];
    }

    public function async(callable $callback): void
    {
        $this->fibers[] = new Fiber($callback);
    }

    public function run(): void
    {
        // Démarrer toutes les fibers
        foreach ($this->fibers as $fiber) {
            if (!$fiber->isStarted()) {
                $fiber->start($this);
            }
        }

        while ($this->hasWork()) {
            // Traiter les callbacks en attente
            foreach ($this->pending as $key => $callback) {
                unset($this->pending[$key]);
                $callback();
            }

            // Vérifier les timers
            $now = microtime(true);
            foreach ($this->timers as $key => $timer) {
                if ($timer['time'] <= $now) {
                    unset($this->timers[$key]);
                    $timer['callback']();
                }
            }

            // Reprendre les fibers suspendues
            foreach ($this->fibers as $key => $fiber) {
                if ($fiber->isSuspended()) {
                    $fiber->resume();
                }
                if ($fiber->isTerminated()) {
                    unset($this->fibers[$key]);
                }
            }

            // Petite pause pour éviter de saturer le CPU
            usleep(1000);
        }
    }

    private function hasWork(): bool
    {
        return !empty($this->fibers) ||
               !empty($this->timers) ||
               !empty($this->pending);
    }
}
```

## Implémentation d'un Async Sleep

```php
<?php
function asyncSleep(EventLoop $loop, float $seconds): void
{
    $fiber = Fiber::getCurrent();

    if ($fiber === null) {
        throw new RuntimeException('asyncSleep doit être appelé depuis une Fiber');
    }

    $loop->delay($seconds, function() use ($fiber) {
        if ($fiber->isSuspended()) {
            $fiber->resume();
        }
    });

    Fiber::suspend();
}

// Utilisation
$loop = new EventLoop();

$loop->async(function() use ($loop) {
    echo "Tâche 1 : Démarrage\n";
    asyncSleep($loop, 0.5);
    echo "Tâche 1 : Après 500ms\n";
    asyncSleep($loop, 0.5);
    echo "Tâche 1 : Terminée\n";
});

$loop->async(function() use ($loop) {
    echo "Tâche 2 : Démarrage\n";
    asyncSleep($loop, 0.3);
    echo "Tâche 2 : Après 300ms\n";
    asyncSleep($loop, 0.3);
    echo "Tâche 2 : Terminée\n";
});

$loop->run();
// Les deux tâches s'exécutent en parallèle (concurremment) !
```

## Le Pattern Type Promise

```php
<?php
class Deferred
{
    private ?Fiber $waiting = null;
    private mixed $result = null;
    private bool $resolved = false;

    public function resolve(mixed $value): void
    {
        $this->result = $value;
        $this->resolved = true;

        if ($this->waiting?->isSuspended()) {
            $this->waiting->resume($value);
        }
    }

    public function await(): mixed
    {
        if ($this->resolved) {
            return $this->result;
        }

        $this->waiting = Fiber::getCurrent();
        return Fiber::suspend();
    }
}

// Utilisation
$deferred = new Deferred();

$fiber = new Fiber(function() use ($deferred) {
    echo "En attente du résultat...\n";
    $result = $deferred->await();
    echo "Reçu : $result\n";
});

$fiber->start();
// Plus tard...
$deferred->resolve('Bonjour !');
// Sortie : Reçu : Bonjour !
```

## Les Grimoires

- [Documentation des Fibers PHP](https://www.php.net/manual/en/language.fibers.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
