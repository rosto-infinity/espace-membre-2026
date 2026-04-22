---
source_course: "php-async"
source_lesson: "php-async-stream-functions"
---

# Fonctions de Flux PHP pour l'I/O Asynchrone

Les **fonctions de flux de PHP sont le fondement de l'I/O non-bloquante**. Les comprendre est essentiel pour la programmation async.

## Fonctions de Flux Essentielles

### stream_set_blocking()

Contrôle si les opérations sur un flux bloquent :

```php
<?php
// Mode bloquant (par défaut)
$fp = fopen('large_file.txt', 'r');
$data = fread($fp, 1000000);  // Attend que les données soient lues

// Mode non-bloquant
$fp = fopen('large_file.txt', 'r');
stream_set_blocking($fp, false);
$data = fread($fp, 1000000);  // Retourne immédiatement
// $data peut contenir moins que demandé (ou chaîne vide)
```

### stream_select()

Le cœur du multiplexage I/O — attend que plusieurs flux soient prêts :

```php
<?php
/**
 * stream_select(
 *     ?array &$read,    // Flux à vérifier pour la lecture
 *     ?array &$write,   // Flux à vérifier pour l'écriture
 *     ?array &$except,  // Flux à vérifier pour les exceptions
 *     ?int $seconds,    // Timeout secondes (null = bloquer indéfiniment)
 *     int $microseconds // Timeout microsecondes
 * ): int|false         // Nombre de flux prêts
 */

// Exemple : Attendre plusieurs sockets
$sockets = [
    stream_socket_client('tcp://api1.exemple.com:80'),
    stream_socket_client('tcp://api2.exemple.com:80'),
    stream_socket_client('tcp://api3.exemple.com:80'),
];

foreach ($sockets as $socket) {
    stream_set_blocking($socket, false);
    fwrite($socket, "GET / HTTP/1.0\r\nHost: exemple.com\r\n\r\n");
}

// Sonder jusqu'à ce que toutes les réponses soient reçues
$responses = array_fill(0, count($sockets), '');
$active = $sockets;

while (!empty($active)) {
    $read = $active;
    $write = null;
    $except = null;

    $ready = stream_select($read, $write, $except, 1);

    if ($ready === false) {
        throw new RuntimeException('stream_select a échoué');
    }

    if ($ready === 0) {
        echo "Timeout, vérification suivante...\n";
        continue;
    }

    foreach ($read as $socket) {
        $index = array_search($socket, $sockets, true);
        $chunk = fread($socket, 8192);

        if ($chunk === '' || $chunk === false) {
            fclose($socket);
            unset($active[array_search($socket, $active, true)]);
            echo "Socket {$index} terminé: " . strlen($responses[$index]) . " octets\n";
        } else {
            $responses[$index] .= $chunk;
        }
    }
}

echo "Toutes les réponses reçues!\n";
```

### stream_socket_client()

Crée des connexions client avec support asynchrone :

```php
<?php
// Connexion synchrone
$sync = stream_socket_client('tcp://exemple.com:80');

// Connexion asynchrone (retourne immédiatement)
$async = stream_socket_client(
    'tcp://exemple.com:80',
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
);

// Vérifier si la connexion est prête
stream_set_blocking($async, false);
$write = [$async];
$read = null;
$except = null;

if (stream_select($read, $write, $except, 5) > 0) {
    // Connexion établie, on peut envoyer des données
    fwrite($async, "GET / HTTP/1.0\r\n\r\n");
}
```

### stream_socket_server()

Crée des serveurs pouvant accepter plusieurs connexions :

```php
<?php
$server = stream_socket_server(
    'tcp://0.0.0.0:8080',
    $errno,
    $errstr,
    STREAM_SERVER_BIND | STREAM_SERVER_LISTEN
);

stream_set_blocking($server, false);

$clients = [];

while (true) {
    $read = array_merge([$server], $clients);
    $write = null;
    $except = null;

    if (stream_select($read, $write, $except, 1) > 0) {
        // Nouvelle connexion ?
        if (in_array($server, $read, true)) {
            $client = stream_socket_accept($server, 0);
            if ($client) {
                stream_set_blocking($client, false);
                $clients[(int)$client] = $client;
                echo "Nouveau client connecté\n";
            }
        }

        // Données des clients existants ?
        foreach ($read as $stream) {
            if ($stream === $server) continue;

            $data = fread($stream, 1024);
            if ($data === '' || $data === false) {
                unset($clients[(int)$stream]);
                fclose($stream);
                echo "Client déconnecté\n";
            } else {
                fwrite($stream, "Écho : {$data}");
            }
        }
    }
}
```

## Contextes de Flux

```php
<?php
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => json_encode(['key' => 'value']),
        'timeout' => 10.0,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$response = file_get_contents('https://api.exemple.com/data', false, $context);
```

## Métadonnées de Flux

```php
<?php
$stream = fopen('http://exemple.com', 'r');

$meta = stream_get_meta_data($stream);
print_r($meta);

if ($meta['timed_out']) {
    echo "Connexion expirée!\n";
}

if ($meta['eof']) {
    echo "Fin du flux atteinte\n";
}
```

## Enveloppe avec Timeout

```php
<?php
function readWithTimeout($stream, int $bytes, float $timeout): string|false {
    $start = microtime(true);
    $data = '';

    stream_set_blocking($stream, false);

    while (strlen($data) < $bytes) {
        $elapsed = microtime(true) - $start;
        if ($elapsed >= $timeout) {
            return strlen($data) > 0 ? $data : false;
        }

        $remaining = $timeout - $elapsed;
        $seconds = (int) $remaining;
        $microseconds = (int) (($remaining - $seconds) * 1000000);

        $read = [$stream];
        $write = null;
        $except = null;

        if (stream_select($read, $write, $except, $seconds, $microseconds) > 0) {
            $chunk = fread($stream, $bytes - strlen($data));
            if ($chunk === '' || $chunk === false) {
                break;
            }
            $data .= $chunk;
        }
    }

    return $data;
}
```

## Les Grimoires

- [Manuel PHP - Flux](https://www.php.net/manual/en/book.stream.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
