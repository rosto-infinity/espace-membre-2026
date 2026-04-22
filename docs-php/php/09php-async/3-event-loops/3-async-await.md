---
source_course: "php-async"
source_lesson: "php-async-promises-async-await"
---

# Promises et Patterns Async/Await

Les **Promises offrent une façon plus propre** de gérer les opérations asynchrones que les callbacks. Combinées avec les Fibers, PHP peut supporter la syntaxe async/await.

## Qu'est-ce qu'une Promise ?

Une **Promise** représente une valeur qui peut ne pas être disponible encore mais le sera à un moment donné, ou échouera avec une erreur.

```php
<?php
enum PromiseState {
    case Pending;   // Pas encore résolue
    case Fulfilled; // Complétée avec succès avec une valeur
    case Rejected;  // Échouée avec une erreur
}

class Promise {
    private PromiseState $state = PromiseState::Pending;
    private mixed $value = null;
    private ?Throwable $reason = null;
    private array $onFulfilled = [];
    private array $onRejected = [];

    public function then(
        ?callable $onFulfilled = null,
        ?callable $onRejected = null
    ): self {
        $next = new self();

        $handleFulfilled = function($value) use ($onFulfilled, $next) {
            try {
                if ($onFulfilled === null) {
                    $next->resolve($value);
                } else {
                    $result = $onFulfilled($value);
                    if ($result instanceof self) {
                        $result->then(
                            fn($v) => $next->resolve($v),
                            fn($e) => $next->reject($e)
                        );
                    } else {
                        $next->resolve($result);
                    }
                }
            } catch (Throwable $e) {
                $next->reject($e);
            }
        };

        match ($this->state) {
            PromiseState::Pending => (
                $this->onFulfilled[] = $handleFulfilled,
                $this->onRejected[] = $handleRejected ?? fn() => null
            ),
            PromiseState::Fulfilled => $handleFulfilled($this->value),
            PromiseState::Rejected => ($handleRejected ?? fn() => null)($this->reason),
        };

        return $next;
    }

    public function catch(callable $onRejected): self {
        return $this->then(null, $onRejected);
    }

    public function finally(callable $callback): self {
        return $this->then(
            function($value) use ($callback) {
                $callback();
                return $value;
            },
            function($reason) use ($callback) {
                $callback();
                throw $reason;
            }
        );
    }

    public function resolve(mixed $value): void {
        if ($this->state !== PromiseState::Pending) return;
        $this->state = PromiseState::Fulfilled;
        $this->value = $value;
        foreach ($this->onFulfilled as $callback) {
            $callback($value);
        }
    }

    public function reject(Throwable $reason): void {
        if ($this->state !== PromiseState::Pending) return;
        $this->state = PromiseState::Rejected;
        $this->reason = $reason;
        foreach ($this->onRejected as $callback) {
            $callback($reason);
        }
    }
}
```

## Utiliser les Promises

```php
<?php
function delay(float $seconds): Promise {
    $promise = new Promise();
    $promise->resolve($seconds);
    return $promise;
}

// Chaîner les promises
delay(1.0)
    ->then(function($seconds) {
        echo "Attendu {$seconds} secondes\n";
        return fetchUser(1);
    })
    ->then(function($user) {
        echo "Utilisateur obtenu : {$user['name']}\n";
        return fetchOrders($user['id']);
    })
    ->then(function($orders) {
        echo count($orders) . " commandes obtenues\n";
    })
    ->catch(function($error) {
        echo "Erreur : " . $error->getMessage() . "\n";
    })
    ->finally(function() {
        echo "Nettoyage terminé\n";
    });
```

## Combinateurs de Promises

```php
<?php
class PromiseUtils {
    /**
     * Attendre que toutes les promises se résolvent
     * Rejette si une promise est rejetée
     */
    public static function all(array $promises): Promise {
        $result = new Promise();
        $values = [];
        $remaining = count($promises);

        if ($remaining === 0) {
            $result->resolve([]);
            return $result;
        }

        foreach ($promises as $key => $promise) {
            $promise->then(
                function($value) use ($key, &$values, &$remaining, $result) {
                    $values[$key] = $value;
                    if (--$remaining === 0) {
                        ksort($values);
                        $result->resolve($values);
                    }
                },
                fn($error) => $result->reject($error)
            );
        }

        return $result;
    }

    /**
     * Attendre que la première promise se règle
     */
    public static function race(array $promises): Promise {
        $result = new Promise();

        foreach ($promises as $promise) {
            $promise->then(
                fn($value) => $result->resolve($value),
                fn($error) => $result->reject($error)
            );
        }

        return $result;
    }

    /**
     * Attendre que toutes les promises se règlent (ne rejette jamais)
     */
    public static function allSettled(array $promises): Promise {
        $result = new Promise();
        $outcomes = [];
        $remaining = count($promises);

        foreach ($promises as $key => $promise) {
            $promise->then(
                function($value) use ($key, &$outcomes, &$remaining, $result) {
                    $outcomes[$key] = ['status' => 'fulfilled', 'value' => $value];
                    if (--$remaining === 0) {
                        ksort($outcomes);
                        $result->resolve($outcomes);
                    }
                },
                function($error) use ($key, &$outcomes, &$remaining, $result) {
                    $outcomes[$key] = ['status' => 'rejected', 'reason' => $error];
                    if (--$remaining === 0) {
                        ksort($outcomes);
                        $result->resolve($outcomes);
                    }
                }
            );
        }

        return $result;
    }
}

// Utilisation
PromiseUtils::all([
    fetchUser(1),
    fetchUser(2),
    fetchUser(3),
])->then(function($users) {
    foreach ($users as $user) {
        echo "Utilisateur : {$user['name']}\n";
    }
});
```

## Async/Await avec les Fibers

```php
<?php
class AsyncRuntime {
    private static ?self $instance = null;
    private array $pending = [];

    public static function getInstance(): self {
        return self::$instance ??= new self();
    }

    public function await(Promise $promise): mixed {
        $fiber = Fiber::getCurrent();

        if ($fiber === null) {
            throw new LogicException('await doit être appelé dans un contexte async');
        }

        $resolved = false;
        $result = null;
        $error = null;

        $promise->then(
            function($value) use (&$resolved, &$result, $fiber) {
                $resolved = true;
                $result = $value;
                if ($fiber->isSuspended()) {
                    $this->pending[] = fn() => $fiber->resume($value);
                }
            },
            function($e) use (&$resolved, &$error, $fiber) {
                $resolved = true;
                $error = $e;
                if ($fiber->isSuspended()) {
                    $this->pending[] = fn() => $fiber->throw($e);
                }
            }
        );

        if ($resolved) {
            if ($error) throw $error;
            return $result;
        }

        return Fiber::suspend();
    }

    public function async(callable $fn): Promise {
        $promise = new Promise();
        $fiber = new Fiber($fn);

        try {
            $fiber->start();
            if ($fiber->isTerminated()) {
                $promise->resolve($fiber->getReturn());
            }
        } catch (Throwable $e) {
            $promise->reject($e);
        }

        return $promise;
    }

    public function run(): void {
        while (!empty($this->pending)) {
            $callback = array_shift($this->pending);
            $callback();
        }
    }
}

function async(callable $fn): Promise {
    return AsyncRuntime::getInstance()->async($fn);
}

function await(Promise $promise): mixed {
    return AsyncRuntime::getInstance()->await($promise);
}

// Utilisation - ressemble à du code synchrone !
async(function() {
    echo "Démarrage...\n";

    $user = await(fetchUserAsync(1));
    echo "Utilisateur obtenu : {$user['name']}\n";

    $orders = await(fetchOrdersAsync($user['id']));
    echo count($orders) . " commandes obtenues\n";

    return $orders;
});
```

## Exemple Réel : Appels API Parallèles

```php
<?php
async(function() {
    // Démarrer toutes les requêtes en même temps
    $userPromise = fetchUserAsync(1);
    $ordersPromise = fetchOrdersAsync(1);
    $recommendationsPromise = fetchRecommendationsAsync(1);

    // Attendre que toutes se terminent
    $results = await(PromiseUtils::all([
        'user' => $userPromise,
        'orders' => $ordersPromise,
        'recommendations' => $recommendationsPromise,
    ]));

    return $results;
});
```

## Les Grimoires

- [Guzzle Promises](https://github.com/guzzle/promises)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
