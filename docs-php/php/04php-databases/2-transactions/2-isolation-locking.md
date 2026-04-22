---
source_course: "php-databases"
source_lesson: "php-databases-isolation-locking"
---

# Niveaux d'Isolation & Verrouillage

Les niveaux d'isolation **contrôlent comment les transactions interagissent entre elles**.

## Les Niveaux d'Isolation

```php
<?php
// Définir le niveau d'isolation (MySQL)
$pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
$pdo->beginTransaction();

// Niveaux du moins au plus restrictif :
// READ UNCOMMITTED  - Voir les changements non validés (lectures sales)
// READ COMMITTED    - Voir seulement les données validées
// REPEATABLE READ   - La même requête retourne les mêmes résultats (défaut MySQL)
// SERIALIZABLE      - Isolation complète (le plus lent)
```

## Les Problèmes de Concurrence

### Lecture Sale (Dirty Read)

```php
<?php
// Transaction A (READ UNCOMMITTED)
// Voit les changements non validés de la Transaction B
// Si B est annulée, A a lu des données invalides !
```

### Lecture Non-Répétable (Non-Repeatable Read)

```php
<?php
// Transaction A lit une ligne, obtient 100 €
// Transaction B met à jour la ligne à 50 €, valide
// Transaction A relit, obtient 50 €
// Même requête, résultat différent !
```

### Lecture Fantôme (Phantom Read)

```php
<?php
// Transaction A compte les lignes WHERE status = 'active', obtient 10
// Transaction B insère une nouvelle ligne active, valide
// Transaction A recompte, obtient 11
// De nouvelles lignes sont apparues !
```

## Le Verrouillage de Lignes

```php
<?php
$pdo->beginTransaction();

// SELECT ... FOR UPDATE verrouille les lignes
$stmt = $pdo->prepare(
    'SELECT balance FROM accounts WHERE id = :id FOR UPDATE'
);
$stmt->execute(['id' => 1]);
$balance = $stmt->fetchColumn();

// D'autres transactions attendent ici jusqu'à notre commit/rollback

if ($balance >= $amount) {
    $stmt = $pdo->prepare(
        'UPDATE accounts SET balance = balance - :amount WHERE id = :id'
    );
    $stmt->execute(['amount' => $amount, 'id' => 1]);
}

$pdo->commit();  // Verrou libéré
```

## Le Verrouillage Optimiste

```php
<?php
class OptimisticLockException extends Exception {}

// Ajouter une colonne version à la table
// version INT DEFAULT 1

function updateWithOptimisticLock(PDO $pdo, int $id, array $data): void {
    // Lire la version actuelle
    $stmt = $pdo->prepare('SELECT version FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $version = $stmt->fetchColumn();

    // Mettre à jour seulement si la version correspond
    $stmt = $pdo->prepare(
        'UPDATE products
         SET name = :name, price = :price, version = version + 1
         WHERE id = :id AND version = :version'
    );
    $stmt->execute([
        'id' => $id,
        'name' => $data['name'],
        'price' => $data['price'],
        'version' => $version,
    ]);

    if ($stmt->rowCount() === 0) {
        throw new OptimisticLockException('L\'enregistrement a été modifié par un autre processus');
    }
}
```

## Exemple Concret

**Un gestionnaire de transactions avec support du verrouillage**

```php
<?php
declare(strict_types=1);

// Gestionnaire de transactions avec rollback automatique
class TransactionManager {
    public function __construct(
        private PDO $pdo
    ) {}

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();
            return $result;

        } catch (Throwable $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function withLock(string $table, int $id, callable $callback): mixed {
        return $this->transaction(function() use ($table, $id, $callback) {
            // Acquérir un verrou sur la ligne
            $stmt = $this->pdo->prepare(
                "SELECT * FROM $table WHERE id = :id FOR UPDATE"
            );
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch();

            if (!$row) {
                throw new RuntimeException('Enregistrement introuvable');
            }

            return $callback($row);
        });
    }
}

// Utilisation
$txManager = new TransactionManager($pdo);

// Transaction simple
$orderId = $txManager->transaction(function() use ($pdo, $items) {
    $pdo->prepare('INSERT INTO orders (user_id) VALUES (:user_id)')
        ->execute(['user_id' => $userId]);
    $orderId = $pdo->lastInsertId();

    foreach ($items as $item) {
        $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity) VALUES (?, ?, ?)')
            ->execute([$orderId, $item['product_id'], $item['quantity']]);
    }

    return $orderId;
});

// Avec verrouillage de ligne
$txManager->withLock('accounts', 1, function($account) use ($pdo, $amount) {
    if ($account['balance'] < $amount) {
        throw new InsufficientFundsException();
    }

    $pdo->prepare('UPDATE accounts SET balance = balance - ? WHERE id = ?')
        ->execute([$amount, $account['id']]);
});
?>
```

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
