---
source_course: "php-performance"
source_lesson: "php-performance-load-balancing"
---

# Équilibrage de Charge des Applications PHP

L'équilibrage de charge distribue le trafic entrant sur plusieurs serveurs PHP pour améliorer les performances et la fiabilité.

## Stratégies d'Équilibrage de Charge

### Round Robin

```nginx
# nginx.conf
upstream php_servers {
    server 192.168.1.1:9000;
    server 192.168.1.2:9000;
    server 192.168.1.3:9000;
}
```

### Distribution Pondérée

```nginx
upstream php_servers {
    server 192.168.1.1:9000 weight=3;  # Reçoit 3× le trafic
    server 192.168.1.2:9000 weight=2;
    server 192.168.1.3:9000 weight=1;
}
```

### Moins de Connexions

```nginx
upstream php_servers {
    least_conn;
    server 192.168.1.1:9000;
    server 192.168.1.2:9000;
}
```

### Hachage IP (Sessions Collantes)

```nginx
upstream php_servers {
    ip_hash;  # La même IP va toujours sur le même serveur
    server 192.168.1.1:9000;
    server 192.168.1.2:9000;
}
```

## Vérifications de Santé

```php
<?php
// Endpoint /health
header('Content-Type: application/json');

try {
    // Vérifier la base de données
    $pdo = new PDO($dsn);
    $pdo->query('SELECT 1');

    // Vérifier le cache
    $redis = new Redis();
    $redis->ping();

    // Vérifier l'espace disque
    $freeSpace = disk_free_space('/');
    if ($freeSpace < 1024 * 1024 * 100) {  // < 100Mo
        throw new Exception('Espace disque insuffisant');
    }

    echo json_encode([
        'status' => 'healthy',
        'timestamp' => time(),
    ]);

} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode([
        'status' => 'unhealthy',
        'error' => $e->getMessage(),
    ]);
}
```

```nginx
upstream php_servers {
    server 192.168.1.1:9000;
    server 192.168.1.2:9000;

    # Vérification de santé
    health_check interval=5s passes=2 fails=3 uri=/health;
}
```

## Dégradation Gracieuse

```php
<?php
class ResilientService
{
    public function getData(): array
    {
        try {
            return $this->fetchFromPrimary();
        } catch (Throwable $e) {
            // Logger l'erreur
            error_log('Primaire échoué : ' . $e->getMessage());

            // Essayer le fallback
            return $this->fetchFromCache();
        }
    }

    public function processRequest(): Response
    {
        // Pattern coupe-circuit
        if ($this->circuitBreaker->isOpen('external-api')) {
            return $this->fallbackResponse();
        }

        try {
            $result = $this->callExternalApi();
            $this->circuitBreaker->recordSuccess('external-api');
            return $result;

        } catch (Throwable $e) {
            $this->circuitBreaker->recordFailure('external-api');
            return $this->fallbackResponse();
        }
    }
}
```

## Déploiements Sans Interruption

```bash
#!/bin/bash
# deploy.sh — Déploiement progressif

SERVERS="server1 server2 server3"

for server in $SERVERS; do
    echo "Déploiement sur $server..."

    # Retirer de l'équilibreur de charge
    ssh $server 'touch /var/www/maintenance.flag'
    sleep 5  # Attendre les requêtes en cours

    # Déployer le nouveau code
    ssh $server 'cd /var/www && git pull && composer install --no-dev'

    # Vider OPcache
    ssh $server 'php -r "opcache_reset();"'

    # Redémarrer PHP-FPM
    ssh $server 'systemctl reload php-fpm'

    # Remettre dans l'équilibreur de charge
    ssh $server 'rm /var/www/maintenance.flag'

    echo "$server déployé avec succès"
    sleep 10  # Attendre avant le prochain serveur
done
```

## Les Grimoires

- [Équilibrage de Charge Nginx](https://nginx.org/en/docs/http/load_balancing.html)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
