---
source_course: "php-async"
source_lesson: "php-async-reactphp-promises"
---

# Promises dans ReactPHP

ReactPHP utilise **intensivement les Promises pour les opérations asynchrones**. Les comprendre est essentiel pour une programmation async efficace.

## Créer des Promises

```php
<?php
require 'vendor/autoload.php';

use React\Promise\Deferred;
use React\Promise\Promise;

// Utiliser Deferred
function fetchDataAsync(): Promise {
    $deferred = new Deferred();

    React\EventLoop\Loop::addTimer(1.0, function () use ($deferred) {
        $deferred->resolve(['data' => 'récupéré']);
    });

    return $deferred->promise();
}

// Utiliser le constructeur de Promise
function fetchData2(): Promise {
    return new Promise(function ($resolve, $reject) {
        React\EventLoop\Loop::addTimer(1.0, function () use ($resolve) {
            $resolve(['data' => 'récupéré']);
        });
    });
}

// Helpers statiques
$resolved = React\Promise\resolve('valeur immédiate');
$rejected = React\Promise\reject(new Exception('erreur'));
```

## Chaînage de Promises

```php
<?php
use React\Promise\Promise;

fetchUser($userId)
    ->then(function ($user) {
        echo "Utilisateur obtenu : {$user['name']}\n";
        return fetchOrders($user['id']);
    })
    ->then(function ($orders) {
        echo count($orders) . " commandes obtenues\n";
        return processOrders($orders);
    })
    ->then(function ($result) {
        echo "Traitement terminé\n";
    })
    ->catch(function (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    })
    ->finally(function () {
        echo "Nettoyage\n";
    });
```

## Combinateurs de Promises

```php
<?php
use React\Promise\Utils;

// all() - Attendre toutes les promises
$promises = [
    fetchUser(1),
    fetchUser(2),
    fetchUser(3),
];

Utils::all($promises)
    ->then(function ($users) {
        foreach ($users as $user) {
            echo "Utilisateur : {$user['name']}\n";
        }
    })
    ->catch(function ($e) {
        echo "Échec : " . $e->getMessage() . "\n";
    });

// race() - Le premier qui se règle gagne
Utils::race([
    fetchFromServer1(),
    fetchFromServer2(),
    fetchFromServer3(),
])->then(function ($result) {
    echo "Première réponse : " . json_encode($result) . "\n";
});

// any() - Le premier qui se résout gagne (ignore les rejets)
Utils::any([
    fetchFromServer1(),  // Peut échouer
    fetchFromServer2(),  // Peut échouer
    fetchFromServer3(),  // Peut réussir
])->then(function ($result) {
    echo "Premier succès : " . json_encode($result) . "\n";
});
```

## Patterns de Gestion des Erreurs

```php
<?php
use React\Promise\Promise;

// Gestion d'erreur par étape
fetchUser($userId)
    ->then(
        function ($user) {
            return fetchOrders($user['id']);
        },
        function ($e) {
            echo "Utilisateur introuvable, utilisation par défaut\n";
            return fetchOrders(0);  // Récupération avec valeur par défaut
        }
    )
    ->then(function ($orders) {
        return processOrders($orders);
    })
    ->catch(function ($e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    });

// Pattern de nouvelle tentative
function withRetry(callable $operation, int $maxRetries = 3): Promise {
    return new Promise(function ($resolve, $reject) use ($operation, $maxRetries) {
        $attempt = function ($remainingRetries) use (&$attempt, $operation, $resolve, $reject) {
            $operation()
                ->then($resolve)
                ->catch(function ($e) use ($attempt, $remainingRetries, $reject) {
                    if ($remainingRetries > 0) {
                        echo "Nouvelle tentative... ({$remainingRetries} restantes)\n";
                        React\EventLoop\Loop::addTimer(1.0, function () use ($attempt, $remainingRetries) {
                            $attempt($remainingRetries - 1);
                        });
                    } else {
                        $reject($e);
                    }
                });
        };

        $attempt($maxRetries);
    });
}

// Pattern timeout
function withTimeout(Promise $promise, float $seconds): Promise {
    return Utils::race([
        $promise,
        new Promise(function ($resolve, $reject) use ($seconds) {
            React\EventLoop\Loop::addTimer($seconds, function () use ($reject) {
                $reject(new TimeoutException("L'opération a expiré"));
            });
        })
    ]);
}

// Utilisation
withTimeout(slowOperation(), 5.0)
    ->then(fn($result) => echo "Succès\n")
    ->catch(fn($e) => echo "Timeout ou échec\n");
```

## Convertir des Callbacks en Promises

```php
<?php
use React\Promise\Deferred;

function readFileAsync(string $path): Promise {
    $deferred = new Deferred();

    $stream = new React\Stream\ReadableResourceStream(
        fopen($path, 'r')
    );

    $content = '';

    $stream->on('data', function ($chunk) use (&$content) {
        $content .= $chunk;
    });

    $stream->on('end', function () use ($deferred, &$content) {
        $deferred->resolve($content);
    });

    $stream->on('error', function ($e) use ($deferred) {
        $deferred->reject($e);
    });

    return $deferred->promise();
}

// Utilisation
readFileAsync('grand-fichier.txt')
    ->then(function ($content) {
        echo "Taille du fichier : " . strlen($content) . " octets\n";
    });
```

## Annulation

```php
<?php
use React\Promise\Promise;
use React\Promise\CancellablePromiseInterface;

function cancellableOperation(): CancellablePromiseInterface {
    $timer = null;

    return new Promise(
        function ($resolve, $reject) use (&$timer) {
            $timer = React\EventLoop\Loop::addTimer(10.0, function () use ($resolve) {
                $resolve('terminé');
            });
        },
        function () use (&$timer) {
            // Fonction d'annulation
            if ($timer !== null) {
                React\EventLoop\Loop::cancelTimer($timer);
            }
        }
    );
}

$promise = cancellableOperation();

// Annuler après 2 secondes
React\EventLoop\Loop::addTimer(2.0, function () use ($promise) {
    $promise->cancel();
    echo "Opération annulée\n";
});
```

## Les Grimoires

- [Documentation ReactPHP Promise](https://reactphp.org/promise/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
