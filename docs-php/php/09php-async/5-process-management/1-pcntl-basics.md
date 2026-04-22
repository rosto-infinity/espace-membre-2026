---
source_course: "php-async"
source_lesson: "php-async-pcntl-basics"
---

# Introduction à PCNTL

L'extension **PCNTL (Process Control)** permet à PHP de créer et gérer des processus enfants, permettant un vrai parallélisme pour les tâches CPU-bound.

## Qu'est-ce que PCNTL ?

PCNTL fournit un contrôle des processus de style Unix :

- **Fork de processus** : Créer des processus enfants
- **Gestion des signaux** : Répondre aux signaux système
- **Gestion des processus** : Attendre les enfants, obtenir le statut

> ⚠️ PCNTL ne fonctionne que sur les systèmes Unix (Linux, macOS) et en mode CLI. Il ne fonctionne pas sur Windows ni dans des contextes de serveur web.

## Vérifier la Disponibilité

```php
<?php
if (!extension_loaded('pcntl')) {
    die("Extension PCNTL non disponible\n");
}

if (php_sapi_name() !== 'cli') {
    die("PCNTL ne fonctionne qu'en mode CLI\n");
}

echo "PCNTL est disponible!\n";
```

## Forking de Base

```php
<?php
$pid = pcntl_fork();

if ($pid === -1) {
    // Fork échoué
    die("Impossible de forker le processus\n");

} elseif ($pid === 0) {
    // Processus enfant
    echo "Processus enfant (PID: " . getmypid() . ")\n";
    sleep(2);
    echo "Enfant terminé\n";
    exit(0);  // Important : l'enfant doit quitter!

} else {
    // Processus parent
    echo "Processus parent (PID: " . getmypid() . ")\n";
    echo "Enfant créé avec le PID: {$pid}\n";

    // Attendre que l'enfant se termine
    pcntl_waitpid($pid, $status);
    echo "L'enfant a quitté avec le statut: " . pcntl_wexitstatus($status) . "\n";
}

echo "Processus " . getmypid() . " se termine\n";
```

## Comprendre le Fork

Quand vous appelez `pcntl_fork()` :

1. Le processus entier est dupliqué
2. Les deux processus continuent depuis le même point
3. `fork()` retourne différemment dans chaque processus :
   - Parent : reçoit le PID de l'enfant
   - Enfant : reçoit 0

```
Avant le fork :
┌─────────────────────┐
│ Processus (PID 1234) │
│ $x = 5              │
│ $y = 10             │
└─────────────────────┘

Après le fork :
┌─────────────────────┐     ┌─────────────────────┐
│ Parent (PID 1234)   │     │ Enfant (PID 1235)   │
│ $x = 5              │     │ $x = 5              │
│ $y = 10             │     │ $y = 10             │
│ $pid = 1235         │     │ $pid = 0            │
└─────────────────────┘     └─────────────────────┘
```

## Plusieurs Processus Enfants

```php
<?php
$workerCount = 4;
$children = [];

for ($i = 0; $i < $workerCount; $i++) {
    $pid = pcntl_fork();

    if ($pid === -1) {
        die("Fork échoué\n");
    } elseif ($pid === 0) {
        // Enfant
        $workerId = $i;
        echo "Worker {$workerId} démarré (PID: " . getmypid() . ")\n";

        $result = doWork($workerId);

        echo "Worker {$workerId} terminé avec résultat: {$result}\n";
        exit($result);
    } else {
        // Parent
        $children[$pid] = $i;
    }
}

echo "\nParent attend " . count($children) . " workers...\n\n";

while (count($children) > 0) {
    $pid = pcntl_waitpid(-1, $status);

    if ($pid > 0) {
        $workerId = $children[$pid];
        $exitCode = pcntl_wexitstatus($status);
        echo "Worker {$workerId} (PID {$pid}) a quitté avec code {$exitCode}\n";
        unset($children[$pid]);
    }
}

echo "\nTous les workers ont terminé\n";

function doWork(int $workerId): int {
    $sleepTime = rand(1, 3);
    sleep($sleepTime);
    return $sleepTime;
}
```

## Attente Non-Bloquante

```php
<?php
$children = [];

for ($i = 0; $i < 3; $i++) {
    $pid = pcntl_fork();
    if ($pid === 0) {
        sleep(rand(1, 5));
        exit(0);
    }
    $children[] = $pid;
}

// Sondage non-bloquant pour les enfants terminés
while (!empty($children)) {
    foreach ($children as $key => $pid) {
        // WNOHANG rend l'appel non-bloquant
        $result = pcntl_waitpid($pid, $status, WNOHANG);

        if ($result === $pid) {
            echo "Enfant {$pid} terminé\n";
            unset($children[$key]);
        } elseif ($result === -1) {
            unset($children[$key]);
        }
    }

    echo "Vérification... " . count($children) . " encore en cours\n";
    usleep(500000);  // 0.5 secondes
}
```

## Les Grimoires

- [Manuel PHP - Fonctions PCNTL](https://www.php.net/manual/en/ref.pcntl.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
