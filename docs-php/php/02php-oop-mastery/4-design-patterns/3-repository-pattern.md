---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-repository-pattern"
---

# Le Pattern Dépôt (Repository Pattern)

Le pattern Dépôt **abstrait la logique d'accès aux données**, fournissant une interface de type collection pour les objets du domaine.

## Dépôt de Base

```php
<?php
interface UserRepositoryInterface {
    public function find(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function findAll(): array;
    public function save(User $user): void;
    public function delete(User $user): void;
}

class MySQLUserRepository implements UserRepositoryInterface {
    public function __construct(
        private PDO $pdo
    ) {}

    public function find(int $id): ?User {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findByEmail(string $email): ?User {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(): array {
        $stmt = $this->pdo->query('SELECT * FROM users');
        return array_map([$this, 'hydrate'], $stmt->fetchAll());
    }

    public function save(User $user): void {
        if ($user->id === null) {
            $this->insert($user);
        } else {
            $this->update($user);
        }
    }

    public function delete(User $user): void {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $user->id]);
    }

    private function hydrate(array $row): User {
        return new User(
            id: (int) $row['id'],
            name: $row['name'],
            email: $row['email']
        );
    }

    private function insert(User $user): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email) VALUES (:name, :email)'
        );
        $stmt->execute(['name' => $user->name, 'email' => $user->email]);
        $user->id = (int) $this->pdo->lastInsertId();
    }

    private function update(User $user): void {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET name = :name, email = :email WHERE id = :id'
        );
        $stmt->execute([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email
        ]);
    }
}
```

## Dépôt En Mémoire (Pour les Tests)

```php
<?php
class InMemoryUserRepository implements UserRepositoryInterface {
    private array $users = [];
    private int $nextId = 1;

    public function find(int $id): ?User {
        return $this->users[$id] ?? null;
    }

    public function findByEmail(string $email): ?User {
        foreach ($this->users as $user) {
            if ($user->email === $email) {
                return $user;
            }
        }
        return null;
    }

    public function findAll(): array {
        return array_values($this->users);
    }

    public function save(User $user): void {
        if ($user->id === null) {
            $user->id = $this->nextId++;
        }
        $this->users[$user->id] = $user;
    }

    public function delete(User $user): void {
        unset($this->users[$user->id]);
    }
}
```

## Service Utilisant le Dépôt

```php
<?php
class UserService {
    public function __construct(
        private UserRepositoryInterface $repository
    ) {}

    public function register(string $name, string $email, string $password): User {
        if ($this->repository->findByEmail($email)) {
            throw new DomainException('Email déjà enregistré');
        }

        $user = new User(
            name: $name,
            email: $email,
            passwordHash: password_hash($password, PASSWORD_DEFAULT)
        );

        $this->repository->save($user);
        return $user;
    }
}

// Production
$service = new UserService(new MySQLUserRepository($pdo));

// Tests
$service = new UserService(new InMemoryUserRepository());
```

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
