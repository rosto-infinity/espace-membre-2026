---
source_course: "php-testing"
source_lesson: "php-testing-setup-teardown"
---

# Configuration et Nettoyage (Setup & Teardown)

PHPUnit fournit des **hooks pour préparer et nettoyer les environnements de test**.

## Méthodes d'Instance

```php
<?php
class UserServiceTest extends TestCase
{
    private UserService $service;
    private PDO $pdo;

    // S'exécute AVANT chaque méthode de test
    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');

        $this->service = new UserService($this->pdo);
    }

    // S'exécute APRÈS chaque méthode de test
    protected function tearDown(): void
    {
        $this->pdo = null;  // Fermer la connexion

        parent::tearDown();
    }

    public function testCreateUser(): void
    {
        // Base de données fraîche pour chaque test
        $user = $this->service->create('Jean');
        $this->assertEquals('Jean', $user->name);
    }

    public function testAnotherTest(): void
    {
        // Aussi une base de données fraîche
        $count = $this->service->count();
        $this->assertEquals(0, $count);
    }
}
```

## Méthodes Statiques (Une Fois par Classe)

```php
<?php
class ExpensiveSetupTest extends TestCase
{
    private static PDO $sharedPdo;

    // S'exécute UNE FOIS avant tous les tests de cette classe
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$sharedPdo = new PDO('sqlite::memory:');
        self::$sharedPdo->exec('CREATE TABLE config (key TEXT, value TEXT)');
        self::$sharedPdo->exec("INSERT INTO config VALUES ('version', '1.0')");
    }

    // S'exécute UNE FOIS après tous les tests de cette classe
    public static function tearDownAfterClass(): void
    {
        self::$sharedPdo = null;

        parent::tearDownAfterClass();
    }

    public function testReadConfig(): void
    {
        $stmt = self::$sharedPdo->query('SELECT value FROM config WHERE key = "version"');
        $this->assertEquals('1.0', $stmt->fetchColumn());
    }
}
```

## Ordre d'Exécution

```
setUpBeforeClass()    // Une fois par classe
    setUp()           // Avant le test 1
        test1()
    tearDown()        // Après le test 1
    setUp()           // Avant le test 2
        test2()
    tearDown()        // Après le test 2
tearDownAfterClass()  // Une fois par classe
```

## Dépendances entre Tests

```php
<?php
class OrderTest extends TestCase
{
    public function testCreateOrder(): int
    {
        $order = new Order();
        $order->addItem('Widget', 10.00);
        $order->save();

        $this->assertGreaterThan(0, $order->id);

        return $order->id;  // Passer au test dépendant
    }

    /**
     * @depends testCreateOrder
     */
    public function testOrderCanBePaid(int $orderId): void
    {
        $order = Order::find($orderId);
        $order->pay();

        $this->assertEquals('paid', $order->status);
    }
}
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
