---
source_course: "php-performance"
source_lesson: "php-performance-opcache-config"
---

# Configurer OPcache

OPcache stocke le bytecode précompilé en mémoire, éliminant le besoin de parser les fichiers PHP à chaque requête.

## Fonctionnement d'OPcache

```
Sans OPcache :
[Requête] → [Lire Fichier] → [Parser] → [Compiler] → [Exécuter]
                                          ↑
                                  (Chaque requête)

Avec OPcache :
[Requête] → [Lire depuis Cache] → [Exécuter]
                   ↑
           (Compilé une seule fois)
```

## Activer OPcache

```ini
; php.ini
zend_extension=opcache
opcache.enable=1
opcache.enable_cli=0  ; Activer pour scripts CLI si nécessaire
```

## Configuration de Production

```ini
; Allocation mémoire
opcache.memory_consumption=256      ; Mo de mémoire pour les scripts en cache
opcache.interned_strings_buffer=32  ; Mo pour les chaînes internées
opcache.max_accelerated_files=20000 ; Nombre max de fichiers en cache

; Paramètres de performance
opcache.validate_timestamps=0       ; Ne pas vérifier les changements de fichiers (production !)
opcache.revalidate_freq=0           ; Ignoré quand validate_timestamps=0

; Optimisation
opcache.optimization_level=0x7FFFFFFF  ; Toutes les optimisations
opcache.save_comments=0                 ; Supprimer les commentaires (si pas de réflexion)
opcache.enable_file_override=1          ; Opérations fichiers plus rapides

; Sécurité
opcache.restrict_api=''             ; Restreindre l'API à des chemins spécifiques
```

## Configuration de Développement

```ini
; Vérifier les changements de fichiers pour mises à jour immédiates
opcache.validate_timestamps=1
opcache.revalidate_freq=2  ; Vérifier toutes les 2 secondes
```

## Préchargement (PHP 7.4+)

```php
<?php
// preload.php
$files = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/src/Entity/User.php',
    __DIR__ . '/src/Entity/Order.php',
    // Ajouter les classes fréquemment utilisées
];

foreach ($files as $file) {
    opcache_compile_file($file);
}
```

```ini
; php.ini
opcache.preload=/var/www/app/preload.php
opcache.preload_user=www-data
```

## Surveiller OPcache

```php
<?php
function opcacheStats(): array
{
    if (!function_exists('opcache_get_status')) {
        return ['error' => 'OPcache non disponible'];
    }

    $status = opcache_get_status();

    return [
        'enabled' => $status['opcache_enabled'],
        'memory_used_mb' => $status['memory_usage']['used_memory'] / 1024 / 1024,
        'memory_free_mb' => $status['memory_usage']['free_memory'] / 1024 / 1024,
        'hit_rate' => $status['opcache_statistics']['opcache_hit_rate'],
        'scripts_cached' => $status['opcache_statistics']['num_cached_scripts'],
        'restarts' => $status['opcache_statistics']['oom_restarts'],
    ];
}
```

## Vider OPcache

```php
<?php
// Vider tout le cache
opcache_reset();

// Invalider un fichier spécifique
opcache_invalidate('/path/to/file.php', true);

// Script de déploiement
if (opcache_get_status()) {
    opcache_reset();
    echo "OPcache vidé\n";
}
```

## Les Grimoires

- [Documentation OPcache](https://www.php.net/manual/en/book.opcache.php)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
