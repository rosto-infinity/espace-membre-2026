---
source_course: "php-performance"
source_lesson: "php-performance-profiling-basics"
---

# Mesurer les Performances PHP

Avant d'optimiser, mesurez. Profilez votre code pour trouver les vrais goulots d'étranglement.

## Mesure du Temps de Base

```php
<?php
$start = microtime(true);

// Code à mesurer
for ($i = 0; $i < 10000; $i++) {
    $result = someFunction($i);
}

$end = microtime(true);
$duration = ($end - $start) * 1000;  // Convertir en millisecondes

echo "Temps d'exécution : {$duration}ms\n";
```

## Utilisation de la Mémoire

```php
<?php
$memStart = memory_get_usage();

// Code qui alloue de la mémoire
$data = range(1, 100000);

$memEnd = memory_get_usage();
$memUsed = ($memEnd - $memStart) / 1024 / 1024;  // Mo

echo "Mémoire utilisée : {$memUsed}Mo\n";
echo "Mémoire de pointe : " . (memory_get_peak_usage() / 1024 / 1024) . "Mo\n";
```

## Classe de Benchmark

```php
<?php
class Benchmark
{
    private float $startTime;
    private int $startMemory;
    private array $markers = [];

    public function start(): void
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    public function mark(string $name): void
    {
        $this->markers[$name] = [
            'time' => microtime(true) - $this->startTime,
            'memory' => memory_get_usage() - $this->startMemory,
        ];
    }

    public function report(): array
    {
        return [
            'total_time_ms' => (microtime(true) - $this->startTime) * 1000,
            'total_memory_mb' => (memory_get_usage() - $this->startMemory) / 1024 / 1024,
            'peak_memory_mb' => memory_get_peak_usage() / 1024 / 1024,
            'markers' => $this->markers,
        ];
    }
}

// Utilisation
$bench = new Benchmark();
$bench->start();

$users = fetchUsers();
$bench->mark('fetch_users');

$processed = processUsers($users);
$bench->mark('process_users');

print_r($bench->report());
```

## Profilage avec Xdebug

```ini
; php.ini
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug
xdebug.profiler_output_name=cachegrind.out.%p
```

```php
<?php
// Déclencher le profilage pour un code spécifique
if (function_exists('xdebug_start_trace')) {
    xdebug_start_trace('/tmp/trace');
}

// Votre code ici

if (function_exists('xdebug_stop_trace')) {
    xdebug_stop_trace();
}
```

## Métriques Clés

| Métrique            | Ce Qu'elle Mesure                | Outil              |
| ------------------- | -------------------------------- | ------------------ |
| Temps de Réponse    | Durée totale de la requête       | Timer, APM         |
| Utilisation Mémoire | Consommation RAM                 | memory_get_usage() |
| Temps CPU           | Temps de traitement              | Xdebug, Blackfire  |
| Attente I/O         | Base de données, fichier, réseau | APM, profileurs    |
| Débit               | Requêtes par seconde             | Tests de charge    |

## Les Grimoires

- [Profilage Xdebug](https://xdebug.org/docs/profiler)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
