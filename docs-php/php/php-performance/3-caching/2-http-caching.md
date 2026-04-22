---
source_course: "php-performance"
source_lesson: "php-performance-http-caching"
---

# Mise en Cache HTTP

La mise en cache HTTP réduit la charge serveur en permettant aux navigateurs et CDN de mettre les réponses en cache.

## En-tête Cache-Control

```php
<?php
// Cache public (CDN, navigateur)
header('Cache-Control: public, max-age=3600');  // 1 heure

// Cache privé (navigateur uniquement)
header('Cache-Control: private, max-age=300');  // 5 minutes

// Pas de mise en cache
header('Cache-Control: no-cache, no-store, must-revalidate');

// Périmé pendant revalidation
header('Cache-Control: public, max-age=3600, stale-while-revalidate=60');
```

## Validation ETag

```php
<?php
class ETagMiddleware
{
    public function handle(string $content): string
    {
        $etag = md5($content);

        header('ETag: "' . $etag . '"');

        // Vérifier si le client a la version actuelle
        $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';

        if ($clientEtag === '"' . $etag . '"') {
            http_response_code(304);  // Non Modifié
            exit;
        }

        return $content;
    }
}
```

## Validation Last-Modified

```php
<?php
function serveWithLastModified(string $filePath): void
{
    $lastModified = filemtime($filePath);
    $lastModifiedGmt = gmdate('D, d M Y H:i:s T', $lastModified);

    header('Last-Modified: ' . $lastModifiedGmt);

    // Vérifier l'en-tête If-Modified-Since
    $ifModifiedSince = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;

    if ($ifModifiedSince && strtotime($ifModifiedSince) >= $lastModified) {
        http_response_code(304);
        exit;
    }

    readfile($filePath);
}
```

## Mise en Cache de Page Complète

```php
<?php
class PageCache
{
    private string $cacheDir = '/var/cache/pages';

    public function get(string $url): ?string
    {
        $path = $this->getCachePath($url);

        if (file_exists($path) && $this->isValid($path)) {
            return file_get_contents($path);
        }

        return null;
    }

    public function set(string $url, string $content, int $ttl = 3600): void
    {
        $path = $this->getCachePath($url);
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Stocker avec métadonnées
        $data = [
            'expires' => time() + $ttl,
            'content' => $content,
        ];

        file_put_contents($path, serialize($data));
    }

    private function isValid(string $path): bool
    {
        $data = unserialize(file_get_contents($path));
        return $data['expires'] > time();
    }

    private function getCachePath(string $url): string
    {
        return $this->cacheDir . '/' . md5($url) . '.cache';
    }
}

// Utilisation
$pageCache = new PageCache();
$url = $_SERVER['REQUEST_URI'];

$cached = $pageCache->get($url);
if ($cached) {
    echo $cached;
    exit;
}

ob_start();
// ... générer la page ...
$content = ob_get_clean();

$pageCache->set($url, $content);
echo $content;
```

## Stratégie de Cache Multicouche

```php
<?php
declare(strict_types=1);

interface CacheInterface {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): void;
    public function delete(string $key): void;
}

class ApcuCache implements CacheInterface {
    public function get(string $key): mixed {
        $value = apcu_fetch($key, $success);
        return $success ? $value : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void {
        apcu_store($key, $value, $ttl);
    }

    public function delete(string $key): void {
        apcu_delete($key);
    }
}

class RedisCache implements CacheInterface {
    public function __construct(private Redis $redis) {}

    public function get(string $key): mixed {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void {
        $this->redis->setex($key, $ttl, serialize($value));
    }

    public function delete(string $key): void {
        $this->redis->del($key);
    }
}

// Multicouche : vérifier le cache local d'abord, puis le cache distribué
class LayeredCache implements CacheInterface {
    /** @param CacheInterface[] $layers */
    public function __construct(private array $layers) {}

    public function get(string $key): mixed {
        foreach ($this->layers as $i => $cache) {
            $value = $cache->get($key);

            if ($value !== null) {
                // Remplir les caches plus rapides
                for ($j = 0; $j < $i; $j++) {
                    $this->layers[$j]->set($key, $value, 60);  // TTL court pour local
                }
                return $value;
            }
        }

        return null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void {
        foreach ($this->layers as $cache) {
            $cache->set($key, $value, $ttl);
        }
    }

    public function delete(string $key): void {
        foreach ($this->layers as $cache) {
            $cache->delete($key);
        }
    }
}

// Utilisation
$cache = new LayeredCache([
    new ApcuCache(),        // L1 : Mémoire locale (plus rapide)
    new RedisCache($redis), // L2 : Cache distribué (partagé)
]);

$user = $cache->get('user:123');
if (!$user) {
    $user = $db->find(123);
    $cache->set('user:123', $user, 3600);
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
