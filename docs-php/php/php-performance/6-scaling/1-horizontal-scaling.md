---
source_course: "php-performance"
source_lesson: "php-performance-horizontal-scaling"
---

# Stratégies de Mise à l'Échelle Horizontale

Montez en charge en ajoutant davantage de serveurs plutôt que des serveurs plus puissants.

## Gestion des Sessions

```php
<?php
// Problème : Les sessions stockées localement ne fonctionnent pas avec plusieurs serveurs

// Solution 1 : Sessions collantes (affinité de l'équilibreur de charge)
// - Le même utilisateur va toujours sur le même serveur
// - Simple mais limite la flexibilité

// Solution 2 : Stockage centralisé des sessions (Redis)
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis.example.com:6379');

// Ou de manière programmatique
class RedisSessionHandler implements SessionHandlerInterface
{
    private Redis $redis;
    private int $ttl = 3600;

    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        return $this->redis->get("session:$id") ?: '';
    }

    public function write(string $id, string $data): bool
    {
        return $this->redis->setex("session:$id", $this->ttl, $data);
    }

    public function destroy(string $id): bool
    {
        return $this->redis->del("session:$id") > 0;
    }

    public function gc(int $max_lifetime): int|false
    {
        return 0;  // Redis gère l'expiration
    }
}

$handler = new RedisSessionHandler(new Redis());
session_set_save_handler($handler, true);
```

## Architecture Sans État Partagé

```php
<?php
// Chaque requête est indépendante
// - Pas de stockage de fichiers local
// - Pas de stockage de sessions local
// - Pas de mémoire partagée entre les requêtes

// Stocker les uploads dans un objet de stockage (S3)
class S3FileStorage implements FileStorage
{
    public function store(string $localPath, string $remotePath): string
    {
        $this->s3->putObject([
            'Bucket' => $this->bucket,
            'Key' => $remotePath,
            'SourceFile' => $localPath,
        ]);

        return $this->getPublicUrl($remotePath);
    }
}

// Stocker le cache dans Redis (partagé entre les serveurs)
class DistributedCache
{
    public function __construct(private Redis $redis) {}

    public function get(string $key): mixed { /* ... */ }
    public function set(string $key, mixed $value, int $ttl = 3600): void { /* ... */ }
}
```

## Mise à l'Échelle de la Base de Données

```php
<?php
// Réplicas de lecture pour la mise à l'échelle des lectures
class DatabaseCluster
{
    private PDO $master;
    private array $replicas = [];

    public function master(): PDO
    {
        return $this->master;
    }

    public function replica(): PDO
    {
        // Sélection en round-robin
        return $this->replicas[array_rand($this->replicas)];
    }
}

// Utilisation dans le référentiel
class UserRepository
{
    public function find(int $id): ?User
    {
        // Les lectures vont vers le réplica
        $stmt = $this->db->replica()->prepare(
            'SELECT * FROM users WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $this->hydrate($stmt->fetch());
    }

    public function save(User $user): void
    {
        // Les écritures vont vers le maître
        $stmt = $this->db->master()->prepare(
            'INSERT INTO users (name, email) VALUES (?, ?)'
        );
        $stmt->execute([$user->name, $user->email]);
    }
}
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
