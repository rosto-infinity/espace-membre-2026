---
source_course: "php-api-development"
source_lesson: "php-api-development-rate-limit-implementation"
---

# Implémenter la Limitation du Débit (Rate Limiting)

La limitation du débit **prévient les abus de l'API** en restreignant le nombre de requêtes qu'un client peut effectuer dans une période donnée.

## L'Algorithme du Seau à Jetons (Token Bucket)

```php
<?php
class RateLimiter {
    public function __construct(
        private PDO $pdo,
        private int $maxRequests = 60,
        private int $windowSeconds = 60
    ) {}

    public function check(string $key): RateLimitResult {
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        // Nettoyer les anciens enregistrements
        $stmt = $this->pdo->prepare(
            'DELETE FROM rate_limits WHERE `key` = :key AND timestamp < :window'
        );
        $stmt->execute(['key' => $key, 'window' => $windowStart]);

        // Compter les requêtes dans la fenêtre
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM rate_limits WHERE `key` = :key'
        );
        $stmt->execute(['key' => $key]);
        $count = (int) $stmt->fetchColumn();

        $remaining = max(0, $this->maxRequests - $count);
        $resetAt = $now + $this->windowSeconds;

        if ($count >= $this->maxRequests) {
            return new RateLimitResult(
                allowed: false,
                limit: $this->maxRequests,
                remaining: 0,
                resetAt: $resetAt
            );
        }

        // Enregistrer cette requête
        $stmt = $this->pdo->prepare(
            'INSERT INTO rate_limits (`key`, timestamp) VALUES (:key, :time)'
        );
        $stmt->execute(['key' => $key, 'time' => $now]);

        return new RateLimitResult(
            allowed: true,
            limit: $this->maxRequests,
            remaining: $remaining - 1,
            resetAt: $resetAt
        );
    }
}

class RateLimitResult {
    public function __construct(
        public bool $allowed,
        public int $limit,
        public int $remaining,
        public int $resetAt
    ) {}

    public function setHeaders(): void {
        header('X-RateLimit-Limit: ' . $this->limit);
        header('X-RateLimit-Remaining: ' . $this->remaining);
        header('X-RateLimit-Reset: ' . $this->resetAt);

        if (!$this->allowed) {
            header('Retry-After: ' . ($this->resetAt - time()));
        }
    }
}
```

## Middleware de Limitation du Débit

```php
<?php
class RateLimitMiddleware {
    public function __construct(
        private RateLimiter $limiter
    ) {}

    public function handle(): void {
        // Utiliser l'IP + l'ID utilisateur (si authentifié) comme clé
        $key = $this->getKey();

        $result = $this->limiter->check($key);
        $result->setHeaders();

        if (!$result->allowed) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Trop de requêtes',
                'message' => 'Limite de débit dépassée. Réessayez plus tard.',
                'retry_after' => $result->resetAt - time(),
            ]);
            exit;
        }
    }

    private function getKey(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'inconnu';

        // Si authentifié, utiliser l'ID utilisateur
        $userId = $GLOBALS['auth_user']['user_id'] ?? null;
        if ($userId) {
            return "user:$userId";
        }

        return "ip:$ip";
    }
}
```

## Limites Différentes par Endpoint

```php
<?php
class TieredRateLimiter {
    private array $tiers = [
        'default' => ['requests' => 60, 'window' => 60],
        'search' => ['requests' => 20, 'window' => 60],
        'write' => ['requests' => 10, 'window' => 60],
        'auth' => ['requests' => 5, 'window' => 300],
    ];

    public function check(string $key, string $tier = 'default'): RateLimitResult {
        $config = $this->tiers[$tier] ?? $this->tiers['default'];

        $limiter = new RateLimiter(
            $this->pdo,
            $config['requests'],
            $config['window']
        );

        return $limiter->check("$tier:$key");
    }
}

// Utilisation
$router->post('/auth/login', function() use ($rateLimiter) {
    $result = $rateLimiter->check($_SERVER['REMOTE_ADDR'], 'auth');
    // ...
});

$router->get('/search', function() use ($rateLimiter) {
    $result = $rateLimiter->check(getAuthUserId(), 'search');
    // ...
});
```

## Les Grimoires

- [Stratégies de Rate Limiting (Google Cloud)](https://cloud.google.com/architecture/rate-limiting-strategies-techniques)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
