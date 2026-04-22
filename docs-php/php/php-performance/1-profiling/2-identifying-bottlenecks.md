---
source_course: "php-performance"
source_lesson: "php-performance-identifying-bottlenecks"
---

# Identifier les Goulots d'Étranglement

La plupart des problèmes de performance tombent dans des catégories prévisibles. Apprenez à les reconnaître.

## Goulots d'Étranglement Courants

### 1. Requêtes Base de Données

```php
<?php
// Activer la journalisation des requêtes
$pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [LoggedStatement::class]);

class LoggedStatement extends PDOStatement
{
    private float $startTime;

    public function execute(?array $params = null): bool
    {
        $this->startTime = microtime(true);
        $result = parent::execute($params);

        $duration = (microtime(true) - $this->startTime) * 1000;

        if ($duration > 100) {  // Seuil de requête lente
            error_log(sprintf(
                'Requête lente (%.2fms) : %s',
                $duration,
                $this->queryString
            ));
        }

        return $result;
    }
}
```

### 2. Requêtes N+1

```php
<?php
// MAUVAIS : N+1 — 101 requêtes pour 100 utilisateurs
$users = $pdo->query('SELECT * FROM users LIMIT 100');
foreach ($users as $user) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ?');
    $stmt->execute([$user['id']]);
}

// BON : 2 requêtes
$users = $pdo->query('SELECT * FROM users LIMIT 100');
$userIds = array_column($users, 'id');
$orders = $pdo->query('SELECT * FROM orders WHERE user_id IN (' . implode(',', $userIds) . ')');
```

### 3. Surconsommation Mémoire

```php
<?php
// MAUVAIS : Charge tout le fichier en mémoire
$content = file_get_contents('huge_file.csv');
$lines = explode("\n", $content);

// BON : Lecture ligne par ligne en flux
$handle = fopen('huge_file.csv', 'r');
while (($line = fgets($handle)) !== false) {
    processLine($line);
}
fclose($handle);
```

### 4. Appels API Externes

```php
<?php
// MAUVAIS : Appels séquentiels
$user = fetchFromApi('/users/1');      // 200ms
$orders = fetchFromApi('/orders');      // 300ms
$products = fetchFromApi('/products');  // 250ms
// Total : 750ms

// BON : En parallèle avec curl_multi ou Guzzle
$responses = $guzzle->request([
    ['GET', '/users/1'],
    ['GET', '/orders'],
    ['GET', '/products'],
]);
// Total : ~300ms (requête la plus lente)
```

## Checklist de Performance

```php
<?php
class PerformanceChecker
{
    public function analyze(): array
    {
        $issues = [];

        // Vérifier OPcache
        if (!function_exists('opcache_get_status') || !opcache_get_status()) {
            $issues[] = 'OPcache est désactivé';
        }

        // Vérifier la limite mémoire
        $memLimit = ini_get('memory_limit');
        if ($this->parseBytes($memLimit) < 128 * 1024 * 1024) {
            $issues[] = "Limite mémoire faible : $memLimit";
        }

        // Vérifier le cache realpath
        $realpathSize = ini_get('realpath_cache_size');
        if ($this->parseBytes($realpathSize) < 4 * 1024 * 1024) {
            $issues[] = "Cache realpath trop petit : $realpathSize";
        }

        return $issues;
    }
}
```

## Middleware de Surveillance

```php
<?php
declare(strict_types=1);

class PerformanceMiddleware
{
    private float $requestStart;
    private array $metrics = [];

    public function before(): void
    {
        $this->requestStart = microtime(true);
        $this->metrics['memory_start'] = memory_get_usage();
        $this->metrics['queries'] = 0;
        $this->metrics['cache_hits'] = 0;
        $this->metrics['cache_misses'] = 0;
    }

    public function recordQuery(float $duration): void
    {
        $this->metrics['queries']++;
        $this->metrics['query_time'] = ($this->metrics['query_time'] ?? 0) + $duration;
    }

    public function recordCacheHit(): void
    {
        $this->metrics['cache_hits']++;
    }

    public function recordCacheMiss(): void
    {
        $this->metrics['cache_misses']++;
    }

    public function after(): void
    {
        $duration = (microtime(true) - $this->requestStart) * 1000;
        $memory = (memory_get_usage() - $this->metrics['memory_start']) / 1024 / 1024;

        // Ajouter des en-têtes de performance
        header(sprintf('X-Response-Time: %.2fms', $duration));
        header(sprintf('X-Memory-Usage: %.2fMB', $memory));
        header(sprintf('X-Query-Count: %d', $this->metrics['queries']));

        // Logger les requêtes lentes
        if ($duration > 500) {
            $this->logSlowRequest($duration);
        }

        // Envoyer aux outils de monitoring
        $this->sendMetrics($duration, $memory);
    }

    private function logSlowRequest(float $duration): void
    {
        $data = [
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'duration_ms' => $duration,
            'queries' => $this->metrics['queries'],
            'query_time_ms' => $this->metrics['query_time'] ?? 0,
            'memory_mb' => (memory_get_peak_usage() / 1024 / 1024),
            'timestamp' => date('c'),
        ];

        error_log('SLOW_REQUEST: ' . json_encode($data));
    }

    private function sendMetrics(float $duration, float $memory): void
    {
        // Envoyer vers StatsD, Prometheus, etc.
    }
}

// Utilisation dans l'application
$perf = new PerformanceMiddleware();
$perf->before();

// Code applicatif...

$perf->after();
?>
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
