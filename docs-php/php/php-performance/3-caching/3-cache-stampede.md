---
source_course: "php-performance"
source_lesson: "php-performance-cache-stampede"
---

# Prévenir le Cache Stampede

Un cache stampede (troupeau fulminant) se produit lorsque de nombreuses requêtes trouvent simultanément une entrée de cache expirée et essaient toutes de la régénérer.

## Le Problème

```php
<?php
// Scénario : page produit populaire, cache expiré
// 1000 utilisateurs simultanés accèdent à la page
// Les 1000 déclenchent une requête BDD coûteuse !

function getProduct(int $id): array
{
    $cached = $cache->get("product:$id");

    if ($cached === null) {
        // STAMPEDE : Toutes les requêtes s'exécutent simultanément !
        $product = $this->expensiveQuery($id);
        $cache->set("product:$id", $product, 3600);
    }

    return $cached;
}
```

## Solution 1 : Verrou

```php
<?php
class StampedeProtectedCache
{
    public function __construct(
        private CacheInterface $cache,
        private LockFactory $locks
    ) {}

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->cache->get($key);

        if ($value !== null) {
            return $value;
        }

        // Tenter d'acquérir le verrou
        $lock = $this->locks->createLock($key . ':lock', 30);

        if ($lock->acquire()) {
            try {
                // Double vérification après acquisition du verrou
                $value = $this->cache->get($key);
                if ($value !== null) {
                    return $value;
                }

                // Générer et mettre en cache
                $value = $callback();
                $this->cache->set($key, $value, $ttl);
                return $value;

            } finally {
                $lock->release();
            }
        }

        // Verrou non obtenu — attendre la valeur en cache
        return $this->waitForValue($key, 5);
    }

    private function waitForValue(string $key, int $maxWait): mixed
    {
        $start = time();

        while (time() - $start < $maxWait) {
            $value = $this->cache->get($key);
            if ($value !== null) {
                return $value;
            }
            usleep(100000); // 100ms
        }

        throw new RuntimeException('Valeur de cache non disponible');
    }
}
```

## Solution 2 : Expiration Anticipée Probabiliste

```php
<?php
class XFetchCache
{
    public function get(string $key, callable $callback, int $ttl): mixed
    {
        $cached = $this->cache->get($key);

        if ($cached !== null) {
            $data = unserialize($cached);

            // Recalcul anticipé probabiliste
            $delta = $data['delta'];
            $expiry = $data['expiry'];
            $now = time();

            // Algorithme XFetch : recalculer tôt selon la probabilité
            $random = -$delta * log(random_int(1, 1000) / 1000);

            if ($now + $random >= $expiry) {
                // Cette requête régénère le cache
                return $this->regenerate($key, $callback, $ttl);
            }

            return $data['value'];
        }

        return $this->regenerate($key, $callback, $ttl);
    }

    private function regenerate(string $key, callable $callback, int $ttl): mixed
    {
        $start = microtime(true);
        $value = $callback();
        $delta = microtime(true) - $start;

        $data = [
            'value' => $value,
            'delta' => $delta,
            'expiry' => time() + $ttl,
        ];

        $this->cache->set($key, serialize($data), $ttl + 60);

        return $value;
    }
}
```

## Solution 3 : Rafraîchissement en Arrière-Plan

```php
<?php
class BackgroundRefreshCache
{
    public function get(string $key, callable $callback, int $ttl): mixed
    {
        $data = $this->cache->get($key);

        if ($data !== null) {
            $meta = $this->cache->get($key . ':meta');

            // Vérifier si on doit rafraîchir en fond (ex. 80% du TTL écoulé)
            if ($meta && time() > $meta['refresh_at']) {
                $this->queueRefresh($key, $callback, $ttl);
            }

            return $data; // Retourner les données périmées immédiatement
        }

        // Défaut de cache — régénérer de manière synchrone
        return $this->regenerate($key, $callback, $ttl);
    }

    private function queueRefresh(string $key, callable $callback, int $ttl): void
    {
        // Ne pas mettre en file si déjà en cours
        if ($this->cache->get($key . ':refreshing')) {
            return;
        }

        $this->cache->set($key . ':refreshing', true, 60);

        // Mettre en file une tâche en arrière-plan
        $this->queue->push('cache:refresh', [
            'key' => $key,
            'callback' => serialize($callback),
            'ttl' => $ttl,
        ]);
    }
}
```

## Les Grimoires

- [Cache Stampede](https://en.wikipedia.org/wiki/Cache_stampede)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
