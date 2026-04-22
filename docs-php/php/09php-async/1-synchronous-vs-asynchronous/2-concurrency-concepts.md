---
source_course: "php-async"
source_lesson: "php-async-concurrency-concepts"
---

# Concurrence, Parallélisme et Async

Ces termes sont souvent confondus, mais **comprendre leurs différences est essentiel** pour écrire des applications PHP efficaces.

## Concurrence vs Parallélisme

Ces concepts sont liés mais distincts :

**Concurrence** : Gérer plusieurs tâches qui peuvent progresser dans le temps. Les tâches n'ont pas besoin de s'exécuter simultanément — elles se relaient.

**Parallélisme** : Exécuter réellement plusieurs tâches exactement au même moment, nécessitant plusieurs cœurs CPU.

## L'Analogie du Café

Imaginez un café :

**Séquentiel (Pas de Concurrence)** :

- Un barista gère tout
- Prend la commande → Prépare la boisson → Sert → Prend la commande suivante
- Chaque client attend tous les clients précédents

**Concurrent (Un Seul Barista)** :

- Un barista, mais plus intelligent
- Prend une commande → Lance le café → Prend la commande suivante en attendant
- Plusieurs commandes en cours, une personne alternant entre les tâches

**Parallèle (Plusieurs Baristas)** :

- Plusieurs baristas travaillent simultanément
- Chacun gère son propre client
- Vrai travail simultané

## Les Options de PHP

```php
<?php
// Séquentiel - ce que vous écrivez normalement
function fetchSequentially(): array {
    $result1 = slowOperation1();  // Attendre...
    $result2 = slowOperation2();  // Attendre...
    $result3 = slowOperation3();  // Attendre...

    return [$result1, $result2, $result3];
}

// Temps total = somme de toutes les opérations
```

### Atteindre la Concurrence en PHP

PHP offre plusieurs mécanismes :

| Mécanisme        | Type                                | Cas d'Usage                 |
| ---------------- | ----------------------------------- | --------------------------- |
| **Fibers**       | Concurrence coopérative             | Opérations I/O-bound        |
| **pcntl_fork()** | Parallélisme basé sur les processus | Tâches CPU-bound ou isolées |
| **ext-parallel** | Vrai parallélisme                   | Calcul intensif             |
| **ReactPHP/Amp** | Concurrence événementielle          | Applications réseau         |

## Comprendre les Boucles d'Événements

Au cœur de PHP asynchrone se trouve la **boucle d'événements** — une construction de programmation qui attend et distribue les événements.

```php
<?php
// Boucle d'événements conceptuelle
while ($running) {
    // Vérifier les opérations I/O terminées
    $events = checkForReadyEvents();

    // Gérer chaque événement prêt
    foreach ($events as $event) {
        $event->callback();
    }

    // Brève pause pour éviter que le CPU tourne à vide
    usleep(1000);
}
```

La boucle d'événements permet l'**I/O non-bloquante** :

1. Démarrer une opération (ex : requête HTTP)
2. Au lieu d'attendre, enregistrer un callback
3. Continuer d'autres travaux
4. La boucle d'événements vous notifie quand l'opération se termine
5. Votre callback s'exécute avec le résultat

## Multitâche Coopératif vs Préemptif

**Multitâche Coopératif** (PHP Fibers) :

- Les tâches cèdent volontairement le contrôle
- La tâche décide quand faire une pause
- Plus simple, pas de conditions de course
- Une tâche peut bloquer les autres si elle ne cède pas

```php
<?php
// Coopératif - la tâche cède explicitement
$fiber = new Fiber(function() {
    echo "Démarrage\n";
    Fiber::suspend();  // Pause volontaire
    echo "Reprise\n";
});
```

**Multitâche Préemptif** (Threads OS) :

- Le système force les changements de tâche
- Les tâches peuvent être interrompues à tout moment
- Complexe, nécessite des verrous/synchronisation
- Planification équitable, aucune tâche ne peut monopoliser

## Patterns de Programmation Asynchrone

### Callbacks (Traditionnel)

```php
<?php
// Async basé sur les callbacks (peut mener à l'"enfer des callbacks")
$http->request('GET', '/users', function($response) {
    $http->request('GET', '/orders/' . $response->userId, function($orders) {
        $http->request('GET', '/details/' . $orders[0]->id, function($details) {
            // Callbacks profondément imbriqués...
        });
    });
});
```

### Promises

```php
<?php
// Basé sur les Promises (plus lisible)
$http->requestAsync('GET', '/users')
    ->then(function($response) use ($http) {
        return $http->requestAsync('GET', '/orders/' . $response->userId);
    })
    ->then(function($orders) use ($http) {
        return $http->requestAsync('GET', '/details/' . $orders[0]->id);
    })
    ->then(function($details) {
        // Gérer le résultat final
    });
```

### Style Async/Await (avec Fibers)

```php
<?php
// Style async/await (le plus lisible)
async function fetchUserDetails($userId) {
    $user = await $http->get('/users/' . $userId);
    $orders = await $http->get('/orders/' . $user->id);
    $details = await $http->get('/details/' . $orders[0]->id);

    return $details;
}
```

## Points Clés

1. **Concurrence** ≠ **Parallélisme** : La concurrence concerne la structure, le parallélisme concerne l'exécution
2. **L'async** brille pour les travaux I/O-bound, pas CPU-bound
3. **Les boucles d'événements** sont le fondement de la programmation async
4. **Les Fibers** permettent la concurrence coopérative en PHP 8.1+
5. PHP moderne peut gérer des scénarios à haute concurrence avec les bons outils

## Les Grimoires

- [PHP RFC : Fibers](https://wiki.php.net/rfc/fibers)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
