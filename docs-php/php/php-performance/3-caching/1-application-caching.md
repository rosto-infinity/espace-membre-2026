---
source_course: "php-performance"
source_lesson: "php-performance-application-caching"
---

# Mise en Cache au Niveau Application

La mise en cache stocke les résultats calculés pour éviter les traitements redondants.

## APCu (Cache en Mémoire)

```php
<?php
// Stocker en cache
apcu_store('user_123', $userData, 3600);  // TTL 1 heure

// Récupérer du cache
$cached = apcu_fetch('user_123', $success);
if ($success) {
    return $cached;
}

// Supprimer du cache
apcu_delete('user_123');

// Vérifier l'existence
if (apcu_exists('user_123')) {
    // ...
}
```

## Pattern Cache-Aside

```php
<?php
class UserRepository
{
    public function find(int $id): ?User
    {
        $key = "user:$id";

        // Essayer le cache d'abord
        $cached = apcu_fetch($key, $success);
        if ($success) {
            return $cached;
        }

        // Défaut de cache — récupérer depuis la base de données
        $user = $this->fetchFromDatabase($id);

        if ($user) {
            apcu_store($key, $user, 3600);
        }

        return $user;
    }

    public function update(User $user): void
    {
        $this->saveToDatabase($user);

        // Invalider le cache
        apcu_delete("user:{$user->id}");
    }
}
```

## Mise en Cache avec Redis

```php
<?php
class RedisCache
{
    private Redis $redis;

    public function __construct(string $host = '127.0.0.1', int $port = 6379)
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);
    }

    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $this->redis->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): void
    {
        $this->redis->del($key);
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $cached = $this->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }
}

// Utilisation
$cache = new RedisCache();

$users = $cache->remember('active_users', 300, function() use ($repo) {
    return $repo->findAllActive();
});
```

## Tags de Cache (Groupes d'Invalidation)

```php
<?php
class TaggedCache
{
    private Redis $redis;

    public function set(string $key, mixed $value, int $ttl, array $tags = []): void
    {
        $this->redis->setex($key, $ttl, serialize($value));

        // Suivre les clés par tag
        foreach ($tags as $tag) {
            $this->redis->sAdd("tag:$tag", $key);
        }
    }

    public function invalidateTag(string $tag): void
    {
        $keys = $this->redis->sMembers("tag:$tag");

        if ($keys) {
            $this->redis->del(...$keys);
            $this->redis->del("tag:$tag");
        }
    }
}

// Utilisation
$cache->set('user:1', $user1, 3600, ['users', 'user:1']);
$cache->set('user:2', $user2, 3600, ['users', 'user:2']);
$cache->set('user_list', $allUsers, 3600, ['users']);

// Invalider tout le cache utilisateur
$cache->invalidateTag('users');
```

## Les Grimoires

- [APCu](https://www.php.net/manual/en/book.apcu.php)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
