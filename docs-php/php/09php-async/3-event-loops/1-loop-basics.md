---
source_course: "php-async"
source_lesson: "php-async-event-loop-basics"
---

# Comprendre les Boucles d'Événements

La **boucle d'événements** est le cœur de la programmation asynchrone. C'est une construction de programmation qui attend et distribue des événements ou messages dans un programme.

## Qu'est-ce qu'une Boucle d'Événements ?

Une boucle d'événements :

1. Vérifie continuellement les événements en attente (I/O prête, timers expirés, etc.)
2. Distribue les callbacks pour les événements prêts
3. Se répète jusqu'à ce qu'il n'y ait plus de travail

```php
<?php
// Structure conceptuelle de boucle d'événements
function eventLoop(): void {
    while ($hasWorkRemaining) {
        // 1. Attendre les événements (avec timeout)
        $events = waitForEvents($timeout);

        // 2. Traiter les timers
        foreach ($expiredTimers as $timer) {
            $timer->callback();
        }

        // 3. Traiter les événements I/O
        foreach ($events as $event) {
            $event->handler();
        }

        // 4. Traiter les callbacks immédiats
        while ($immediateCallback = $immediateQueue->dequeue()) {
            $immediateCallback();
        }
    }
}
```

## Phases de la Boucle d'Événements

```
   ┌───────────────────────────┐
┌─▶│         Timers            │
│  │  (setTimeout, setInterval)│
│  └─────────────┬─────────────┘
│                │
│  ┌─────────────▼─────────────┐
│  │     Callbacks en Attente  │
│  │  (callbacks I/O différés) │
│  └─────────────┬─────────────┘
│                │
│  ┌─────────────▼─────────────┐
│  │       Sondage (I/O)       │
│  │ (récupérer nouveaux événem)│
│  └─────────────┬─────────────┘
│                │
│  ┌─────────────▼─────────────┐
│  │         Vérification      │
│  │   (callbacks setImmediate)│
│  └─────────────┬─────────────┘
│                │
│  ┌─────────────▼─────────────┐
│  │    Callbacks de Fermeture │
│  │  (nettoyage, fermeture)   │
│  └─────────────┬─────────────┘
│                │
└────────────────┘
```

## Construire une Boucle d'Événements Simple

```php
<?php
class EventLoop {
    /** @var array<int, array{callback: callable, time: float}> */
    private array $timers = [];

    /** @var SplQueue<callable> */
    private SplQueue $deferred;

    /** @var array<int, array{stream: resource, callback: callable}> */
    private array $readers = [];

    private bool $running = false;
    private int $timerId = 0;

    public function __construct() {
        $this->deferred = new SplQueue();
    }

    public function addTimer(float $seconds, callable $callback): int {
        $id = ++$this->timerId;
        $this->timers[$id] = [
            'callback' => $callback,
            'time' => microtime(true) + $seconds
        ];
        return $id;
    }

    public function cancelTimer(int $id): void {
        unset($this->timers[$id]);
    }

    public function defer(callable $callback): void {
        $this->deferred->enqueue($callback);
    }

    public function addReader($stream, callable $callback): void {
        $id = (int) $stream;
        $this->readers[$id] = [
            'stream' => $stream,
            'callback' => $callback
        ];
    }

    public function removeReader($stream): void {
        $id = (int) $stream;
        unset($this->readers[$id]);
    }

    public function run(): void {
        $this->running = true;

        while ($this->running && $this->hasWork()) {
            $this->tick();
        }
    }

    public function stop(): void {
        $this->running = false;
    }

    private function hasWork(): bool {
        return !empty($this->timers)
            || !$this->deferred->isEmpty()
            || !empty($this->readers);
    }

    private function tick(): void {
        // Traiter les callbacks différés
        $count = $this->deferred->count();
        for ($i = 0; $i < $count; $i++) {
            $callback = $this->deferred->dequeue();
            $callback();
        }

        // Traiter les timers
        $now = microtime(true);
        foreach ($this->timers as $id => $timer) {
            if ($timer['time'] <= $now) {
                unset($this->timers[$id]);
                $timer['callback']();
            }
        }

        // Vérifier les flux lisibles
        if (!empty($this->readers)) {
            $this->pollStreams();
        } else {
            // Pas d'I/O, petite pause pour éviter que le CPU tourne à vide
            usleep(1000);
        }
    }

    private function pollStreams(): void {
        $read = array_column($this->readers, 'stream');
        $write = null;
        $except = null;

        // Attendre jusqu'à 10ms pour une activité
        if (stream_select($read, $write, $except, 0, 10000) > 0) {
            foreach ($read as $stream) {
                $id = (int) $stream;
                if (isset($this->readers[$id])) {
                    $this->readers[$id]['callback']($stream);
                }
            }
        }
    }
}
```

## Utiliser la Boucle d'Événements

```php
<?php
$loop = new EventLoop();

// Ajouter un timer
$loop->addTimer(2.0, function() {
    echo "2 secondes écoulées!\n";
});

// Timer périodique (se recrée lui-même)
$periodicCallback = function() use ($loop, &$periodicCallback) {
    echo "Tick à " . date('H:i:s') . "\n";
    $loop->addTimer(1.0, $periodicCallback);
};
$loop->addTimer(1.0, $periodicCallback);

// Arrêter après 5 secondes
$loop->addTimer(5.0, function() use ($loop) {
    echo "Arrêt...\n";
    $loop->stop();
});

// Démarrer la boucle
$loop->run();
echo "Boucle d'événements terminée\n";
```

## I/O Non-Bloquante avec les Flux

```php
<?php
$loop = new EventLoop();

$socket = stream_socket_client(
    'tcp://httpbin.org:80',
    $errno,
    $errstr,
    30,
    STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT
);

stream_set_blocking($socket, false);

$request = "GET /get HTTP/1.1\r\nHost: httpbin.org\r\nConnection: close\r\n\r\n";
fwrite($socket, $request);

$response = '';
$loop->addReader($socket, function($stream) use ($loop, &$response) {
    $chunk = fread($stream, 8192);

    if ($chunk === '' || $chunk === false) {
        $loop->removeReader($stream);
        fclose($stream);
        echo "Réponse reçue : " . strlen($response) . " octets\n";
        $loop->stop();
    } else {
        $response .= $chunk;
    }
});

$loop->addTimer(10.0, function() use ($loop) {
    echo "Timeout!\n";
    $loop->stop();
});

$loop->run();
```

## Concepts Clés

| Concept                  | Description                                                  |
| ------------------------ | ------------------------------------------------------------ |
| **Non-bloquant**         | Les opérations retournent immédiatement, même si incomplètes |
| **Callback**             | Fonction appelée quand un événement se produit               |
| **Sondage**              | Vérifier si les événements sont prêts (`stream_select`)      |
| **Dispatch d'événement** | Exécuter les callbacks pour les événements prêts             |
| **Tick**                 | Une itération de la boucle d'événements                      |

## Les Grimoires

- [Manuel PHP - stream_select](https://www.php.net/manual/en/function.stream-select.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
