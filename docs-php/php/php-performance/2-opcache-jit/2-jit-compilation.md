---
source_course: "php-performance"
source_lesson: "php-performance-jit-compilation"
---

# Compilation JIT en PHP 8

La compilation Just-In-Time convertit le bytecode PHP en code machine à l'exécution pour des performances maximales.

## JIT vs OPcache

```
OPcache :  Source → Bytecode → Exécution sur la VM Zend
JIT :      Source → Bytecode → Code Machine → Exécution sur le CPU
```

## Activer le JIT

```ini
; php.ini
opcache.enable=1  ; Obligatoire
opcache.jit=1255  ; Activer JIT
opcache.jit_buffer_size=128M
```

## Valeurs de Configuration JIT

La valeur `opcache.jit` a 4 chiffres : `CRTO`

```
C - Optimisation spécifique au CPU
  0 = pas d'optimisation
  1 = activer AVX

R - Allocation des registres
  0 = pas d'allocation de registres
  1 = balayage linéaire local
  2 = balayage linéaire global

T - Mode de déclenchement
  0 = compiler au chargement du script
  1 = compiler à la première exécution
  2 = compiler à la première exécution et profiler
  3 = compiler la fonction à la première exécution
  4 = compiler selon le nombre d'appels
  5 = compiler selon le nombre d'appels et tracer les fonctions chaudes

O - Niveau d'optimisation
  0 = pas de JIT
  1 = JIT minimal
  2 = JIT sélectif
  3 = JIT optimisé
  4 = JIT optimisé et spéculatif
  5 = JIT maximal
```

## Paramètres Recommandés

```ini
; JIT de trace (meilleur pour la plupart des apps)
opcache.jit=tracing
; Équivalent à : opcache.jit=1255

; JIT de fonctions (plus conservateur)
opcache.jit=function
; Équivalent à : opcache.jit=1205

; Désactiver JIT
opcache.jit=off
; Équivalent à : opcache.jit=0
```

## Quand le JIT Aide

```php
<?php
// JIT aide : calculs intensifs en CPU
function fibonacci(int $n): int {
    if ($n <= 1) return $n;
    return fibonacci($n - 1) + fibonacci($n - 2);
}

// JIT aide : boucles avec calculs
function processArray(array $data): float {
    $sum = 0;
    foreach ($data as $value) {
        $sum += sqrt($value) * log($value);
    }
    return $sum;
}

// JIT a moins d'impact : opérations liées aux I/O
function fetchData(): array {
    return $pdo->query('SELECT * FROM users')->fetchAll();
}
```

## Surveiller le JIT

```php
<?php
function jitStatus(): array
{
    $status = opcache_get_status();

    return [
        'enabled' => $status['jit']['enabled'],
        'on' => $status['jit']['on'],
        'kind' => $status['jit']['kind'],
        'buffer_size' => $status['jit']['buffer_size'],
        'buffer_free' => $status['jit']['buffer_free'],
    ];
}
```

## Comparaison de Benchmark

```php
<?php
// Sans JIT : ~2.5 secondes
// Avec JIT : ~0.5 secondes (5× plus rapide)
$start = microtime(true);
$result = fibonacci(35);
echo microtime(true) - $start;
```

## Benchmark de Performance JIT

```php
<?php
declare(strict_types=1);

class JitBenchmark
{
    public function run(): array
    {
        return [
            'fibonacci' => $this->benchFibonacci(),
            'mandelbrot' => $this->benchMandelbrot(),
            'string_ops' => $this->benchStringOps(),
            'array_ops' => $this->benchArrayOps(),
        ];
    }

    private function benchFibonacci(): float
    {
        $start = microtime(true);
        for ($i = 0; $i < 30; $i++) {
            $this->fibonacci(25);
        }
        return (microtime(true) - $start) * 1000;
    }

    private function fibonacci(int $n): int
    {
        if ($n <= 1) return $n;
        return $this->fibonacci($n - 1) + $this->fibonacci($n - 2);
    }

    private function benchMandelbrot(): float
    {
        $start = microtime(true);
        $size = 200;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $zr = $zi = 0;
                $cr = ($x / $size) * 3.5 - 2.5;
                $ci = ($y / $size) * 2 - 1;

                for ($i = 0; $i < 100; $i++) {
                    $temp = $zr * $zr - $zi * $zi + $cr;
                    $zi = 2 * $zr * $zi + $ci;
                    $zr = $temp;
                    if ($zr * $zr + $zi * $zi > 4) break;
                }
            }
        }

        return (microtime(true) - $start) * 1000;
    }

    private function benchStringOps(): float
    {
        $start = microtime(true);

        for ($i = 0; $i < 100000; $i++) {
            $str = 'Bonjour Monde ' . $i;
            $upper = strtoupper($str);
            $replaced = str_replace('MONDE', 'PHP', $upper);
            $len = strlen($replaced);
        }

        return (microtime(true) - $start) * 1000;
    }

    private function benchArrayOps(): float
    {
        $start = microtime(true);

        $data = range(1, 10000);

        for ($i = 0; $i < 100; $i++) {
            $filtered = array_filter($data, fn($n) => $n % 2 === 0);
            $mapped = array_map(fn($n) => $n * 2, $filtered);
            $sum = array_sum($mapped);
        }

        return (microtime(true) - $start) * 1000;
    }
}

// Lancer le benchmark
$bench = new JitBenchmark();
$results = $bench->run();

echo "JIT Status : " . (opcache_get_status()['jit']['on'] ? 'ACTIVÉ' : 'DÉSACTIVÉ') . "\n";
foreach ($results as $name => $ms) {
    printf("%s : %.2f ms\n", $name, $ms);
}
?>
```

## Les Grimoires

- [Configuration JIT PHP](https://www.php.net/manual/en/opcache.configuration.php#ini.opcache.jit)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
