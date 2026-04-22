---
source_course: "php-essentials"
source_lesson: "php-essentials-prepared-statements"
---

# Requêtes Préparées (Prepared Statements)

Les requêtes préparées sont **la seule façon sécurisée** d'exécuter des requêtes avec des données utilisateurs. Elles préviennent totalement les injections SQL.

## Comment Fonctionnent les Requêtes Préparées

1. **Préparer** : Envoyer le modèle de requête avec des espaces réservés (placeholders).
2. **Lier/Exécuter** : Envoyer les données séparément.
3. **Récupérer** : Obtenir les résultats.

La base de données sait ce qui est SQL et ce qui est donnée — ils ne se mélangent jamais !

## Paramètres Nommés (Recommandé)

```php
<?php
$stmt = $pdo->prepare(
    'SELECT * FROM utilisateurs WHERE email = :email AND statut = :statut'
);

$stmt->execute([
    ':email'  => $email,
    ':statut' => 'actif'
]);

$utilisateur = $stmt->fetch();
```

## Paramètres Positionnels

```php
<?php
$stmt = $pdo->prepare(
    'SELECT * FROM produits WHERE prix > ? AND categorie = ?'
);

$stmt->execute([29.99, 'electronique']);
$produits = $stmt->fetchAll();
```

## Opérations INSERT

```php
<?php
$stmt = $pdo->prepare(
    'INSERT INTO utilisateurs (nom, email, hash_mdp) VALUES (:nom, :email, :mdp)'
);

$stmt->execute([
    ':nom'   => $nom,
    ':email' => $email,
    ':mdp'   => password_hash($motdepasse, PASSWORD_DEFAULT)
]);

// Obtenir l'ID de la ligne insérée
$nouvelId = $pdo->lastInsertId();
```

## Opérations UPDATE

```php
<?php
$stmt = $pdo->prepare(
    'UPDATE utilisateurs SET nom = :nom, mis_a_jour_le = NOW() WHERE id = :id'
);

$stmt->execute([
    ':nom' => $nouveauNom,
    ':id'  => $userId
]);

// Vérifier combien de lignes ont été affectées
$lignesModifiees = $stmt->rowCount();
```

## Opérations DELETE

```php
<?php
$stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
$stmt->execute([':id' => $userId]);

if ($stmt->rowCount() > 0) {
    echo "Utilisateur supprimé avec succès";
}
```

## Pourquoi les Requêtes Préparées Préviennent les Injections SQL

```php
<?php
// DANGEREUX - Vulnérable à l'injection SQL !
$email = "'; DROP TABLE utilisateurs; --";
$requete = "SELECT * FROM utilisateurs WHERE email = '$email'";
// Résultat : SELECT * FROM utilisateurs WHERE email = ''; DROP TABLE utilisateurs; --'

// SÉCURISÉ - Requête préparée
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = :email');
$stmt->execute([':email' => $email]);
// L'entrée malveillante est traitée comme une chaîne littérale, pas du SQL
```

## Exemples de code

**Modèle Repository avec des requêtes préparées**

```php
<?php
class RepositoryUtilisateurs {
    public function __construct(private PDO $pdo) {}

    public function trouverParId(int $id): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    public function trouverParEmail(string $email): ?array {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateurs WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $u = $stmt->fetch();
        return $u ?: null;
    }

    public function creer(string $nom, string $email, string $mdp): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO utilisateurs (nom, email, hash_mdp, cree_le)
             VALUES (:nom, :email, :mdp, NOW())'
        );

        $stmt->execute([
            ':nom'   => $nom,
            ':email' => $email,
            ':mdp'   => password_hash($mdp, PASSWORD_DEFAULT)
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function mettreAJour(int $id, array $donnees): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE utilisateurs SET nom = :nom, email = :email WHERE id = :id'
        );

        return $stmt->execute([
            ':id'    => $id,
            ':nom'   => $donnees['nom'],
            ':email' => $donnees['email']
        ]);
    }

    public function supprimer(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM utilisateurs WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->rowCount() > 0;
    }
}
?>
```

## Ressources

- [Requêtes Préparées PDO](https://www.php.net/manual/fr/pdo.prepared-statements.php) — Guide officiel des requêtes préparées PDO
- [Sécurité des BDD](https://www.php.net/manual/fr/security.database.sql-injection.php) — Prévention des injections SQL

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
