---
source_course: "php-modern-features"
source_lesson: "php-modern-features-lazy-objects"
---

# Les Objets Paresseux - Lazy Objects (PHP 8.4)

Les objets paresseux (Lazy Objects) **diffèrent leur initialisation** jusqu'à ce qu'ils soient réellement utilisés. PHP 8.4 introduit le support natif des objets paresseux via l'API Reflection.

## Pourquoi le Chargement Paresseux ?

```php
<?php
// Sans lazy loading :
// Tous les services sont instanciés même s'ils ne sont pas utilisés
class Container
{
    public function __construct()
    {
        $this->db = new DatabaseConnection();      // Lourd !
        $this->cache = new RedisConnection();      // Lourd !
        $this->mailer = new SmtpMailer();          // Lourd !
    }
}

// Avec lazy loading :
// Les services ne sont créés QUE quand on y accède
```

## Les Objets Paresseux Natifs (PHP 8.4)

```php
<?php
class ExpensiveService
{
    public function __construct()
    {
        echo "Initialisation coûteuse...\n";
        sleep(2);  // Simule une configuration lourde
    }

    public function doWork(): string
    {
        return "Travail effectué !";
    }
}

// Créer un objet "ghost" paresseux
$reflector = new ReflectionClass(ExpensiveService::class);

$lazy = $reflector->newLazyGhost(function(ExpensiveService $object) {
    // Cet initialiseur ne s'exécute QUE lors du premier accès
    $object->__construct();
});

echo "Objet paresseux créé\n";  // Aucune initialisation encore !
echo $lazy->doWork();           // L'initialisation se produit ICI
```

## Le Pattern Proxy Paresseux

```php
<?php
class DatabaseConnection
{
    private PDO $pdo;

    public function __construct(string $dsn)
    {
        echo "Connexion à la base de données...\n";
        $this->pdo = new PDO($dsn);
    }

    public function query(string $sql): array
    {
        return $this->pdo->query($sql)->fetchAll();
    }
}

// Créer un proxy paresseux
$reflector = new ReflectionClass(DatabaseConnection::class);

$proxy = $reflector->newLazyProxy(function() {
    // La factory retourne l'objet réel
    return new DatabaseConnection('mysql:host=localhost');
});

// Connexion pas encore établie !
echo "Proxy créé\n";

// Le premier appel de méthode déclenche l'initialisation
$result = $proxy->query('SELECT 1');
```

## Ghost VS Proxy

| Fonctionnalité     | Lazy Ghost             | Lazy Proxy              |
| ------------------ | ---------------------- | ----------------------- |
| Identité           | Même objet             | Enveloppe l'objet réel  |
| Vérification `===` | Fonctionne normalement | Identité différente     |
| Cas d'usage        | La plupart des cas     | Quand l'identité compte |

## Vérifier l'État Paresseux

```php
<?php
$reflector = new ReflectionClass($object);

// Vérifier si l'objet est paresseux et non initialisé
if ($reflector->isUninitializedLazyObject($object)) {
    echo "Objet pas encore initialisé\n";
}

// Forcer l'initialisation
$reflector->initializeLazyObject($object);

// Réinitialiser à un état non initialisé
$reflector->resetAsLazyGhost($object, function($obj) {
    $obj->__construct();
});
```

## Exemple de Container Paresseux

```php
<?php
class LazyContainer
{
    private array $factories = [];
    private array $instances = [];

    public function register(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
    }

    public function get(string $id): object
    {
        if (!isset($this->instances[$id])) {
            $reflector = new ReflectionClass($id);

            $this->instances[$id] = $reflector->newLazyGhost(
                function($object) use ($id) {
                    $real = ($this->factories[$id])();

                    // Copier les propriétés du vrai objet vers le ghost
                    $r = new ReflectionObject($real);
                    foreach ($r->getProperties() as $prop) {
                        $prop->setAccessible(true);
                        $prop->setValue($object, $prop->getValue($real));
                    }
                }
            );
        }

        return $this->instances[$id];
    }
}
```

## Les Grimoires

- [RFC Lazy Objects (PHP 8.4)](https://wiki.php.net/rfc/lazy-objects)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
