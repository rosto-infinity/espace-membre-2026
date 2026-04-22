---
source_course: "php-async"
source_lesson: "php-async-realtime-patterns"
---

# Patterns d'Applications Temps Réel

Au-delà de la messagerie basique, les **applications temps réel nécessitent une architecture soignée** pour la scalabilité et la fiabilité.

## Pattern Pub/Sub avec Redis

```php
<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class PubSubServer implements MessageComponentInterface {
    protected \SplObjectStorage $clients;
    protected array $subscriptions = [];
    protected \Redis $redis;

    public function __construct() {
        $this->clients = new \SplObjectStorage();

        $this->redis = new \Redis();
        $this->redis->connect('localhost', 6379);

        $this->startRedisSubscriber();
    }

    private function startRedisSubscriber(): void {
        React\EventLoop\Loop::addPeriodicTimer(0.1, function () {
            $message = $this->redis->lpop('ws:messages');
            if ($message) {
                $data = json_decode($message, true);
                $this->broadcastToChannel($data['channel'], $data['message']);
            }
        });
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);

        switch ($data['action']) {
            case 'subscribe':
                $this->subscribe($from, $data['channel']);
                break;

            case 'unsubscribe':
                $this->unsubscribe($from, $data['channel']);
                break;

            case 'publish':
                $this->redis->rpush('ws:messages', json_encode([
                    'channel' => $data['channel'],
                    'message' => $data['message']
                ]));
                break;
        }
    }

    private function subscribe(ConnectionInterface $conn, string $channel): void {
        if (!isset($this->subscriptions[$channel])) {
            $this->subscriptions[$channel] = new \SplObjectStorage();
        }
        $this->subscriptions[$channel]->attach($conn);

        $conn->send(json_encode([
            'type' => 'subscribed',
            'channel' => $channel
        ]));
    }

    private function broadcastToChannel(string $channel, $message): void {
        if (!isset($this->subscriptions[$channel])) {
            return;
        }

        $payload = json_encode([
            'type' => 'message',
            'channel' => $channel,
            'data' => $message,
            'timestamp' => microtime(true)
        ]);

        foreach ($this->subscriptions[$channel] as $client) {
            $client->send($payload);
        }
    }
}
```

## Système de Présence

```php
<?php
class PresenceChannel {
    private array $members = [];
    private $redis;

    public function join(
        string $channel,
        ConnectionInterface $conn,
        array $userData
    ): void {
        $userId = $userData['id'];

        if (!isset($this->members[$channel])) {
            $this->members[$channel] = [];
        }
        $this->members[$channel][$userId] = [
            'connection' => $conn,
            'data' => $userData
        ];

        $this->redis->hSet(
            "presence:{$channel}",
            $userId,
            json_encode($userData)
        );

        // Notifier les autres membres
        $this->broadcast($channel, [
            'type' => 'member_joined',
            'member' => $userData
        ], $conn);

        // Envoyer les membres actuels au nouvel utilisateur
        $conn->send(json_encode([
            'type' => 'presence_state',
            'members' => $this->getMembers($channel)
        ]));
    }

    public function leave(string $channel, ConnectionInterface $conn): void {
        $userId = null;

        foreach ($this->members[$channel] ?? [] as $id => $member) {
            if ($member['connection'] === $conn) {
                $userId = $id;
                break;
            }
        }

        if ($userId) {
            $userData = $this->members[$channel][$userId]['data'];
            unset($this->members[$channel][$userId]);

            $this->redis->hDel("presence:{$channel}", $userId);

            $this->broadcast($channel, [
                'type' => 'member_left',
                'member' => $userData
            ]);
        }
    }

    public function getMembers(string $channel): array {
        $members = $this->redis->hGetAll("presence:{$channel}");
        return array_map('json_decode', $members);
    }
}
```

## Limitation du Débit

```php
<?php
class RateLimitedServer implements MessageComponentInterface {
    private array $messageCounts = [];
    private array $lastReset = [];

    private const MAX_MESSAGES_PER_SECOND = 10;

    public function onMessage(ConnectionInterface $from, $msg): void {
        $id = $from->resourceId;
        $now = time();

        // Réinitialiser le compteur chaque seconde
        if (!isset($this->lastReset[$id]) || $this->lastReset[$id] < $now) {
            $this->messageCounts[$id] = 0;
            $this->lastReset[$id] = $now;
        }

        $this->messageCounts[$id]++;

        if ($this->messageCounts[$id] > self::MAX_MESSAGES_PER_SECOND) {
            $from->send(json_encode([
                'type' => 'error',
                'code' => 'RATE_LIMITED',
                'message' => 'Trop de messages. Veuillez ralentir.'
            ]));
            return;
        }

        $this->handleMessage($from, $msg);
    }
}
```

## Heartbeat/Keepalive

```php
<?php
class HeartbeatServer implements MessageComponentInterface {
    private array $lastPing = [];
    private const PING_INTERVAL = 30;
    private const PONG_TIMEOUT = 10;

    public function __construct() {
        React\EventLoop\Loop::addPeriodicTimer(5.0, function () {
            $this->checkConnections();
        });
    }

    private function checkConnections(): void {
        $now = time();

        foreach ($this->clients as $client) {
            $lastPing = $this->lastPing[$client->resourceId] ?? 0;

            if ($now - $lastPing > self::PING_INTERVAL) {
                $client->send(json_encode(['type' => 'ping']));
                $this->lastPing[$client->resourceId] = $now;
            }

            $lastPong = $client->lastPong ?? $now;
            if ($now - $lastPong > self::PING_INTERVAL + self::PONG_TIMEOUT) {
                echo "Client {$client->resourceId} a expiré\n";
                $client->close();
            }
        }
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);

        if (($data['type'] ?? '') === 'pong') {
            $from->lastPong = time();
            return;
        }
    }
}
```

## Mise à l'Échelle des Serveurs WebSocket

```
                    ┌─────────────────┐
                    │ Load Balancer   │
                    │(sessions collantes)│
                    └────────┬────────┘
           ┌─────────────────┼─────────────────┐
           │                 │                 │
    ┌──────▼─────┐    ┌──────▼─────┐    ┌──────▼─────┐
    │  WS Serveur│    │  WS Serveur│    │  WS Serveur│
    │     #1     │    │     #2     │    │     #3     │
    └──────┬─────┘    └──────┬─────┘    └──────┬─────┘
           │                 │                 │
           └─────────────────┼─────────────────┘
                             │
                    ┌────────▼────────┐
                    │  Redis Pub/Sub  │
                    │  (bus messages) │
                    └─────────────────┘
```

Considérations clés :

- Utiliser des sessions collantes pour que les clients restent sur le même serveur
- Utiliser Redis Pub/Sub pour diffuser les messages entre serveurs
- Stocker la présence/l'état dans Redis pour la cohérence
- Implémenter la logique de reconnexion côté client

## Les Grimoires

- [Manuel PHP - Extension Redis](https://www.php.net/manual/en/book.redis.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
