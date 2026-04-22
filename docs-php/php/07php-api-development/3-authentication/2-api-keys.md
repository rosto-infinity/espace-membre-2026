---
source_course: "php-api-development"
source_lesson: "php-api-development-api-keys"
---

# L'Authentification par Clés API

Les clés API sont des jetons simples pour **authentifier les requêtes API**, idéales pour la communication serveur-à-serveur.

## Générer des Clés API

```php
<?php
class ApiKeyService {
    public function __construct(
        private PDO $pdo
    ) {}

    public function generate(int $userId, string $name): array {
        // Générer une clé aléatoire sécurisée
        $rawKey = bin2hex(random_bytes(32));  // Chaîne hex de 64 caractères

        // Stocker seulement le hash
        $hashedKey = hash('sha256', $rawKey);

        // Créer un préfixe d'identification (8 premiers caractères)
        $prefix = substr($rawKey, 0, 8);

        $stmt = $this->pdo->prepare('
            INSERT INTO api_keys (user_id, name, key_prefix, key_hash, created_at)
            VALUES (:user_id, :name, :prefix, :hash, NOW())
        ');

        $stmt->execute([
            'user_id' => $userId,
            'name' => $name,
            'prefix' => $prefix,
            'hash' => $hashedKey,
        ]);

        return [
            'id' => (int) $this->pdo->lastInsertId(),
            'key' => $rawKey,  // Affiché une seule fois !
            'prefix' => $prefix,
            'name' => $name,
        ];
    }

    public function validate(string $key): ?array {
        $prefix = substr($key, 0, 8);
        $hash = hash('sha256', $key);

        $stmt = $this->pdo->prepare('
            SELECT ak.*, u.id as user_id, u.email, u.role
            FROM api_keys ak
            JOIN users u ON ak.user_id = u.id
            WHERE ak.key_prefix = :prefix
              AND ak.key_hash = :hash
              AND ak.revoked_at IS NULL
        ');

        $stmt->execute(['prefix' => $prefix, 'hash' => $hash]);
        $result = $stmt->fetch();

        if ($result) {
            // Mettre à jour la dernière utilisation
            $this->pdo->prepare('UPDATE api_keys SET last_used_at = NOW() WHERE id = ?')
                ->execute([$result['id']]);
        }

        return $result ?: null;
    }

    public function revoke(int $keyId): void {
        $this->pdo->prepare('UPDATE api_keys SET revoked_at = NOW() WHERE id = ?')
            ->execute([$keyId]);
    }
}
```

## Middleware de Clé API

```php
<?php
class ApiKeyMiddleware {
    public function __construct(
        private ApiKeyService $apiKeys
    ) {}

    public function handle(): void {
        $key = $this->extractKey();

        if (!$key) {
            $this->unauthorized('Clé API requise');
        }

        $keyData = $this->apiKeys->validate($key);

        if (!$keyData) {
            $this->unauthorized('Clé API invalide');
        }

        // Stocker pour utilisation ultérieure
        $GLOBALS['auth_user'] = [
            'user_id' => $keyData['user_id'],
            'email' => $keyData['email'],
            'role' => $keyData['role'],
            'key_id' => $keyData['id'],
        ];
    }

    private function extractKey(): ?string {
        // Header : X-API-Key: votre-clé
        if (isset($_SERVER['HTTP_X_API_KEY'])) {
            return $_SERVER['HTTP_X_API_KEY'];
        }

        // Header : Authorization: Bearer votre-clé
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/^Bearer\s+(\S+)$/i', $auth, $matches)) {
            return $matches[1];
        }

        // Paramètre de requête (non recommandé)
        return $_GET['api_key'] ?? null;
    }

    private function unauthorized(string $message): never {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => $message]);
        exit;
    }
}
```

## Gérer les Clés API

```php
<?php
// Créer une clé (authentifié)
$router->post('/api-keys', function() use ($apiKeys) {
    $user = getAuthUser();
    $input = json_decode(file_get_contents('php://input'), true);

    $result = $apiKeys->generate($user['user_id'], $input['name'] ?? 'Défaut');

    http_response_code(201);
    return [
        'message' => 'Conservez cette clé en sécurité. Elle ne sera plus affichée.',
        'api_key' => $result,
    ];
});

// Lister les clés (sans afficher la clé complète)
$router->get('/api-keys', function() use ($pdo) {
    $user = getAuthUser();

    $stmt = $pdo->prepare('
        SELECT id, name, key_prefix, created_at, last_used_at
        FROM api_keys
        WHERE user_id = ? AND revoked_at IS NULL
    ');
    $stmt->execute([$user['user_id']]);

    return ['data' => $stmt->fetchAll()];
});

// Révoquer une clé
$router->delete('/api-keys/{id}', function(string $id) use ($apiKeys) {
    $apiKeys->revoke((int) $id);
    http_response_code(204);
    return null;
});
```

## Exemple Complet

**Gestionnaire d'authentification multi-stratégies**

```php
<?php
declare(strict_types=1);

// Système d'authentification complet supportant plusieurs méthodes
class AuthManager {
    private array $strategies = [];

    public function addStrategy(string $name, AuthStrategy $strategy): void {
        $this->strategies[$name] = $strategy;
    }

    public function authenticate(): ?AuthUser {
        foreach ($this->strategies as $strategy) {
            $user = $strategy->authenticate();
            if ($user !== null) {
                return $user;
            }
        }
        return null;
    }

    public function requireAuth(): AuthUser {
        $user = $this->authenticate();
        if ($user === null) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Authentification requise']);
            exit;
        }
        return $user;
    }
}

interface AuthStrategy {
    public function authenticate(): ?AuthUser;
}

class AuthUser {
    public function __construct(
        public int $id,
        public string $email,
        public string $role,
        public string $authMethod
    ) {}
}

class JwtAuthStrategy implements AuthStrategy {
    public function __construct(private JWT $jwt) {}

    public function authenticate(): ?AuthUser {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(\S+)$/i', $header, $matches)) {
            return null;
        }

        $payload = $this->jwt->decode($matches[1]);
        if (!$payload) {
            return null;
        }

        return new AuthUser(
            $payload['user_id'],
            $payload['email'],
            $payload['role'] ?? 'user',
            'jwt'
        );
    }
}

class ApiKeyAuthStrategy implements AuthStrategy {
    public function __construct(private ApiKeyService $keys) {}

    public function authenticate(): ?AuthUser {
        $key = $_SERVER['HTTP_X_API_KEY'] ?? null;
        if (!$key) {
            return null;
        }

        $data = $this->keys->validate($key);
        if (!$data) {
            return null;
        }

        return new AuthUser(
            $data['user_id'],
            $data['email'],
            $data['role'] ?? 'user',
            'api_key'
        );
    }
}

// Utilisation
$auth = new AuthManager();
$auth->addStrategy('jwt', new JwtAuthStrategy($jwt));
$auth->addStrategy('api_key', new ApiKeyAuthStrategy($apiKeyService));

// Dans les routes
$router->get('/protected', function() use ($auth) {
    $user = $auth->requireAuth();
    return ['message' => "Bonjour {$user->email}, authentifié via {$user->authMethod}"];
});
?>
```

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
