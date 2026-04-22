---
source_course: "php-async"
source_lesson: "php-async-fiber-error-handling"
---

# Gestion des Erreurs dans les Fibers

La **gestion des erreurs est cruciale** lors du travail avec les Fibers. Les exceptions peuvent se propager de manière inattendue si vous n'êtes pas prudent.

## Exceptions à l'Intérieur des Fibers

Les exceptions lancées dans une Fiber se propagent à l'appelant de `start()` ou `resume()` :

```php
<?php
$fiber = new Fiber(function(): void {
    echo "Avant l'exception\n";
    throw new RuntimeException('Erreur Fiber !');
    echo "Après l'exception\n";  // Jamais atteint
});

try {
    $fiber->start();
} catch (RuntimeException $e) {
    echo "Attrapé : " . $e->getMessage() . "\n";
}

echo "Fiber terminée : " . ($fiber->isTerminated() ? 'oui' : 'non') . "\n";
```

Sortie :

```
Avant l'exception
Attrapé : Erreur Fiber !
Fiber terminée : oui
```

## Lancer des Exceptions dans les Fibers

Vous pouvez injecter des exceptions dans des Fibers suspendues :

```php
<?php
$fiber = new Fiber(function(): string {
    try {
        echo "En attente de données...\n";
        $data = Fiber::suspend();
        return "Reçu : {$data}";
    } catch (TimeoutException $e) {
        return "Timeout : " . $e->getMessage();
    }
});

$fiber->start();

// Simuler un timeout
$timedOut = true;

if ($timedOut) {
    $fiber->throw(new TimeoutException('La requête a pris trop de temps'));
} else {
    $fiber->resume('des données');
}

echo $fiber->getReturn() . "\n";
```

## Pattern de Gestion d'Erreurs : Superviseurs

```php
<?php
class FiberSupervisor {
    /** @var array<string, Fiber> */
    private array $fibers = [];

    /** @var array<string, array{attempts: int, maxRetries: int}> */
    private array $retryInfo = [];

    public function spawn(
        string $id,
        callable $task,
        int $maxRetries = 3
    ): void {
        $this->fibers[$id] = new Fiber($task);
        $this->retryInfo[$id] = [
            'attempts' => 0,
            'maxRetries' => $maxRetries,
            'task' => $task
        ];
    }

    public function run(): array {
        $results = [];
        $errors = [];

        foreach ($this->fibers as $id => $fiber) {
            try {
                $results[$id] = $this->runFiber($id, $fiber);
            } catch (Throwable $e) {
                $errors[$id] = $e->getMessage();
            }
        }

        return ['results' => $results, 'errors' => $errors];
    }

    private function runFiber(string $id, Fiber $fiber): mixed {
        $info = $this->retryInfo[$id];

        while ($info['attempts'] < $info['maxRetries']) {
            try {
                if (!$fiber->isStarted()) {
                    $fiber->start();
                }

                while (!$fiber->isTerminated()) {
                    if ($fiber->isSuspended()) {
                        $fiber->resume();
                    }
                }

                return $fiber->getReturn();

            } catch (RetryableException $e) {
                $info['attempts']++;
                $this->retryInfo[$id] = $info;

                echo "[{$id}] Nouvelle tentative {$info['attempts']}/{$info['maxRetries']}\n";

                // Créer une nouvelle Fiber pour la nouvelle tentative
                $fiber = new Fiber($info['task']);

            } catch (Throwable $e) {
                // Erreur non ré-essayable
                throw $e;
            }
        }

        throw new MaxRetriesException("Nombre max de tentatives dépassé pour {$id}");
    }
}
```

## Blocs Finally et Nettoyage

```php
<?php
$fiber = new Fiber(function(): void {
    $resource = fopen('fichier.txt', 'r');

    try {
        while (!feof($resource)) {
            $line = fgets($resource);
            Fiber::suspend($line);
        }
    } finally {
        // Toujours exécuté, même si la fiber est terminée prématurément
        fclose($resource);
        echo "Ressource nettoyée\n";
    }
});

$fiber->start();
$fiber->resume();  // Lire la ligne suivante

// Terminer tôt - finally s'exécute quand même
$fiber->throw(new Exception('Terminaison anticipée'));
```

## Bonnes Pratiques de Propagation des Erreurs

```php
<?php
class AsyncResult {
    public function __construct(
        public readonly bool $success,
        public readonly mixed $value = null,
        public readonly ?Throwable $error = null
    ) {}

    public static function ok(mixed $value): self {
        return new self(true, $value);
    }

    public static function fail(Throwable $error): self {
        return new self(false, null, $error);
    }

    public function unwrap(): mixed {
        if (!$this->success) {
            throw $this->error ?? new RuntimeException('Erreur inconnue');
        }
        return $this->value;
    }
}

function safeRunFiber(Fiber $fiber): AsyncResult {
    try {
        $fiber->start();

        while ($fiber->isSuspended()) {
            $fiber->resume();
        }

        return AsyncResult::ok($fiber->getReturn());

    } catch (Throwable $e) {
        return AsyncResult::fail($e);
    }
}

// Utilisation
$result = safeRunFiber(new Fiber(fn() => riskyOperation()));

if ($result->success) {
    echo "Succès : " . $result->value . "\n";
} else {
    echo "Échec : " . $result->error->getMessage() . "\n";
}
```

## Pièges Courants

### 1. Oublier de Vérifier l'État

```php
<?php
// INCORRECT : Peut lancer FiberError
$fiber->resume($data);  // Et si la fiber n'est pas suspendue ?

// CORRECT : Toujours vérifier l'état
if ($fiber->isSuspended()) {
    $fiber->resume($data);
}
```

### 2. Exceptions Non Gérées en Contexte Async

```php
<?php
// INCORRECT : L'exception peut être perdue
$scheduler->spawn(function() {
    throw new Exception('Oups');
});

// CORRECT : Gérer au point de lancement
$scheduler->spawn(function() {
    try {
        riskyOperation();
    } catch (Exception $e) {
        logError($e);
        // Relancer si nécessaire
    }
});
```

### 3. Interblocages avec Suspend

```php
<?php
// INCORRECT : Fiber jamais reprise
$fiber = new Fiber(function() {
    Fiber::suspend();  // Qui va reprendre ceci ?
    return 'terminé';
});
$fiber->start();
// La Fiber est suspendue indéfiniment

// CORRECT : S'assurer qu'un chemin de reprise existe
// Utiliser un planificateur ou une boucle d'événements
```

## Les Grimoires

- [Manuel PHP - FiberError](https://www.php.net/manual/en/class.fibererror.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
