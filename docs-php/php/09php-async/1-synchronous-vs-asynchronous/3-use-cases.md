---
source_course: "php-async"
source_lesson: "php-async-async-use-cases"
---

# Cas d'Usage Réels de l'Async

Comprendre **quand** utiliser la programmation asynchrone est aussi important que savoir comment. Explorons des scénarios pratiques où PHP async apporte des bénéfices significatifs.

## Cas d'Usage 1 : Agrégation d'API

Les applications modernes doivent souvent combiner des données de plusieurs sources :

```php
<?php
// AVANT : Appels API séquentiels
class DashboardService {
    public function getDashboardData(int $userId): array {
        // Chaque appel bloque jusqu'à la fin
        $user = $this->userApi->getUser($userId);           // 200ms
        $orders = $this->orderApi->getOrders($userId);      // 300ms
        $notifications = $this->notificationApi->get($userId); // 150ms
        $recommendations = $this->recApi->get($userId);     // 400ms

        return compact('user', 'orders', 'notifications', 'recommendations');
        // Total : ~1050ms
    }
}

// APRÈS : Appels API concurrents
class AsyncDashboardService {
    public function getDashboardData(int $userId): array {
        // Toutes les requêtes démarrent immédiatement
        $promises = [
            'user' => $this->userApi->getUserAsync($userId),
            'orders' => $this->orderApi->getOrdersAsync($userId),
            'notifications' => $this->notificationApi->getAsync($userId),
            'recommendations' => $this->recApi->getAsync($userId),
        ];

        // Attendre que toutes se terminent
        return Promise\all($promises)->wait();
        // Total : ~400ms (requête la plus lente)
    }
}
```

Résultat : **60% plus rapide** pour le chargement du tableau de bord.

## Cas d'Usage 2 : Traitement des Webhooks

Quand un événement se produit, vous devrez peut-être notifier plusieurs services externes :

```php
<?php
// Distributeur de webhooks asynchrone
class WebhookDispatcher {
    public function dispatch(Event $event, array $endpoints): void {
        $payload = json_encode($event->toArray());

        // Envoyer tous les webhooks en simultané
        $promises = [];
        foreach ($endpoints as $endpoint) {
            $promises[] = $this->httpClient->postAsync(
                $endpoint->url,
                ['body' => $payload, 'timeout' => 5]
            );
        }

        // Ne pas attendre les réponses (fire-and-forget)
        // Ou attendre avec timeout pour l'accusé de réception
        Promise\settle($promises)->wait();
    }
}
```

## Cas d'Usage 3 : Application de Chat en Temps Réel

```php
<?php
// Serveur de chat WebSocket avec ReactPHP
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class ChatServer implements MessageComponentInterface {
    protected \SplObjectStorage $clients;

    public function __construct() {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn): void {
        $this->clients->attach($conn);
        echo "Nouvelle connexion : {$conn->resourceId}\n";
    }

    public function onMessage(ConnectionInterface $from, $msg): void {
        // Diffuser à tous les clients connectés
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn): void {
        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e): void {
        $conn->close();
    }
}
```

## Cas d'Usage 4 : Traitement en File d'Attente

```php
<?php
// Worker de file d'attente asynchrone
class AsyncWorker {
    public function run(): void {
        $loop = React\EventLoop\Loop::get();

        // Vérifier les jobs toutes les 100ms
        $loop->addPeriodicTimer(0.1, function() {
            $job = $this->queue->pop();

            if ($job) {
                $this->processAsync($job);
            }
        });

        // Traiter plusieurs jobs en simultané
        $loop->run();
    }

    private function processAsync(Job $job): void {
        // Démarrer le job sans bloquer
        $this->executor->executeAsync($job)
            ->then(
                fn($result) => $this->onSuccess($job, $result),
                fn($error) => $this->onFailure($job, $error)
            );
    }
}
```

## Cas d'Usage 5 : Scraping Web

```php
<?php
// Scraper web concurrent
class WebScraper {
    public function scrapeUrls(array $urls): array {
        $promises = [];

        foreach ($urls as $url) {
            $promises[$url] = $this->httpClient->getAsync($url)
                ->then(function ($response) use ($url) {
                    return [
                        'url' => $url,
                        'status' => $response->getStatusCode(),
                        'content' => $this->parseContent($response->getBody())
                    ];
                })
                ->otherwise(function ($error) use ($url) {
                    return [
                        'url' => $url,
                        'error' => $error->getMessage()
                    ];
                });
        }

        return Promise\settle($promises)->wait();
    }
}

// Scraper 100 pages en simultané (avec pool de connexions)
$scraper = new WebScraper();
$results = $scraper->scrapeUrls($centUrls);
// Prend des secondes au lieu de minutes
```

## Quand NE PAS Utiliser l'Async

❌ **Opérations CRUD simples** : La surcharge ne vaut pas la peine
❌ **Dépendances séquentielles** : Quand chaque étape a besoin du résultat précédent
❌ **Tâches CPU-intensives** : L'async n'aide pas le travail CPU-bound
❌ **Scripts courts** : La surcharge de configuration dépasse les bénéfices
❌ **Requêtes web traditionnelles** : Le cycle requête/réponse est déjà assez rapide

## Framework de Décision

```
Dois-je utiliser l'async ?
        │
        ▼
┌─────────────────────────────┐
│ Plusieurs opérations I/O    │──Non──▶ Utiliser le synchrone
│ indépendantes ?             │
└─────────────────────────────┘
        │ Oui
        ▼
┌─────────────────────────────┐
│ Opérations > 100ms chacune  │──Non──▶ Probablement pas nécessaire
│ et il y en a 3 ou plus ?    │
└─────────────────────────────┘
        │ Oui
        ▼
┌─────────────────────────────┐
│ La performance est critique │──Non──▶ Considérer la lisibilité
│ pour cette fonctionnalité ? │
└─────────────────────────────┘
        │ Oui
        ▼
    Utiliser l'async ! ✅
```

## Les Grimoires

- [ReactPHP - PHP Événementiel](https://reactphp.org/)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
