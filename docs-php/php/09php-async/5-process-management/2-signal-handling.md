---
source_course: "php-async"
source_lesson: "php-async-signal-handling"
---

# Gestion des Signaux en PHP

Les **signaux sont des interruptions logicielles** envoyées à un processus. Les gérer correctement est essentiel pour des applications CLI robustes.

## Signaux Courants

| Signal  | Numéro | Description                | Action par Défaut        |
| ------- | ------ | -------------------------- | ------------------------ |
| SIGTERM | 15     | Demande de terminaison     | Terminer                 |
| SIGINT  | 2      | Interruption (Ctrl+C)      | Terminer                 |
| SIGKILL | 9      | Arrêt forcé                | Ne peut pas être capturé |
| SIGHUP  | 1      | Déconnexion                | Terminer                 |
| SIGCHLD | 17     | Statut enfant changé       | Ignorer                  |
| SIGUSR1 | 10     | Défini par l'utilisateur 1 | Terminer                 |
| SIGUSR2 | 12     | Défini par l'utilisateur 2 | Terminer                 |

## Enregistrer des Gestionnaires de Signaux

```php
<?php
// Activer la gestion asynchrone des signaux
pcntl_async_signals(true);

// Enregistrer un gestionnaire pour SIGTERM
pcntl_signal(SIGTERM, function (int $signal) {
    echo "SIGTERM reçu, arrêt gracieux...\n";
    exit(0);
});

// Enregistrer un gestionnaire pour SIGINT (Ctrl+C)
pcntl_signal(SIGINT, function (int $signal) {
    echo "\nSIGINT (Ctrl+C) reçu\n";
    exit(0);
});

// Plusieurs signaux, même gestionnaire
$shutdown = function (int $signal) {
    $signalNames = [
        SIGTERM => 'SIGTERM',
        SIGINT => 'SIGINT',
        SIGHUP => 'SIGHUP',
    ];
    echo "Reçu " . ($signalNames[$signal] ?? $signal) . "\n";
    exit(0);
};

pcntl_signal(SIGTERM, $shutdown);
pcntl_signal(SIGINT, $shutdown);
pcntl_signal(SIGHUP, $shutdown);

echo "En cours... Appuyez sur Ctrl+C pour arrêter\n";
while (true) {
    echo ".";
    sleep(1);
}
```

## Gestion Synchrone vs Asynchrone des Signaux

```php
<?php
// MÉTHODE 1 : Signaux asynchrones (PHP 7.1+, recommandé)
pcntl_async_signals(true);
pcntl_signal(SIGTERM, $handler);
// Le gestionnaire s'exécute immédiatement à réception du signal

// MÉTHODE 2 : Dispatch manuel (méthode plus ancienne)
pcntl_signal(SIGTERM, $handler);
while (true) {
    // Appeler ceci pour traiter les signaux en attente
    pcntl_signal_dispatch();

    doWork();
}
```

## Pattern d'Arrêt Gracieux

```php
<?php
class GracefulWorker {
    private bool $shouldStop = false;
    private array $activeJobs = [];

    public function __construct() {
        pcntl_async_signals(true);

        pcntl_signal(SIGTERM, [$this, 'handleShutdown']);
        pcntl_signal(SIGINT, [$this, 'handleShutdown']);
    }

    public function handleShutdown(int $signal): void {
        echo "\nArrêt demandé, fin des jobs en cours...\n";
        $this->shouldStop = true;
    }

    public function run(): void {
        echo "Worker démarré. PID: " . getmypid() . "\n";

        while (!$this->shouldStop) {
            $job = $this->getNextJob();

            if ($job === null) {
                sleep(1);
                continue;
            }

            $this->activeJobs[] = $job;
            $this->processJob($job);
            array_pop($this->activeJobs);
        }

        // Attendre que les jobs actifs se terminent
        while (!empty($this->activeJobs)) {
            echo "Attente de " . count($this->activeJobs) . " jobs...\n";
            sleep(1);
        }

        echo "Worker arrêté gracieusement\n";
    }

    private function getNextJob(): ?array {
        return rand(0, 2) > 0 ? ['id' => uniqid()] : null;
    }

    private function processJob(array $job): void {
        echo "Traitement du job {$job['id']}\n";
        sleep(2);
        echo "Job {$job['id']} terminé\n";
    }
}

$worker = new GracefulWorker();
$worker->run();
```

## Signaux des Processus Enfants

```php
<?php
pcntl_async_signals(true);

$children = [];

// Gérer SIGCHLD pour savoir quand les enfants quittent
pcntl_signal(SIGCHLD, function () use (&$children) {
    // Récupérer tous les enfants terminés
    while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
        if (isset($children[$pid])) {
            $exitCode = pcntl_wexitstatus($status);
            echo "Enfant {$pid} a quitté avec code {$exitCode}\n";
            unset($children[$pid]);
        }
    }
});

for ($i = 0; $i < 3; $i++) {
    $pid = pcntl_fork();

    if ($pid === 0) {
        sleep(rand(1, 5));
        exit($i);
    }

    $children[$pid] = true;
}

echo "Parent fait d'autres travaux...\n";
while (!empty($children)) {
    echo "En cours... (" . count($children) . " enfants)\n";
    sleep(1);
}
echo "Tous les enfants ont terminé\n";
```

## Envoyer des Signaux

```php
<?php
// Envoyer un signal à un processus spécifique
posix_kill($pid, SIGTERM);

// Envoyer un signal à un groupe de processus
posix_kill(-$pgid, SIGTERM);  // PID négatif = groupe de processus

// Vérifier si un processus existe
if (posix_kill($pid, 0)) {
    echo "Processus {$pid} est en cours\n";
} else {
    echo "Processus {$pid} introuvable\n";
}
```

## Opérations Sûres pour les Signaux

Les gestionnaires de signaux doivent être minimaux :

✅ Définir des flags/variables
✅ Appeler `exit()`
✅ Arithmétique simple

À éviter dans les gestionnaires :

❌ Opérations I/O complexes
❌ Allocation de mémoire
❌ Opérations de base de données
❌ Opérations sur les fichiers

```php
<?php
// Bon pattern : définir un flag, gérer dans la boucle principale
$shutdown = false;

pcntl_signal(SIGTERM, function () use (&$shutdown) {
    $shutdown = true;  // Juste définir le flag
});

while (!$shutdown) {
    doWork();

    if ($shutdown) {
        performCleanup();  // Sûr de faire des opérations complexes ici
    }
}
```

## Les Grimoires

- [Manuel PHP - pcntl_signal](https://www.php.net/manual/en/function.pcntl-signal.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
