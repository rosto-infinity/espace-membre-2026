---
source_course: "php-performance"
source_lesson: "php-performance-preloading-advanced"
---

# Stratégies de Préchargement Avancé

Le préchargement (PHP 7.4+) charge des fichiers PHP dans OPcache au démarrage du serveur, les rendant en permanence disponibles sans recompilation.

## Comment Fonctionne le Préchargement

```
Démarrage Serveur → Exécuter preload.php → Classes en mémoire partagée
                                                   ↓
              Toutes les requêtes partagent les classes préchargées (coût zéro)
```

## Script de Préchargement de Base

```php
<?php
// preload.php

// Option 1 : Précharger des fichiers spécifiques
$files = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/src/Kernel.php',
    __DIR__ . '/src/Entity/User.php',
    __DIR__ . '/src/Entity/Order.php',
];

foreach ($files as $file) {
    require_once $file;
}
```

## Découverte Automatique de Classes

```php
<?php
// preload.php — Découvrir et précharger les classes fréquentes

class Preloader
{
    private array $loaded = [];
    private array $ignored = [];

    public function __construct(
        private array $paths,
        private array $ignorePatterns = []
    ) {}

    public function load(): int
    {
        foreach ($this->paths as $path) {
            $this->loadPath($path);
        }

        return count($this->loaded);
    }

    private function loadPath(string $path): void
    {
        if (is_file($path)) {
            $this->loadFile($path);
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $this->loadFile($file->getPathname());
            }
        }
    }

    private function loadFile(string $path): void
    {
        foreach ($this->ignorePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                $this->ignored[] = $path;
                return;
            }
        }

        try {
            require_once $path;
            $this->loaded[] = $path;
        } catch (Throwable $e) {
            // Logger mais ne pas échouer — certains fichiers peuvent avoir des dépendances
        }
    }

    public function getLoaded(): array
    {
        return $this->loaded;
    }
}

// Utilisation
$preloader = new Preloader(
    paths: [
        __DIR__ . '/src/Entity',
        __DIR__ . '/src/Service',
        __DIR__ . '/src/Repository',
    ],
    ignorePatterns: [
        '/Test\.php$/',
        '/Fixture/',
    ]
);

$count = $preloader->load();
error_log("Préchargé $count fichiers");
```

## Configuration

```ini
; php.ini
opcache.preload=/var/www/app/preload.php
opcache.preload_user=www-data

; S'assurer qu'OPcache est activé
opcache.enable=1
opcache.enable_cli=0
```

## Que Précharger

**Bons candidats :**

- Classes de base du framework
- Entités/modèles très utilisés
- Classes de services
- Utilitaires fréquents

**À éviter de précharger :**

- Fichiers de tests
- Commandes CLI uniquement
- Classes d'exceptions rarement utilisées
- Outils de développement

## Mesurer l'Impact

```php
<?php
// Vérifier les classes préchargées
$status = opcache_get_status();
$preloadStats = $status['preload_statistics'] ?? [];

echo "Fonctions préchargées : " . ($preloadStats['functions'] ?? 0) . "\n";
echo "Classes préchargées : " . ($preloadStats['classes'] ?? 0) . "\n";
echo "Mémoire utilisée : " . ($preloadStats['memory_consumption'] ?? 0) . " octets\n";
```

## Mises en Garde

1. **Redémarrage serveur requis** — Les changements nécessitent un redémarrage FPM/serveur
2. **Pas de rechargement à chaud** — En développement, le préchargement peut être désactivé
3. **Ordre des dépendances** — Les fichiers se chargent dans l'ordre ; les dépendances doivent être chargées en premier
4. **Consommation mémoire** — Les classes préchargées utilisent la mémoire même si inutilisées

## Les Grimoires

- [Documentation Préchargement OPcache](https://www.php.net/manual/en/opcache.preloading.php)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
