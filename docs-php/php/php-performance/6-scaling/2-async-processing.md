---
source_course: "php-performance"
source_lesson: "php-performance-async-processing"
---

# Traitement Asynchrone

Déplacez les opérations lentes hors du cycle de requête.

## Files de Messages

```php
<?php
// Au lieu de traiter immédiatement :
// POST /orders -> traiter paiement -> envoyer email -> répondre

// Mettre le travail en file pour plus tard :
// POST /orders -> mettre en file -> répondre immédiatement

class QueueClient
{
    private Redis $redis;

    public function push(string $queue, array $job): void
    {
        $this->redis->rPush($queue, json_encode([
            'id' => uniqid(),
            'payload' => $job,
            'created_at' => time(),
        ]));
    }

    public function pop(string $queue, int $timeout = 0): ?array
    {
        $result = $this->redis->blPop($queue, $timeout);

        if ($result) {
            return json_decode($result[1], true);
        }

        return null;
    }
}

// Dans le gestionnaire de requêtes
$queue->push('emails', [
    'type' => 'order_confirmation',
    'user_id' => $user->id,
    'order_id' => $order->id,
]);

return new JsonResponse(['order_id' => $order->id]);
```

## Processus Worker

```php
<?php
// worker.php — s'exécute en continu
class Worker
{
    public function __construct(
        private QueueClient $queue,
        private array $handlers
    ) {}

    public function run(string $queueName): never
    {
        echo "Worker démarré sur la file : $queueName\n";

        while (true) {
            $job = $this->queue->pop($queueName, timeout: 30);

            if ($job === null) {
                continue;  // Délai expiré, vérifier à nouveau
            }

            try {
                $this->process($job);
                echo "Tâche traitée : {$job['id']}\n";
            } catch (Throwable $e) {
                echo "Tâche échouée : {$job['id']} - {$e->getMessage()}\n";
                $this->handleFailure($job, $e);
            }
        }
    }

    private function process(array $job): void
    {
        $type = $job['payload']['type'];

        if (!isset($this->handlers[$type])) {
            throw new RuntimeException("Type de tâche inconnu : $type");
        }

        $this->handlers[$type]->handle($job['payload']);
    }

    private function handleFailure(array $job, Throwable $e): void
    {
        // Déplacer vers la file des échecs ou réessayer
        $this->queue->push('failed', [
            ...$job,
            'error' => $e->getMessage(),
            'failed_at' => time(),
        ]);
    }
}

// Exécuter les workers
$worker = new Worker($queue, [
    'order_confirmation' => new SendOrderConfirmation(),
    'process_payment' => new ProcessPayment(),
    'generate_report' => new GenerateReport(),
]);

$worker->run('default');
```

## Configuration Supervisor

```ini
; /etc/supervisor/conf.d/worker.conf
[program:php-worker]
command=php /var/www/app/worker.php
process_name=%(program_name)s_%(process_num)02d
numprocs=4  ; Exécuter 4 processus worker
autostart=true
autorestart=true
user=www-data
stdout_logfile=/var/log/worker.log
```

## File de Tâches Complète avec Retry

```php
<?php
declare(strict_types=1);

interface JobHandler {
    public function handle(array $payload): void;
}

class SendEmailHandler implements JobHandler {
    public function handle(array $payload): void {
        $email = $payload['email'];
        $subject = $payload['subject'];
        $body = $payload['body'];

        // Envoyer l'email...
        mail($email, $subject, $body);
    }
}

class JobQueue {
    public function __construct(
        private Redis $redis,
        private string $prefix = 'queue:'
    ) {}

    public function dispatch(string $queue, string $handler, array $payload, int $delay = 0): string {
        $jobId = bin2hex(random_bytes(16));

        $job = [
            'id' => $jobId,
            'handler' => $handler,
            'payload' => $payload,
            'attempts' => 0,
            'created_at' => time(),
        ];

        if ($delay > 0) {
            // Tâche différée
            $this->redis->zAdd(
                $this->prefix . 'delayed',
                time() + $delay,
                json_encode($job)
            );
        } else {
            $this->redis->rPush($this->prefix . $queue, json_encode($job));
        }

        return $jobId;
    }

    public function migrateDelayed(): int {
        $now = time();
        $jobs = $this->redis->zRangeByScore(
            $this->prefix . 'delayed',
            '-inf',
            (string) $now
        );

        foreach ($jobs as $jobJson) {
            $job = json_decode($jobJson, true);
            $this->redis->rPush($this->prefix . 'default', $jobJson);
            $this->redis->zRem($this->prefix . 'delayed', $jobJson);
        }

        return count($jobs);
    }
}

class JobWorker {
    private bool $running = true;

    public function __construct(
        private Redis $redis,
        private array $handlers,
        private int $maxAttempts = 3
    ) {
        pcntl_signal(SIGTERM, fn() => $this->running = false);
        pcntl_signal(SIGINT, fn() => $this->running = false);
    }

    public function run(string $queue = 'default'): void {
        $prefix = 'queue:';

        while ($this->running) {
            pcntl_signal_dispatch();

            // Migrer les tâches différées
            (new JobQueue($this->redis))->migrateDelayed();

            // Récupérer une tâche
            $result = $this->redis->blPop($prefix . $queue, 5);

            if (!$result) continue;

            $job = json_decode($result[1], true);
            $this->processJob($job, $queue);
        }
    }

    private function processJob(array $job, string $queue): void {
        $job['attempts']++;

        try {
            $handler = $this->handlers[$job['handler']] ?? null;

            if (!$handler) {
                throw new RuntimeException("Gestionnaire inconnu : {$job['handler']}");
            }

            $handler->handle($job['payload']);

            echo "[OK] Tâche {$job['id']} traitée\n";

        } catch (Throwable $e) {
            echo "[ERREUR] Tâche {$job['id']} : {$e->getMessage()}\n";

            if ($job['attempts'] < $this->maxAttempts) {
                // Réessayer avec backoff exponentiel
                $delay = pow(2, $job['attempts']) * 60;
                (new JobQueue($this->redis))->dispatch(
                    $queue,
                    $job['handler'],
                    $job['payload'],
                    $delay
                );
            } else {
                // Déplacer vers la file des échecs
                $job['error'] = $e->getMessage();
                $job['failed_at'] = time();
                $this->redis->rPush('queue:failed', json_encode($job));
            }
        }
    }
}

// Utilisation
$redis = new Redis();
$redis->connect('127.0.0.1');

// Expédier les tâches
$queue = new JobQueue($redis);
$queue->dispatch('default', SendEmailHandler::class, [
    'email' => 'user@example.com',
    'subject' => 'Bienvenue !',
    'body' => 'Merci de votre inscription.',
]);

// Exécuter le worker
$worker = new JobWorker($redis, [
    SendEmailHandler::class => new SendEmailHandler(),
]);
$worker->run();
?>
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
