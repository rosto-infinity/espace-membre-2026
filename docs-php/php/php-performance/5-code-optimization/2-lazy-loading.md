---
source_course: "php-performance"
source_lesson: "php-performance-lazy-loading"
---

# Chargement Paresseux et Exécution Différée

Ne faites le travail que lorsque vous en avez besoin.

## Initialisation Paresseuse d'Objets

```php
<?php
class ServiceContainer
{
    private array $services = [];
    private array $factories = [];

    public function register(string $name, callable $factory): void
    {
        $this->factories[$name] = $factory;
    }

    public function get(string $name): object
    {
        // Instancier seulement si demandé
        if (!isset($this->services[$name])) {
            $this->services[$name] = ($this->factories[$name])();
        }

        return $this->services[$name];
    }
}

$container = new ServiceContainer();

// Enregistrer des fabriques (peu coûteux)
$container->register('database', fn() => new PDO(...));
$container->register('mailer', fn() => new Mailer(...));
$container->register('logger', fn() => new Logger(...));

// Seul le mailer est réellement instancié
$mailer = $container->get('mailer');
```

## Générateurs pour l'Itération Paresseuse

```php
<?php
// Eager : Charge tous les utilisateurs en mémoire
function getAllUsers(): array {
    return $pdo->query('SELECT * FROM users')->fetchAll();
}

// Paresseux : Un utilisateur à la fois
function getAllUsers(): Generator {
    $stmt = $pdo->query('SELECT * FROM users');
    while ($row = $stmt->fetch()) {
        yield new User($row);
    }
}

// Traiter un million d'utilisateurs avec une mémoire minimale
foreach (getAllUsers() as $user) {
    processUser($user);
}
```

## Collections Paresseuses

```php
<?php
class LazyCollection implements IteratorAggregate
{
    private array $operations = [];

    public function __construct(
        private iterable $source
    ) {}

    public function map(callable $fn): self
    {
        $this->operations[] = ['map', $fn];
        return $this;
    }

    public function filter(callable $fn): self
    {
        $this->operations[] = ['filter', $fn];
        return $this;
    }

    public function getIterator(): Generator
    {
        foreach ($this->source as $item) {
            $include = true;

            foreach ($this->operations as [$op, $fn]) {
                if ($op === 'map') {
                    $item = $fn($item);
                } elseif ($op === 'filter') {
                    if (!$fn($item)) {
                        $include = false;
                        break;
                    }
                }
            }

            if ($include) {
                yield $item;
            }
        }
    }

    // C'est seulement ici qu'on itère réellement
    public function toArray(): array
    {
        return iterator_to_array($this);
    }
}

// Les opérations sont chaînées mais pas exécutées
$result = (new LazyCollection($hugeDataset))
    ->filter(fn($x) => $x > 0)
    ->map(fn($x) => $x * 2)
    ->filter(fn($x) => $x < 100);

// C'est seulement maintenant que le traitement se produit
foreach ($result as $item) {
    echo $item;
}
```

## Calculs Différés

```php
<?php
class Report
{
    private ?array $data = null;

    // Calcul coûteux différé
    public function getData(): array
    {
        if ($this->data === null) {
            $this->data = $this->calculateExpensiveReport();
        }
        return $this->data;
    }

    // L'utilisateur peut ne jamais avoir besoin de ça
    private function calculateExpensiveReport(): array
    {
        // Requêtes et calculs complexes
        return [...];
    }
}
```

## Pipeline Paresseux

```php
<?php
declare(strict_types=1);

class Pipeline
{
    /** @var callable[] */
    private array $stages = [];

    public static function from(iterable $source): self
    {
        $pipeline = new self();
        $pipeline->stages[] = fn() => yield from $source;
        return $pipeline;
    }

    public function map(callable $fn): self
    {
        $prev = $this->stages;
        $this->stages[] = function() use ($prev, $fn) {
            foreach ($this->execute($prev) as $item) {
                yield $fn($item);
            }
        };
        return $this;
    }

    public function filter(callable $fn): self
    {
        $prev = $this->stages;
        $this->stages[] = function() use ($prev, $fn) {
            foreach ($this->execute($prev) as $item) {
                if ($fn($item)) {
                    yield $item;
                }
            }
        };
        return $this;
    }

    public function take(int $n): self
    {
        $prev = $this->stages;
        $this->stages[] = function() use ($prev, $n) {
            $count = 0;
            foreach ($this->execute($prev) as $item) {
                if ($count >= $n) break;
                yield $item;
                $count++;
            }
        };
        return $this;
    }

    public function chunk(int $size): self
    {
        $prev = $this->stages;
        $this->stages[] = function() use ($prev, $size) {
            $chunk = [];
            foreach ($this->execute($prev) as $item) {
                $chunk[] = $item;
                if (count($chunk) === $size) {
                    yield $chunk;
                    $chunk = [];
                }
            }
            if ($chunk) yield $chunk;
        };
        return $this;
    }

    private function execute(array $stages): Generator
    {
        $current = null;
        foreach ($stages as $stage) {
            $current = $stage();
        }
        yield from $current ?? [];
    }

    public function toArray(): array
    {
        return iterator_to_array($this->run());
    }

    public function run(): Generator
    {
        yield from $this->execute($this->stages);
    }

    public function each(callable $fn): void
    {
        foreach ($this->run() as $item) {
            $fn($item);
        }
    }

    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        $result = $initial;
        foreach ($this->run() as $item) {
            $result = $fn($result, $item);
        }
        return $result;
    }
}

// Utilisation : Traiter un million d'enregistrements avec une mémoire minimale
$total = Pipeline::from(readLargeFile('sales.csv'))
    ->map(fn($line) => str_getcsv($line))
    ->filter(fn($row) => $row[2] === 'completed')
    ->map(fn($row) => (float) $row[3])
    ->reduce(fn($sum, $amount) => $sum + $amount, 0.0);

echo "Total des ventes : $total";
?>
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
