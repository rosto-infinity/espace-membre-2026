---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-di-basics"
---

# Les Fondamentaux de l'Injection de Dépendances (DI)

L'Injection de Dépendances (DI) est un pattern de conception où les objets **reçoivent leurs dépendances de sources externes** plutôt que de les créer eux-mêmes.

## Sans DI (Couplage Fort)

```php
<?php
// MAUVAIS : Crée ses propres dépendances
class UserService {
    private MySQLUserRepository $repository;
    private SmtpMailer $mailer;

    public function __construct() {
        $this->repository = new MySQLUserRepository();  // Codé en dur !
        $this->mailer = new SmtpMailer();               // Codé en dur !
    }
}

// Problèmes :
// - Impossible de changer d'implémentation
// - Difficile à tester
// - Dépendances cachées
```

## Avec DI (Couplage Faible)

```php
<?php
// BON : Reçoit ses dépendances
class UserService {
    public function __construct(
        private UserRepositoryInterface $repository,  // Interface !
        private MailerInterface $mailer               // Interface !
    ) {}
}

// Avantages :
// - Facile de changer d'implémentation
// - Facile à tester (mocker les dépendances)
// - Dépendances explicites
```

## Les Types d'Injection de Dépendances

### Injection par Constructeur (Recommandée)

```php
<?php
class OrderService {
    public function __construct(
        private OrderRepository $orders,
        private PaymentGateway $payments,
        private Logger $logger
    ) {}
}
```

### Injection par Setter

```php
<?php
class ReportGenerator {
    private ?Logger $logger = null;

    public function setLogger(Logger $logger): void {
        $this->logger = $logger;
    }
}
```

### Injection par Interface

```php
<?php
interface LoggerAware {
    public function setLogger(Logger $logger): void;
}

class Service implements LoggerAware {
    private Logger $logger;

    public function setLogger(Logger $logger): void {
        $this->logger = $logger;
    }
}
```

## Câblage Manuel (Manual Wiring)

```php
<?php
// Racine de composition — câbler tout ensemble
$pdo = new PDO('mysql:host=localhost;dbname=app', 'user', 'pass');
$logger = new FileLogger('/var/log/app.log');
$mailer = new SmtpMailer('smtp.example.com');

$userRepository = new MySQLUserRepository($pdo);
$userService = new UserService($userRepository, $mailer);

$orderRepository = new MySQLOrderRepository($pdo);
$paymentGateway = new StripeGateway($_ENV['STRIPE_KEY']);
$orderService = new OrderService($orderRepository, $paymentGateway, $logger);
```

## Exemple Concret

**La DI rend le code testable via des implémentations de substitution**

```php
<?php
declare(strict_types=1);

// La DI facilite les tests
interface UserRepository {
    public function findByEmail(string $email): ?User;
    public function save(User $user): void;
}

interface PasswordHasher {
    public function hash(string $password): string;
    public function verify(string $password, string $hash): bool;
}

class RegistrationService {
    public function __construct(
        private UserRepository $users,
        private PasswordHasher $hasher
    ) {}

    public function register(string $email, string $password): User {
        if ($this->users->findByEmail($email)) {
            throw new DomainException('Email déjà utilisé');
        }

        $user = new User(
            email: $email,
            passwordHash: $this->hasher->hash($password)
        );

        $this->users->save($user);
        return $user;
    }
}

// En production :
$service = new RegistrationService(
    new MySQLUserRepository($pdo),
    new BcryptHasher()
);

// En tests :
class InMemoryUserRepository implements UserRepository {
    private array $users = [];

    public function findByEmail(string $email): ?User {
        foreach ($this->users as $user) {
            if ($user->email === $email) return $user;
        }
        return null;
    }

    public function save(User $user): void {
        $this->users[] = $user;
    }
}

class FakeHasher implements PasswordHasher {
    public function hash(string $password): string {
        return "hashed:$password";
    }

    public function verify(string $password, string $hash): bool {
        return $hash === "hashed:$password";
    }
}

// Test avec des faux (fakes)
$service = new RegistrationService(
    new InMemoryUserRepository(),
    new FakeHasher()
);
?>
```

## Les Grimoires

- [Comprendre l'Injection de Dépendances en PHP](https://php-di.org/doc/understanding-di.html)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
