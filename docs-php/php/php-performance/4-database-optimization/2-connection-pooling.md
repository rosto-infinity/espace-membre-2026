---
source_course: "php-performance"
source_lesson: "php-performance-connection-pooling"
---

# Gestion des Connexions

Les connexions base de données sont coûteuses. Gérez-les judicieusement.

## Pool de Connexions

```php
<?php
// Pattern Singleton pour la réutilisation des connexions
class Database
{
    private static ?PDO $instance = null;

    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = new PDO(
                $_ENV['DATABASE_URL'],
                $_ENV['DATABASE_USER'],
                $_ENV['DATABASE_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_PERSISTENT => true,  // Connexion persistante
                ]
            );
        }

        return self::$instance;
    }
}
```

## Connexions Persistantes

```php
<?php
// Activer les connexions persistantes
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_PERSISTENT => true,
]);

// Avantages :
// - Évite le coût de connexion par requête
// - Réutilise les connexions existantes

// Précautions :
// - Les connexions persistent entre les requêtes
// - Nettoyage de l'état nécessaire (tables temporaires, verrous)
// - Peut nécessiter d'augmenter max_connections
```

## Délais d'Expiration de Connexion

```php
<?php
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_TIMEOUT => 5,  // Délai de 5 secondes
]);

// Pour MySQL spécifiquement
$pdo->exec('SET wait_timeout = 28800');  // 8 heures
$pdo->exec('SET interactive_timeout = 28800');
```

## Réplicas de Lecture

```php
<?php
class DatabaseManager
{
    private PDO $writer;
    private array $readers = [];

    public function __construct(string $writerDsn, array $readerDsns)
    {
        $this->writer = new PDO($writerDsn);

        foreach ($readerDsns as $dsn) {
            $this->readers[] = new PDO($dsn);
        }
    }

    public function write(): PDO
    {
        return $this->writer;
    }

    public function read(): PDO
    {
        // Round-robin ou sélection aléatoire
        return $this->readers[array_rand($this->readers)];
    }
}

// Utilisation
$db = new DatabaseManager(
    'mysql:host=primary.db;dbname=app',
    [
        'mysql:host=replica1.db;dbname=app',
        'mysql:host=replica2.db;dbname=app',
    ]
);

// Les lectures vont vers les réplicas (scalable)
$users = $db->read()->query('SELECT * FROM users');

// Les écritures vont vers le primaire
$db->write()->exec('INSERT INTO users ...');
```

## Constructeur de Requêtes Optimisé

```php
<?php
declare(strict_types=1);

class OptimizedQueryBuilder
{
    private PDO $pdo;
    private string $table;
    private array $select = ['*'];
    private array $where = [];
    private array $bindings = [];
    private ?int $limit = null;
    private ?int $offset = null;
    private array $orderBy = [];
    private ?string $indexHint = null;

    public function __construct(PDO $pdo, string $table)
    {
        $this->pdo = $pdo;
        $this->table = $table;
    }

    public function select(string ...$columns): self
    {
        $this->select = $columns ?: ['*'];
        return $this;
    }

    public function where(string $column, mixed $value, string $operator = '='): self
    {
        $param = ':w' . count($this->bindings);
        $this->where[] = "$column $operator $param";
        $this->bindings[$param] = $value;
        return $this;
    }

    public function useIndex(string $indexName): self
    {
        $this->indexHint = "USE INDEX ($indexName)";
        return $this;
    }

    public function forceIndex(string $indexName): self
    {
        $this->indexHint = "FORCE INDEX ($indexName)";
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orderBy[] = "$column $direction";
        return $this;
    }

    public function toSql(): string
    {
        $sql = 'SELECT ' . implode(', ', $this->select);
        $sql .= ' FROM ' . $this->table;

        if ($this->indexHint) {
            $sql .= ' ' . $this->indexHint;
        }

        if ($this->where) {
            $sql .= ' WHERE ' . implode(' AND ', $this->where);
        }

        if ($this->orderBy) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit !== null) {
            $sql .= ' LIMIT ' . $this->limit;
            if ($this->offset !== null) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        return $sql;
    }

    public function explain(): array
    {
        $stmt = $this->pdo->prepare('EXPLAIN ' . $this->toSql());
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(): array
    {
        $stmt = $this->pdo->prepare($this->toSql());
        $stmt->execute($this->bindings);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Utilisation
$query = new OptimizedQueryBuilder($pdo, 'orders');

$orders = $query
    ->select('id', 'total', 'created_at')
    ->where('user_id', 123)
    ->where('status', 'completed')
    ->forceIndex('idx_user_status')  // Forcer un index spécifique
    ->orderBy('created_at', 'DESC')
    ->limit(10)
    ->get();

// Debug : vérifier le plan d'exécution
print_r($query->explain());
?>
```

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
