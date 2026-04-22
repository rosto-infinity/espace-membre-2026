---
source_course: "php-testing"
source_lesson: "php-testing-database-testing"
---

# Tests d'Intégration avec Base de Données

Les tests d'intégration **vérifient que les composants fonctionnent correctement ensemble**, y compris les interactions réelles avec la base de données.

## SQLite en Mémoire

```php
<?php
class UserRepositoryTest extends TestCase
{
    private PDO $pdo;
    private UserRepository $repository;

    protected function setUp(): void
    {
        // Utiliser SQLite en mémoire pour la rapidité
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Créer le schéma
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT UNIQUE NOT NULL,
                name TEXT NOT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )
        ');

        $this->repository = new UserRepository($this->pdo);
    }

    public function testCreateAndFindUser(): void
    {
        // Créer un utilisateur
        $user = $this->repository->create([
            'email' => 'jean@exemple.com',
            'name' => 'Jean Dupont',
        ]);

        $this->assertGreaterThan(0, $user->id);

        // Trouver l'utilisateur
        $found = $this->repository->find($user->id);

        $this->assertNotNull($found);
        $this->assertEquals('jean@exemple.com', $found->email);
        $this->assertEquals('Jean Dupont', $found->name);
    }

    public function testFindByEmail(): void
    {
        $this->repository->create([
            'email' => 'marie@exemple.com',
            'name' => 'Marie',
        ]);

        $user = $this->repository->findByEmail('marie@exemple.com');

        $this->assertNotNull($user);
        $this->assertEquals('Marie', $user->name);
    }

    public function testUniqueEmailConstraint(): void
    {
        $this->repository->create(['email' => 'test@exemple.com', 'name' => 'Test']);

        $this->expectException(PDOException::class);
        $this->repository->create(['email' => 'test@exemple.com', 'name' => 'Doublon']);
    }
}
```

## Transactions pour l'Isolation

```php
<?php
class TransactionalTestCase extends TestCase
{
    protected PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO($_ENV['DATABASE_URL']);
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Annuler tous les changements après chaque test
        $this->pdo->rollBack();
    }
}

class OrderIntegrationTest extends TransactionalTestCase
{
    public function testCreateOrder(): void
    {
        // Les changements sont automatiquement annulés
        $order = $this->createOrder();
        $this->assertNotNull($order->id);
    }

    public function testAnotherTest(): void
    {
        // La base de données est propre - pas de commande du test précédent
    }
}
```

## Fixtures et Seeders

```php
<?php
trait DatabaseFixtures
{
    protected function seedUsers(): array
    {
        $users = [];
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, name) VALUES (:email, :name)'
        );

        foreach ($this->userFixtures() as $data) {
            $stmt->execute($data);
            $users[] = ['id' => $this->pdo->lastInsertId(), ...$data];
        }

        return $users;
    }

    private function userFixtures(): array
    {
        return [
            ['email' => 'admin@exemple.com', 'name' => 'Utilisateur Admin'],
            ['email' => 'user@exemple.com', 'name' => 'Utilisateur Standard'],
            ['email' => 'guest@exemple.com', 'name' => 'Utilisateur Invité'],
        ];
    }
}

class UserSearchTest extends TestCase
{
    use DatabaseFixtures;

    public function testSearchUsers(): void
    {
        $users = $this->seedUsers();

        $results = $this->repository->search('Utilisateur');

        $this->assertCount(2, $results);  // Admin, Standard
    }
}
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
