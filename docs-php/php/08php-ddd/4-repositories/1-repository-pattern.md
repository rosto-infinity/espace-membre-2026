---
source_course: "php-ddd"
source_lesson: "php-ddd-repository-pattern"
---

# Le Pattern Référentiel (Repository)

Un **Référentiel** fait la médiation entre le domaine et les couches de mapping de données. Il fournit une interface de type collection pour accéder aux objets de domaine tout en cachant les détails de persistance.

## Pourquoi des Référentiels ?

```php
<?php
// MAUVAIS : Domaine couplé à la persistance
class OrderService {
    public function getOrder(string $id): Order {
        $pdo = new PDO(...);
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        return new Order($data['id'], ...);
    }
}

// BON : Le domaine utilise une abstraction
class OrderService {
    public function __construct(
        private OrderRepository $orders  // Interface, pas implémentation
    ) {}

    public function getOrder(OrderId $id): Order {
        return $this->orders->find($id);
    }
}
```

## Interface du Référentiel (Couche Domaine)

```php
<?php
namespace Domain\Ordering;

interface OrderRepository {
    public function find(OrderId $id): ?Order;

    public function findOrFail(OrderId $id): Order;

    public function save(Order $order): void;

    public function remove(Order $order): void;

    public function nextIdentity(): OrderId;

    /**
     * @return Order[]
     */
    public function findByCustomer(CustomerId $customerId): array;

    /**
     * @return Order[]
     */
    public function findPendingOlderThan(\DateTimeImmutable $date): array;
}
```

## Implémentation du Référentiel (Couche Infrastructure)

```php
<?php
namespace Infrastructure\Persistence\Doctrine;

use Doctrine\ORM\EntityManagerInterface;
use Domain\Ordering\Order;
use Domain\Ordering\OrderId;
use Domain\Ordering\OrderRepository;

final class DoctrineOrderRepository implements OrderRepository {
    public function __construct(
        private EntityManagerInterface $em
    ) {}

    public function find(OrderId $id): ?Order {
        return $this->em->find(Order::class, $id->toString());
    }

    public function findOrFail(OrderId $id): Order {
        $order = $this->find($id);

        if ($order === null) {
            throw OrderNotFound::withId($id);
        }

        return $order;
    }

    public function save(Order $order): void {
        $this->em->persist($order);
        $this->em->flush();
    }

    public function remove(Order $order): void {
        $this->em->remove($order);
        $this->em->flush();
    }

    public function nextIdentity(): OrderId {
        return OrderId::generate();
    }

    public function findByCustomer(CustomerId $customerId): array {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.customerId = :customerId')
            ->setParameter('customerId', $customerId->toString())
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingOlderThan(\DateTimeImmutable $date): array {
        return $this->em->createQueryBuilder()
            ->select('o')
            ->from(Order::class, 'o')
            ->where('o.status = :status')
            ->andWhere('o.createdAt < :date')
            ->setParameter('status', OrderStatus::Pending->value)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
}
```

## Référentiel En Mémoire pour les Tests

```php
<?php
namespace Infrastructure\Persistence\InMemory;

final class InMemoryOrderRepository implements OrderRepository {
    /** @var array<string, Order> */
    private array $orders = [];

    public function find(OrderId $id): ?Order {
        return $this->orders[$id->toString()] ?? null;
    }

    public function findOrFail(OrderId $id): Order {
        return $this->find($id)
            ?? throw OrderNotFound::withId($id);
    }

    public function save(Order $order): void {
        $this->orders[$order->id()->toString()] = $order;
    }

    public function remove(Order $order): void {
        unset($this->orders[$order->id()->toString()]);
    }

    public function nextIdentity(): OrderId {
        return OrderId::generate();
    }

    public function findByCustomer(CustomerId $customerId): array {
        return array_filter(
            $this->orders,
            fn(Order $o) => $o->customerId()->equals($customerId)
        );
    }

    // Aide pour les tests
    public function clear(): void {
        $this->orders = [];
    }

    public function count(): int {
        return count($this->orders);
    }
}
```

## Directives du Référentiel

### 1. Un Référentiel par Agrégat

```php
<?php
// BON : Référentiel pour la racine d'agrégat seulement
interface OrderRepository {
    public function save(Order $order): void;
}

// MAUVAIS : Référentiel pour une entité interne
interface OrderLineRepository {  // OrderLine est à l'intérieur de l'agrégat Order !
    public function save(OrderLine $line): void;
}
```

### 2. Retourner des Objets de Domaine, Pas des Tableaux

```php
<?php
// MAUVAIS
interface OrderRepository {
    public function find(string $id): ?array;  // Données brutes
}

// BON
interface OrderRepository {
    public function find(OrderId $id): ?Order;  // Objet de domaine
}
```

### 3. Méthodes de Requête Orientées Domaine

```php
<?php
// MAUVAIS : Méthodes de requête techniques
interface OrderRepository {
    public function findWhere(array $criteria): array;
    public function findBySql(string $sql): array;
}

// BON : Méthodes à signification métier
interface OrderRepository {
    public function findPendingForCustomer(CustomerId $id): array;
    public function findRequiringAttention(): array;
    public function findRecentlyCompleted(int $days): array;
}
```

## Les Grimoires

- [Pattern Repository](https://martinfowler.com/eaaCatalog/repository.html)

---

> 📘 _Cette leçon fait partie du cours [DDD avec PHP](/php/php-ddd/) sur la plateforme d'apprentissage RostoDev._
