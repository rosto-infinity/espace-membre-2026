---
source_course: "php-async"
source_lesson: "php-async-reactphp-streams"
---

# Travailler avec les Flux ReactPHP

Les **flux sont l'épine dorsale de l'I/O dans ReactPHP**. Ils fournissent une interface cohérente pour lire et écrire des données de manière asynchrone.

## Interfaces de Flux

ReactPHP définit plusieurs interfaces de flux :

| Interface                 | Description                            |
| ------------------------- | -------------------------------------- |
| `ReadableStreamInterface` | Peut émettre des événements de données |
| `WritableStreamInterface` | Peut recevoir des données via write()  |
| `DuplexStreamInterface`   | Lisible et inscriptible                |

## Lire depuis des Flux

```php
<?php
require 'vendor/autoload.php';

use React\Stream\ReadableResourceStream;
use React\EventLoop\Loop;

// Créer un flux lisible depuis STDIN
$stream = new ReadableResourceStream(STDIN);

echo "Tapez quelque chose (Ctrl+D pour terminer):\n";

// Gérer les données entrantes
$stream->on('data', function (string $data) {
    echo "Reçu : " . trim($data) . "\n";
});

// Gérer la fin du flux
$stream->on('end', function () {
    echo "Flux terminé\n";
});

// Gérer les erreurs
$stream->on('error', function (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
});

// Gérer la fermeture
$stream->on('close', function () {
    echo "Flux fermé\n";
});
```

## Écrire dans des Flux

```php
<?php
require 'vendor/autoload.php';

use React\Stream\WritableResourceStream;

$file = fopen('sortie.txt', 'w');
$stream = new WritableResourceStream($file);

$stream->write("Ligne 1\n");
$stream->write("Ligne 2\n");
$stream->write("Ligne 3\n");

// Terminer le flux (écrit les données finales et ferme)
$stream->end("Dernière ligne\n");

$stream->on('drain', function () {
    echo "Tampon vidé, prêt pour plus de données\n";
});

$stream->on('close', function () {
    echo "Fichier écrit et fermé\n";
});
```

## Flux Duplex

Les flux duplex combinent lecture et écriture :

```php
<?php
require 'vendor/autoload.php';

use React\Socket\Connector;

$connector = new Connector();

$connector->connect('tcp://echo.exemple.com:7')
    ->then(function (React\Socket\ConnectionInterface $connection) {
        // La connexion est un flux duplex

        $connection->on('data', function (string $data) {
            echo "Reçu : {$data}";
        });

        $connection->write("Bonjour, serveur!\n");

        React\EventLoop\Loop::addTimer(5.0, function () use ($connection) {
            $connection->end();
        });
    });
```

## Piping des Flux

Diriger les données d'un flux vers un autre :

```php
<?php
require 'vendor/autoload.php';

use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use React\Stream\ThroughStream;

// Fichier source
$source = new ReadableResourceStream(fopen('entree.txt', 'r'));

// Flux de transformation (majuscules)
$transform = new ThroughStream(function (string $data) {
    return strtoupper($data);
});

// Fichier de destination
$destination = new WritableResourceStream(fopen('sortie.txt', 'w'));

// Pipe : source -> transformation -> destination
$source->pipe($transform)->pipe($destination);

$destination->on('close', function () {
    echo "Transformation du fichier terminée\n";
});
```

## Flux Through pour Transformation

```php
<?php
require 'vendor/autoload.php';

use React\Stream\ThroughStream;

// Parseur de lignes JSON
$jsonParser = new ThroughStream(function (string $data) {
    $lines = explode("\n", $data);
    $objects = [];

    foreach ($lines as $line) {
        if (trim($line) !== '') {
            $objects[] = json_decode($line, true);
        }
    }

    return $objects;
});

// Flux de compression
$compressor = new ThroughStream(function (string $data) {
    return gzcompress($data);
});

// Limiteur de débit (avec état)
class RateLimitStream extends ThroughStream {
    private int $bytesThisSecond = 0;
    private int $maxBytesPerSecond;

    public function __construct(int $maxBytesPerSecond) {
        $this->maxBytesPerSecond = $maxBytesPerSecond;

        React\EventLoop\Loop::addPeriodicTimer(1.0, function () {
            $this->bytesThisSecond = 0;
        });

        parent::__construct(function (string $data) {
            $this->bytesThisSecond += strlen($data);

            if ($this->bytesThisSecond > $this->maxBytesPerSecond) {
                return '';
            }

            return $data;
        });
    }
}
```

## Contre-Pression (Back Pressure)

Gérer les consommateurs lents :

```php
<?php
$readable->on('data', function (string $data) use ($writable, $readable) {
    // write() retourne false si le tampon est plein
    $canContinue = $writable->write($data);

    if (!$canContinue) {
        // Pause de la lecture jusqu'au vidage du tampon
        $readable->pause();
    }
});

$writable->on('drain', function () use ($readable) {
    // Tampon vidé, reprendre la lecture
    $readable->resume();
});

// Ou simplement utiliser pipe() qui gère cela automatiquement
$readable->pipe($writable);
```

## Les Grimoires

- [Documentation ReactPHP Stream](https://reactphp.org/stream/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
