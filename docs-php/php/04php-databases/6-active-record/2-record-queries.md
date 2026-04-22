---
source_course: "php-databases"
source_lesson: "php-databases-active-record-queries"
---

# Méthodes de Requête & Relations

Étendons l'Active Record avec des **méthodes de requête fluentes et le support des relations**.

## Méthodes de Requête

```php
<?php
abstract class ActiveRecord {
    // ... code précédent ...

    public static function all(): array {
        $table = static::$table;
        $stmt = static::$pdo->query("SELECT * FROM $table");
        return array_map([static::class, 'hydrate'], $stmt->fetchAll());
    }

    public static function where(string $column, mixed $value, string $operator = '='): QueryBuilder {
        return (new QueryBuilder(static::$pdo, static::class))
            ->table(static::$table)
            ->where($column, $value, $operator);
    }

    public static function create(array $attributes): static {
        $instance = new static();
        foreach ($attributes as $key => $value) {
            $instance->$key = $value;
        }
        $instance->save();
        return $instance;
    }

    public function fill(array $attributes): static {
        foreach ($attributes as $key => $value) {
            if (in_array($key, static::$fillable, true)) {
                $this->attributes[$key] = $value;
            }
        }
        return $this;
    }

    public function toArray(): array {
        return $this->attributes;
    }
}
```

## Modèle Concret

```php
<?php
class User extends ActiveRecord {
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'password_hash'];

    // Relation : User possède plusieurs Orders
    public function orders(): array {
        return Order::where('user_id', $this->id)->get();
    }

    // Accesseur
    public function getDisplayName(): string {
        return ucfirst($this->name);
    }

    // Mutateur (appelé avant la sauvegarde)
    public function setPassword(string $password): void {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }
}

class Order extends ActiveRecord {
    protected static string $table = 'orders';
    protected static array $fillable = ['user_id', 'status', 'total'];

    // Relation : Order appartient à un User
    public function user(): ?User {
        return User::find($this->user_id);
    }

    // Relation : Order possède plusieurs Items
    public function items(): array {
        return OrderItem::where('order_id', $this->id)->get();
    }
}
```

## Exemples d'Utilisation

```php
<?php
// Établir la connexion
ActiveRecord::setConnection($pdo);

// Créer
$user = User::create([
    'name' => 'Jean Dupont',
    'email' => 'jean@example.com',
]);
$user->setPassword('secret123');
$user->save();

// Lire
$user = User::find(1);
echo $user->name;

// Requêter
$activeUsers = User::where('status', 'active')->get();
$recentOrders = Order::where('created_at', date('Y-m-d'), '>=' )->get();

// Mettre à jour
$user->name = 'Marie Dupont';
$user->save();

// Supprimer
$user->delete();

// Relations
$user = User::find(1);
foreach ($user->orders() as $order) {
    echo "Commande #{$order->id}: {$order->total} €\n";
    foreach ($order->items() as $item) {
        echo "  - {$item->product_name}\n";
    }
}
```

## Exemple Complet

**Active Record avec validation et hooks de cycle de vie**

```php
<?php
declare(strict_types=1);

// Active Record complet avec hooks et validation
abstract class ActiveRecord {
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    protected static array $fillable = [];
    protected static array $hidden = ['password_hash'];

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected array $errors = [];

    protected static ?PDO $pdo = null;

    public static function setConnection(PDO $pdo): void {
        static::$pdo = $pdo;
    }

    // Hooks — à surcharger dans les sous-classes
    protected function beforeSave(): void {}
    protected function afterSave(): void {}
    protected function beforeCreate(): void {}
    protected function afterCreate(): void {}
    protected function beforeUpdate(): void {}
    protected function afterUpdate(): void {}
    protected function beforeDelete(): void {}
    protected function afterDelete(): void {}
    protected function validate(): bool { return true; }

    public function save(): bool {
        $this->beforeSave();

        if (!$this->validate()) {
            return false;
        }

        if ($this->exists) {
            $this->beforeUpdate();
            $result = $this->performUpdate();
            if ($result) $this->afterUpdate();
        } else {
            $this->beforeCreate();
            $result = $this->performInsert();
            if ($result) $this->afterCreate();
        }

        if ($result) $this->afterSave();
        return $result;
    }

    public function getErrors(): array {
        return $this->errors;
    }

    public function toArray(): array {
        return array_diff_key($this->attributes, array_flip(static::$hidden));
    }

    public function toJson(): string {
        return json_encode($this->toArray());
    }
}

// Exemple de modèle avec validation et hooks
class User extends ActiveRecord {
    protected static string $table = 'users';
    protected static array $fillable = ['name', 'email', 'status'];
    protected static array $hidden = ['password_hash'];

    protected function validate(): bool {
        $this->errors = [];

        if (empty($this->attributes['name'])) {
            $this->errors['name'] = 'Le nom est requis';
        }

        if (empty($this->attributes['email'])) {
            $this->errors['email'] = 'L\'email est requis';
        } elseif (!filter_var($this->attributes['email'], FILTER_VALIDATE_EMAIL)) {
            $this->errors['email'] = 'Format d\'email invalide';
        }

        return empty($this->errors);
    }

    protected function beforeCreate(): void {
        $this->attributes['created_at'] = date('Y-m-d H:i:s');
        $this->attributes['status'] = $this->attributes['status'] ?? 'active';
    }

    protected function beforeUpdate(): void {
        $this->attributes['updated_at'] = date('Y-m-d H:i:s');
    }

    public function setPassword(string $password): void {
        $this->attributes['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword(string $password): bool {
        return password_verify($password, $this->attributes['password_hash'] ?? '');
    }
}

// Utilisation
$user = new User();
$user->name = 'Jean';
$user->email = 'invalide';

if (!$user->save()) {
    print_r($user->getErrors());
    // ['email' => 'Format d\'email invalide']
}

$user->email = 'jean@example.com';
$user->setPassword('secret123');
$user->save();  // Succès !

echo $user->toJson();
// {"id":1,"name":"Jean","email":"jean@example.com","status":"active"}
// Note : password_hash est masqué
?>
```

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
