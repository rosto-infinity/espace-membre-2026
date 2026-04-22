---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-magic-methods"
---

# Les Méthodes Magiques

Les méthodes magiques sont des méthodes spéciales que **PHP appelle automatiquement** dans certaines situations. Elles commencent toutes par un double underscore (`__`).

## Constructeur & Destructeur

```php
<?php
class DatabaseConnection {
    private PDO $connection;

    public function __construct(string $dsn) {
        echo "Ouverture de la connexion\n";
        $this->connection = new PDO($dsn);
    }

    public function __destruct() {
        echo "Fermeture de la connexion\n";
        // La connexion est automatiquement fermée
    }
}
```

## Surcharge de Propriétés

```php
<?php
class DynamicObject {
    private array $data = [];

    public function __get(string $name): mixed {
        return $this->data[$name] ?? null;
    }

    public function __set(string $name, mixed $value): void {
        $this->data[$name] = $value;
    }

    public function __isset(string $name): bool {
        return isset($this->data[$name]);
    }

    public function __unset(string $name): void {
        unset($this->data[$name]);
    }
}

$obj = new DynamicObject();
$obj->name = 'Jean';     // Appelle __set
echo $obj->name;         // Appelle __get
var_dump(isset($obj->name));  // Appelle __isset
unset($obj->name);       // Appelle __unset
```

## Surcharge de Méthodes

```php
<?php
class ApiClient {
    public function __call(string $method, array $args): mixed {
        // Convertit getUsers() en GET /users
        if (str_starts_with($method, 'get')) {
            $endpoint = strtolower(substr($method, 3));
            return $this->request('GET', "/$endpoint", $args[0] ?? []);
        }

        throw new BadMethodCallException("Méthode $method introuvable");
    }

    public static function __callStatic(string $method, array $args): mixed {
        return (new self())->$method(...$args);
    }

    private function request(string $method, string $endpoint, array $params): array {
        return ['method' => $method, 'endpoint' => $endpoint];
    }
}

$client = new ApiClient();
$users = $client->getUsers();  // GET /users
$posts = ApiClient::getPosts(); // Appel statique
```

## Conversion en Chaîne

```php
<?php
class Money {
    public function __construct(
        private int $cents,
        private string $currency = 'EUR'
    ) {}

    public function __toString(): string {
        return sprintf('%s %.2f', $this->currency, $this->cents / 100);
    }
}

$price = new Money(1999);
echo $price;  // "EUR 19.99"
```

## Clonage

```php
<?php
class Order {
    public function __construct(
        public int $id,
        public array $items,
        public DateTime $createdAt
    ) {}

    public function __clone(): void {
        // Clonage profond de l'objet DateTime
        $this->createdAt = clone $this->createdAt;
        $this->id = 0;  // Réinitialiser l'ID pour le clone
    }
}

$order1 = new Order(1, ['item1'], new DateTime());
$order2 = clone $order1;
$order2->id;  // 0 (remis à zéro par __clone)
```

## Sérialisation

```php
<?php
class User {
    public function __construct(
        public string $name,
        public string $password  // Ne pas sérialiser ceci !
    ) {}

    public function __sleep(): array {
        // Sérialiser seulement ces propriétés
        return ['name'];
    }

    public function __wakeup(): void {
        // Réinitialiser après désérialisation
        $this->password = '';
    }
}
```

## Exemple Concret

**Un constructeur de requêtes fluent avec les méthodes magiques**

```php
<?php
declare(strict_types=1);

// Constructeur de requêtes fluent avec méthodes magiques
class QueryBuilder {
    private string $table = '';
    private array $wheres = [];
    private array $selects = ['*'];
    private ?int $limit = null;

    public function __call(string $method, array $args): self {
        // where{Column}($value) -> where(column, value)
        if (str_starts_with($method, 'where')) {
            $column = strtolower(substr($method, 5));
            return $this->where($column, $args[0]);
        }

        throw new BadMethodCallException("Méthode $method introuvable");
    }

    public function table(string $table): self {
        $this->table = $table;
        return $this;
    }

    public function select(string ...$columns): self {
        $this->selects = $columns;
        return $this;
    }

    public function where(string $column, mixed $value): self {
        $this->wheres[] = [$column, $value];
        return $this;
    }

    public function limit(int $limit): self {
        $this->limit = $limit;
        return $this;
    }

    public function __toString(): string {
        $sql = 'SELECT ' . implode(', ', $this->selects);
        $sql .= ' FROM ' . $this->table;

        if ($this->wheres) {
            $conditions = array_map(
                fn($w) => "{$w[0]} = '{$w[1]}'",
                $this->wheres
            );
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;
        }

        return $sql;
    }
}

$query = (new QueryBuilder())
    ->table('users')
    ->select('id', 'name', 'email')
    ->whereStatus('active')  // Méthode magique !
    ->whereRole('admin')     // Méthode magique !
    ->limit(10);

echo $query;
// SELECT id, name, email FROM users WHERE status = 'active' AND role = 'admin' LIMIT 10
?>
```

## Les Grimoires

- [Méthodes Magiques (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.magic.php)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
