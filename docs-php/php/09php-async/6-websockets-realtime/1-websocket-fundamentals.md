---
source_course: "php-async"
source_lesson: "php-async-websocket-fundamentals"
---

# Fondamentaux des WebSockets

Les WebSockets permettent des **connexions persistantes bidirectionnelles** entre clients et serveurs — essentielles pour les applications temps réel.

## HTTP vs WebSocket

```
HTTP (Requête-Réponse) :
Client ──requête──▶ Serveur
Client ◀──réponse── Serveur
(connexion fermée)

WebSocket (Persistant, Bidirectionnel) :
Client ◀═══════════▶ Serveur
(connexion reste ouverte)
(l'un ou l'autre peut envoyer à tout moment)
```

## Handshake WebSocket

Les connexions WebSocket commencent par une requête HTTP de mise à niveau :

```
GET /chat HTTP/1.1
Host: serveur.exemple.com
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==
Sec-WebSocket-Version: 13
```

Le serveur répond :

```
HTTP/1.1 101 Switching Protocols
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Accept: s3pPLMBiTxaQ9kYGzzhZRbK+xOo=
```

## Serveur WebSocket avec Ratchet

Ratchet est la bibliothèque PHP WebSocket la plus populaire :

```bash
composer require cboden/ratchet
```

```php
<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected \SplObjectStorage $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
        echo "Serveur de chat démarré\n";
    }

    public function onOpen(ConnectionInterface $conn): void {
        $this->clients->attach($conn);
        echo "Nouvelle connexion : {$conn->resourceId}\n";

        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => 'Connecté au serveur de chat',
            'clientId' => $conn->resourceId
        ]));
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);

        echo "Message de {$from->resourceId}: {$msg}\n";

        // Diffuser à tous les autres clients
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode([
                    'type' => 'message',
                    'from' => $from->resourceId,
                    'content' => $data['content'] ?? $msg,
                    'timestamp' => time()
                ]));
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        $this->clients->detach($conn);
        echo "Connexion {$conn->resourceId} déconnectée\n";

        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'type' => 'userLeft',
                'clientId' => $conn->resourceId
            ]));
        }
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        echo "Erreur : {$e->getMessage()}\n";
        $conn->close();
    }
}

// Lancer le serveur
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatServer()
        )
    ),
    8080
);

echo "Serveur WebSocket sur le port 8080\n";
$server->run();
```

## Client JavaScript

```javascript
const ws = new WebSocket("ws://localhost:8080");

ws.onopen = () => {
  console.log("Connecté");
  ws.send(JSON.stringify({ content: "Bonjour, serveur!" }));
};

ws.onmessage = (event) => {
  const data = JSON.parse(event.data);
  console.log("Reçu :", data);
};

ws.onclose = () => {
  console.log("Déconnecté");
};

ws.onerror = (error) => {
  console.error("Erreur WebSocket :", error);
};
```

## Ajouter des Salons/Canaux

```php
<?php
class RoomChatServer implements MessageComponentInterface {
    protected \SplObjectStorage $clients;
    protected array $rooms = [];

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        $data = json_decode($msg, true);

        switch ($data['action'] ?? '') {
            case 'join':
                $this->joinRoom($from, $data['room']);
                break;

            case 'leave':
                $this->leaveRoom($from, $data['room']);
                break;

            case 'message':
                $this->broadcastToRoom(
                    $data['room'],
                    $from,
                    $data['content']
                );
                break;
        }
    }

    private function joinRoom(ConnectionInterface $conn, string $room): void {
        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = new \SplObjectStorage();
        }

        $this->rooms[$room]->attach($conn);

        $conn->send(json_encode([
            'type' => 'joined',
            'room' => $room,
            'members' => $this->rooms[$room]->count()
        ]));
    }

    private function broadcastToRoom(
        string $room,
        ConnectionInterface $from,
        string $content
    ): void {
        if (!isset($this->rooms[$room])) {
            return;
        }

        $message = json_encode([
            'type' => 'message',
            'room' => $room,
            'from' => $from->resourceId,
            'content' => $content,
            'timestamp' => time()
        ]);

        foreach ($this->rooms[$room] as $client) {
            $client->send($message);
        }
    }
}
```

## WebSocket avec Authentification

```php
<?php
use Ratchet\ConnectionInterface;
use Ratchet\WebSocket\WsServerInterface;

class AuthenticatedServer implements MessageComponentInterface, WsServerInterface {
    public function onOpen(ConnectionInterface $conn): void {
        $queryString = $conn->httpRequest->getUri()->getQuery();
        parse_str($queryString, $params);

        $token = $params['token'] ?? null;

        if (!$this->validateToken($token)) {
            $conn->send(json_encode(['error' => 'Non autorisé']));
            $conn->close();
            return;
        }

        $conn->user = $this->getUserFromToken($token);
        $this->clients->attach($conn);
    }

    private function validateToken(?string $token): bool {
        if ($token === null) {
            return false;
        }
        return true;
    }

    public function getSubProtocols(): array {
        return [];
    }
}
```

## Les Grimoires

- [Documentation Ratchet](http://socketo.me/docs/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
