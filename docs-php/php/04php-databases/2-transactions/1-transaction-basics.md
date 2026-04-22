---
source_course: "php-databases"
source_lesson: "php-databases-transaction-basics"
---

# Comprendre les Transactions

Les transactions **regroupent plusieurs opérations en une seule unité atomique**. Soit tout réussit, soit tout échoue.

## Les Propriétés ACID

- **Atomicité** : Tout ou rien
- **Cohérence** : La base de données reste valide
- **Isolation** : Les transactions concurrentes ne s'interfèrent pas
- **Durabilité** : Les données validées persistent

## Transaction Basique

```php
<?php
try {
    $pdo->beginTransaction();

    // Transférer de l'argent entre deux comptes
    $stmt = $pdo->prepare('UPDATE accounts SET balance = balance - :amount WHERE id = :from');
    $stmt->execute(['amount' => 100, 'from' => 1]);

    $stmt = $pdo->prepare('UPDATE accounts SET balance = balance + :amount WHERE id = :to');
    $stmt->execute(['amount' => 100, 'to' => 2]);

    $pdo->commit();  // Les deux changements sont appliqués

} catch (Exception $e) {
    $pdo->rollBack();  // Les deux changements sont annulés
    throw $e;
}
```

## Pourquoi les Transactions sont Importantes

```php
<?php
// Sans transaction - Données incohérentes possibles !
$pdo->exec('UPDATE accounts SET balance = balance - 100 WHERE id = 1');
// Crash serveur ici = l'argent disparaît !
$pdo->exec('UPDATE accounts SET balance = balance + 100 WHERE id = 2');

// Avec transaction - Opération atomique
$pdo->beginTransaction();
$pdo->exec('UPDATE accounts SET balance = balance - 100 WHERE id = 1');
// Crash serveur ici = transaction automatiquement annulée
$pdo->exec('UPDATE accounts SET balance = balance + 100 WHERE id = 2');
$pdo->commit();
```

## L'État de la Transaction

```php
<?php
// Vérifier si une transaction est active
if ($pdo->inTransaction()) {
    // Gérer différemment
}

// Transactions imbriquées (points de sauvegarde)
$pdo->beginTransaction();
// ... opérations ...

$pdo->exec('SAVEPOINT sp1');
// ... d'autres opérations ...

// Retour partiel en arrière
$pdo->exec('ROLLBACK TO sp1');

// Continuer et valider
$pdo->commit();
```

## Le Comportement Auto-Commit

```php
<?php
// Par défaut, chaque requête est auto-commitée
$pdo->exec('INSERT INTO logs VALUES (...)');  // Validée immédiatement

// beginTransaction() désactive l'auto-commit
$pdo->beginTransaction();
$pdo->exec('INSERT INTO logs VALUES (...)');  // Pas encore validée
$pdo->commit();  // Maintenant validée

// Ou
$pdo->rollBack();  // Annulée
```

## Les Grimoires

- [Transactions PDO (Documentation Officielle)](https://www.php.net/manual/en/pdo.transactions.php)

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
