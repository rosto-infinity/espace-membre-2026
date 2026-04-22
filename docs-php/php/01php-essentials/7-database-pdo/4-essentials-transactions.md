---
source_course: "php-essentials"
source_lesson: "php-essentials-transactions"
---

# Transactions de Base de Données

Les transactions garantissent que plusieurs opérations de base de données réussissent toutes ensemble ou échouent toutes ensemble, préservant l'intégrité des données.

## Qu'est-ce qu'une Transaction ?

Une transaction regroupe des opérations traitées comme une seule unité. Elle respecte les propriétés **ACID** :

- **Atomicité** : Tout ou rien.
- **Cohérence** : Les données restent dans un état valide.
- **Isolation** : Les opérations ne s'interfèrent pas.
- **Durabilité** : Les modifications persistent après le commit.

## Transaction de Base

```php
<?php
try {
    $pdo->beginTransaction();

    // Plusieurs opérations
    $pdo->exec("UPDATE comptes SET solde = solde - 100 WHERE id = 1");
    $pdo->exec("UPDATE comptes SET solde = solde + 100 WHERE id = 2");

    // Si on arrive ici, valider toutes les modifications
    $pdo->commit();

} catch (Exception $e) {
    // Quelque chose s'est mal passé, annuler tout
    $pdo->rollBack();
    throw $e;
}
```

## Exemple Pratique : Traitement de Commande

```php
<?php
function creerCommande(PDO $pdo, int $userId, array $articles): int {
    $pdo->beginTransaction();

    try {
        // 1. Créer la commande
        $stmt = $pdo->prepare(
            'INSERT INTO commandes (user_id, statut, cree_le)
             VALUES (:user_id, :statut, NOW())'
        );
        $stmt->execute([':user_id' => $userId, ':statut' => 'en_attente']);
        $commandeId = (int) $pdo->lastInsertId();

        // 2. Ajouter les articles et mettre à jour le stock
        $articleStmt = $pdo->prepare(
            'INSERT INTO articles_commande (commande_id, produit_id, quantite, prix)
             VALUES (:commande_id, :produit_id, :quantite, :prix)'
        );

        $stockStmt = $pdo->prepare(
            'UPDATE produits SET stock = stock - :qte WHERE id = :id AND stock >= :qte'
        );

        foreach ($articles as $article) {
            // Réduire le stock
            $stockStmt->execute([
                ':qte' => $article['quantite'],
                ':id'  => $article['produit_id']
            ]);

            // Vérifier si le stock a bien été réduit
            if ($stockStmt->rowCount() === 0) {
                throw new Exception("Stock insuffisant pour le produit {$article['produit_id']}");
            }

            // Ajouter l'article à la commande
            $articleStmt->execute([
                ':commande_id' => $commandeId,
                ':produit_id'  => $article['produit_id'],
                ':quantite'    => $article['quantite'],
                ':prix'        => $article['prix']
            ]);
        }

        $pdo->commit();
        return $commandeId;

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

## Méthodes de Transaction

```php
<?php
$pdo->beginTransaction();  // Démarrer la transaction
$pdo->commit();            // Valider toutes les modifications
$pdo->rollBack();          // Annuler toutes les modifications
$pdo->inTransaction();     // Vérifier si on est dans une transaction (bool)
```

## Patron : Gestionnaire de Transaction Imbriquée

```php
<?php
class GestionnaireTransaction {
    private int $niveau = 0;

    public function __construct(private PDO $pdo) {}

    public function debuter(): void {
        if ($this->niveau === 0) {
            $this->pdo->beginTransaction();
        }
        $this->niveau++;
    }

    public function valider(): void {
        $this->niveau--;
        if ($this->niveau === 0) {
            $this->pdo->commit();
        }
    }

    public function annuler(): void {
        $this->niveau = 0;
        $this->pdo->rollBack();
    }
}
```

## Ressources

- [Transactions PDO](https://www.php.net/manual/fr/pdo.transactions.php) — Guide officiel sur les transactions PDO

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
