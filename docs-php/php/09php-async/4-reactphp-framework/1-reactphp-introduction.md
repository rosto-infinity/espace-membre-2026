---
source_course: "php-async"
source_lesson: "php-async-reactphp-introduction"
---

# Introduction à ReactPHP

ReactPHP est une **bibliothèque bas niveau pour la programmation événementielle** en PHP. Elle fournit les composants fondamentaux pour construire des applications non-bloquantes et asynchrones.

## Qu'est-ce que ReactPHP ?

ReactPHP n'est pas un framework — c'est une collection de composants qui fonctionnent ensemble :

- **EventLoop** : Le composant central qui drive tout
- **Stream** : Flux I/O non-bloquants
- **Promise** : Gestion des résultats async
- **Socket** : Client et serveur TCP/UDP
- **HTTP** : Client et serveur HTTP async
- **DNS** : Résolveur DNS async
- **Child Process** : Exécution de processus non-bloquante

## Installation

```bash
composer require react/event-loop react/http react/socket
```

## La Boucle d'Événements

La boucle d'événements est le cœur de ReactPHP. Toutes les opérations async y sont enregistrées :

```php
<?php
require 'vendor/autoload.php';

use React\EventLoop\Loop;

// Ajouter un timer
Loop::addTimer(2.0, function () {
    echo "2 secondes écoulées!\n";
});

// Ajouter un timer périodique
$counter = 0;
Loop::addPeriodicTimer(1.0, function () use (&$counter) {
    $counter++;
    echo "Tick {$counter}\n";

    if ($counter >= 5) {
        Loop::stop();
    }
});

echo "Démarrage de la boucle d'événements...\n";

Loop::run();

echo "Boucle d'événements terminée\n";
```

## Méthodes de la Boucle d'Événements

| Méthode                                        | Description                               |
| ---------------------------------------------- | ----------------------------------------- |
| `Loop::addTimer($seconds, $callback)`          | Exécuter le callback une fois après délai |
| `Loop::addPeriodicTimer($interval, $callback)` | Exécuter le callback de façon répétée     |
| `Loop::cancelTimer($timer)`                    | Annuler un timer planifié                 |
| `Loop::addReadStream($stream, $callback)`      | Surveiller un flux pour lecture           |
| `Loop::addWriteStream($stream, $callback)`     | Surveiller un flux pour écriture          |
| `Loop::removeReadStream($stream)`              | Arrêter de surveiller les lectures        |
| `Loop::removeWriteStream($stream)`             | Arrêter de surveiller les écritures       |
| `Loop::futureTick($callback)`                  | Exécuter le callback au prochain tick     |
| `Loop::run()`                                  | Démarrer la boucle d'événements           |
| `Loop::stop()`                                 | Arrêter la boucle d'événements            |

## Client HTTP Asynchrone

```php
<?php
require 'vendor/autoload.php';

use React\Http\Browser;
use React\EventLoop\Loop;

$browser = new Browser();

// Requête unique
$browser->get('https://httpbin.org/get')
    ->then(function (Psr\Http\Message\ResponseInterface $response) {
        echo 'Statut : ' . $response->getStatusCode() . "\n";
        echo 'Corps : ' . $response->getBody() . "\n";
    })
    ->catch(function (Exception $e) {
        echo 'Erreur : ' . $e->getMessage() . "\n";
    });

echo "Requête envoyée (non-bloquante)\n";
```

## Requêtes Concurrentes

```php
<?php
require 'vendor/autoload.php';

use React\Http\Browser;
use React\Promise\Utils;

$browser = new Browser();

$urls = [
    'https://httpbin.org/delay/2',
    'https://httpbin.org/delay/1',
    'https://httpbin.org/delay/3',
];

$startTime = microtime(true);

// Démarrer toutes les requêtes en simultané
$promises = array_map(
    fn($url) => $browser->get($url),
    $urls
);

// Attendre que toutes se terminent
Utils::all($promises)
    ->then(function ($responses) use ($startTime) {
        $elapsed = microtime(true) - $startTime;

        echo count($responses) . " requêtes terminées\n";
        echo "Temps total : " . round($elapsed, 2) . "s\n";
        // En séquentiel : ~6 secondes
        // En concurrent : ~3 secondes (délai le plus long)
    })
    ->catch(function (Exception $e) {
        echo "Erreur : " . $e->getMessage() . "\n";
    });
```

## Créer un Serveur HTTP

```php
<?php
require 'vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use React\Socket\SocketServer;
use Psr\Http\Message\ServerRequestInterface;

$server = new HttpServer(function (ServerRequestInterface $request) {
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();

    return match (true) {
        $path === '/' => new Response(
            200,
            ['Content-Type' => 'text/html'],
            '<h1>Bienvenue sur ReactPHP!</h1>'
        ),
        $path === '/api/time' => new Response(
            200,
            ['Content-Type' => 'application/json'],
            json_encode(['time' => date('c')])
        ),
        default => new Response(
            404,
            ['Content-Type' => 'text/plain'],
            'Page introuvable'
        )
    };
});

$socket = new SocketServer('0.0.0.0:8080');
$server->listen($socket);

echo "Serveur sur http://localhost:8080\n";
```

## Requêtes BDD Asynchrones

```php
<?php
require 'vendor/autoload.php';

use React\MySQL\Factory;
use React\MySQL\QueryResult;

$factory = new Factory();

$connection = $factory->createLazyConnection('user:pass@localhost/database');

$connection->query('SELECT * FROM users WHERE active = ?', [1])
    ->then(function (QueryResult $result) {
        echo count($result->resultRows) . " utilisateurs trouvés:\n";
        foreach ($result->resultRows as $row) {
            echo "- {$row['name']}\n";
        }
    })
    ->catch(function (Exception $e) {
        echo "Requête échouée : " . $e->getMessage() . "\n";
    })
    ->finally(function () use ($connection) {
        $connection->quit();
    });
```

## Les Grimoires

- [Documentation ReactPHP (Officielle)](https://reactphp.org/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
