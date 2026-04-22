---
source_course: "php-security"
source_lesson: "php-security-rate-limiting"
---

# Limitation du Débit (Rate Limiting)

La limitation du débit **prévient les abus en restreignant la fréquence des actions**.

## Limiteur de Débit Simple

```php
<?php
class RateLimiter {
    public function __construct(
        private PDO $pdo
    ) {}

    public function attempt(
        string $key,
        int $maxAttempts,
        int $windowSeconds
    ): bool {
        $now = time();
        $windowStart = $now - $windowSeconds;

        // Nettoyer les anciennes entrées
        $stmt = $this->pdo->prepare(
            'DELETE FROM rate_limits WHERE key = :key AND timestamp < :window'
        );
        $stmt->execute(['key' => $key, 'window' => $windowStart]);

        // Compter les tentatives récentes
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE key = :key'
        );
        $stmt->execute(['key' => $key]);
        $count = $stmt->fetchColumn();

        if ($count >= $maxAttempts) {
            return false;  // Débit limité
        }

        // Enregistrer cette tentative
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limits (key, timestamp) VALUES (:key, :time)'
        );
        $stmt->execute(['key' => $key, 'time' => $now]);

        return true;
    }
}

// Utilisation
$limiter = new RateLimiter($pdo);
$key = 'login:' . $_SERVER['REMOTE_ADDR'];

if (!$limiter->attempt($key, maxAttempts: 5, windowSeconds: 300)) {
    http_response_code(429);
    die('Trop de requêtes. Veuillez attendre 5 minutes.');
}
```

## Seau à Jetons avec Redis

```php
<?php
class TokenBucketLimiter {
    public function __construct(
        private Redis $redis
    ) {}

    public function consume(
        string $key,
        int $capacity,
        int $refillRate,
        int $refillInterval = 1
    ): bool {
        $now = microtime(true);
        $bucketKey = "bucket:$key";

        $bucket = $this->redis->hGetAll($bucketKey);

        if (empty($bucket)) {
            $tokens = $capacity - 1;
            $lastRefill = $now;
        } else {
            $tokens = (float) $bucket['tokens'];
            $lastRefill = (float) $bucket['last_refill'];

            // Recharger les jetons selon le temps écoulé
            $elapsed = $now - $lastRefill;
            $refillAmount = floor($elapsed / $refillInterval) * $refillRate;
            $tokens = min($capacity, $tokens + $refillAmount);
            $lastRefill = $now;

            if ($tokens < 1) {
                return false;  // Pas de jetons disponibles
            }

            $tokens--;
        }

        $this->redis->hMSet($bucketKey, [
            'tokens' => $tokens,
            'last_refill' => $lastRefill,
        ]);
        $this->redis->expire($bucketKey, 3600);

        return true;
    }
}
```

## Exemple Complet

**Limiteur à fenêtre glissante prêt pour la production**

```php
<?php
declare(strict_types=1);

// Limiteur de débit avec différentes stratégies
interface RateLimiterInterface {
    public function attempt(string $key): bool;
    public function getRemainingAttempts(string $key): int;
    public function getRetryAfter(string $key): int;
}

class SlidingWindowLimiter implements RateLimiterInterface {
    public function __construct(
        private PDO $pdo,
        private int $maxAttempts = 60,
        private int $windowSeconds = 60
    ) {}

    public function attempt(string $key): bool {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        // Opération atomique avec transaction
        $this->pdo->beginTransaction();

        try {
            // Supprimer les entrées expirées
            $stmt = $this->pdo->prepare(
                'DELETE FROM rate_limits WHERE `key` = :key AND created_at < :window'
            );
            $stmt->execute(['key' => $key, 'window' => $windowStart]);

            // Compter la fenêtre actuelle
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM rate_limits WHERE `key` = :key'
            );
            $stmt->execute(['key' => $key]);
            $count = (int) $stmt->fetchColumn();

            if ($count >= $this->maxAttempts) {
                $this->pdo->commit();
                return false;
            }

            // Enregistrer la tentative
            $stmt = $this->pdo->prepare(
                'INSERT INTO rate_limits (`key`, created_at) VALUES (:key, :time)'
            );
            $stmt->execute(['key' => $key, 'time' => $now]);

            $this->pdo->commit();
            return true;

        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function getRemainingAttempts(string $key): int {
        $windowStart = time() - $this->windowSeconds;

        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE `key` = :key AND created_at >= :window'
        );
        $stmt->execute(['key' => $key, 'window' => $windowStart]);
        $count = (int) $stmt->fetchColumn();

        return max(0, $this->maxAttempts - $count);
    }

    public function getRetryAfter(string $key): int {
        $stmt = $this->pdo->prepare(
            'SELECT MIN(created_at) FROM rate_limits WHERE `key` = :key'
        );
        $stmt->execute(['key' => $key]);
        $oldest = $stmt->fetchColumn();

        if (!$oldest) {
            return 0;
        }

        return max(0, ($oldest + $this->windowSeconds) - time());
    }
}

// Utilisation middleware
function rateLimitMiddleware(RateLimiterInterface $limiter): void {
    $key = 'api:' . ($_SERVER['REMOTE_ADDR'] ?? 'inconnu');

    if (!$limiter->attempt($key)) {
        $retryAfter = $limiter->getRetryAfter($key);
        header('Retry-After: ' . $retryAfter);
        header('X-RateLimit-Remaining: 0');
        http_response_code(429);
        die(json_encode(['error' => 'Trop de Requêtes']));
    }

    header('X-RateLimit-Remaining: ' . $limiter->getRemainingAttempts($key));
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
