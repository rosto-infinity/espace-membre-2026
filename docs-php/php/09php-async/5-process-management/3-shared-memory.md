---
source_course: "php-async"
source_lesson: "php-async-ipc-shared-memory"
---

# Communication Inter-Processus (IPC)

Lors de l'utilisation de plusieurs processus, ils ont besoin de **moyens pour partager des données**. PHP offre plusieurs mécanismes d'IPC.

## Aperçu des Méthodes IPC

| Méthode           | Vitesse     | Complexité | Cas d'Usage                          |
| ----------------- | ----------- | ---------- | ------------------------------------ |
| Fichiers          | Lente       | Simple     | Données persistantes, logs           |
| Pipes             | Rapide      | Moyenne    | Communication parent-enfant          |
| Mémoire Partagée  | Très Rapide | Complexe   | Partage de données haute performance |
| Files de Messages | Moyenne     | Moyenne    | Distribution de tâches               |
| Sockets           | Moyenne     | Moyenne    | IPC capable de réseau                |

## Utiliser les Pipes

```php
<?php
$descriptors = [
    0 => ['pipe', 'r'],  // stdin
    1 => ['pipe', 'w'],  // stdout
    2 => ['pipe', 'w'],  // stderr
];

$process = proc_open('php worker.php', $descriptors, $pipes);

if ($process) {
    // Envoyer des données au stdin de l'enfant
    fwrite($pipes[0], json_encode(['task' => 'process', 'data' => [1,2,3]]));
    fclose($pipes[0]);

    // Lire depuis le stdout de l'enfant
    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    // Lire les erreurs
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $returnCode = proc_close($process);

    echo "Sortie : {$output}\n";
    echo "Code de retour : {$returnCode}\n";
}
```

## Création de Pipe Manuelle

```php
<?php
$sockets = [];
if (!socket_create_pair(AF_UNIX, SOCK_STREAM, 0, $sockets)) {
    die("Impossible de créer la paire de sockets\n");
}

$pid = pcntl_fork();

if ($pid === 0) {
    // Enfant : fermer l'extrémité du parent
    socket_close($sockets[0]);

    // Lire le message du parent
    $msg = socket_read($sockets[1], 1024);
    echo "Enfant a reçu : {$msg}\n";

    // Envoyer une réponse
    socket_write($sockets[1], "Bonjour depuis l'enfant!");

    socket_close($sockets[1]);
    exit(0);
} else {
    // Parent : fermer l'extrémité de l'enfant
    socket_close($sockets[1]);

    // Envoyer un message à l'enfant
    socket_write($sockets[0], "Bonjour depuis le parent!");

    // Lire la réponse
    $response = socket_read($sockets[0], 1024);
    echo "Parent a reçu : {$response}\n";

    socket_close($sockets[0]);
    pcntl_waitpid($pid, $status);
}
```

## Mémoire Partagée (shmop)

```php
<?php
$key = ftok(__FILE__, 'a');
$size = 1024;

$shmId = shmop_open($key, 'c', 0644, $size);

if ($shmId === false) {
    die("Impossible de créer la mémoire partagée\n");
}

$pid = pcntl_fork();

if ($pid === 0) {
    // Enfant : ouvrir le segment existant
    $shmId = shmop_open($key, 'w', 0, 0);

    // Écrire des données
    $data = json_encode(['result' => 42, 'time' => time()]);
    shmop_write($shmId, $data, 0);

    echo "L'enfant a écrit en mémoire partagée\n";
    exit(0);
} else {
    // Attendre l'enfant
    pcntl_waitpid($pid, $status);

    // Lire les données
    $data = shmop_read($shmId, 0, $size);
    $data = trim($data, "\0");

    echo "Parent a lu : {$data}\n";

    shmop_delete($shmId);
}
```

## Files de Messages

```php
<?php
$key = ftok(__FILE__, 'q');
$queue = msg_get_queue($key, 0644);

$pid = pcntl_fork();

if ($pid === 0) {
    // Enfant : envoyer des messages
    for ($i = 0; $i < 5; $i++) {
        $message = ['task_id' => $i, 'data' => "Tâche {$i}"];
        msg_send($queue, 1, $message);
        echo "Tâche {$i} envoyée\n";
    }
    exit(0);
} else {
    pcntl_waitpid($pid, $status);

    // Parent : recevoir les messages
    while (msg_stat_queue($queue)['msg_qnum'] > 0) {
        $msgType = null;
        $message = null;

        if (msg_receive($queue, 0, $msgType, 1024, $message, true, MSG_IPC_NOWAIT)) {
            echo "Reçu : " . json_encode($message) . "\n";
        }
    }

    msg_remove_queue($queue);
}
```

## Pool de Workers avec IPC

```php
<?php
class WorkerPool {
    private int $workerCount;
    private array $workers = [];
    private $queue;
    private $resultMemory;

    public function __construct(int $workerCount = 4) {
        $this->workerCount = $workerCount;
        $this->queue = msg_get_queue(ftok(__FILE__, 'q'));
        $this->resultMemory = shm_attach(ftok(__FILE__, 's'), 65536);
    }

    public function start(): void {
        for ($i = 0; $i < $this->workerCount; $i++) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                $this->runWorker($i);
                exit(0);
            }

            $this->workers[$pid] = $i;
        }
    }

    private function runWorker(int $workerId): void {
        while (true) {
            $msgType = null;
            $task = null;

            if (msg_receive($this->queue, 1, $msgType, 1024, $task, true)) {
                if ($task === 'STOP') {
                    break;
                }

                $result = $this->processTask($task);

                $results = shm_has_var($this->resultMemory, 1)
                    ? shm_get_var($this->resultMemory, 1)
                    : [];
                $results[$task['id']] = $result;
                shm_put_var($this->resultMemory, 1, $results);
            }
        }
    }

    private function processTask(array $task): mixed {
        usleep($task['duration'] * 1000);
        return ['id' => $task['id'], 'result' => $task['value'] * 2];
    }

    public function submit(array $task): void {
        msg_send($this->queue, 1, $task);
    }

    public function shutdown(): array {
        for ($i = 0; $i < $this->workerCount; $i++) {
            msg_send($this->queue, 1, 'STOP');
        }

        foreach ($this->workers as $pid => $workerId) {
            pcntl_waitpid($pid, $status);
        }

        $results = shm_get_var($this->resultMemory, 1);

        shm_remove($this->resultMemory);
        msg_remove_queue($this->queue);

        return $results;
    }
}

$pool = new WorkerPool(4);
$pool->start();

for ($i = 0; $i < 20; $i++) {
    $pool->submit([
        'id' => $i,
        'value' => $i * 10,
        'duration' => rand(100, 500)
    ]);
}

$results = $pool->shutdown();
print_r($results);
```

## Les Grimoires

- [Manuel PHP - Mémoire Partagée](https://www.php.net/manual/en/book.shmop.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
