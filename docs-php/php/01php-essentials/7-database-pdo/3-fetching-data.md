---
source_course: "php-essentials"
source_lesson: "php-essentials-fetching-data"
---

# Récupérer les Résultats de Requêtes

PDO offre plusieurs façons de récupérer des données depuis la base de données. Choisissez la méthode adaptée à vos besoins.

## fetch() — Une Seule Ligne

```php
<?php
$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
$stmt->execute([':id' => 1]);

$utilisateur = $stmt->fetch();
// Retourne une ligne ou false si non trouvé

if ($utilisateur) {
    echo $utilisateur['nom'];
}
```

## fetchAll() — Toutes les Lignes

```php
<?php
$stmt = $pdo->query('SELECT * FROM produits WHERE actif = 1');
$produits = $stmt->fetchAll();

foreach ($produits as $produit) {
    echo $produit['nom'] . ' : ' . $produit['prix'] . " €\n";
}
```

## Modes de Récupération (Fetch Modes)

```php
<?php
// Tableau associatif (le plus courant)
$ligne = $stmt->fetch(PDO::FETCH_ASSOC);
// ['id' => 1, 'nom' => 'Jean']

// Tableau indexé
$ligne = $stmt->fetch(PDO::FETCH_NUM);
// [0 => 1, 1 => 'Jean']

// Les deux à la fois
$ligne = $stmt->fetch(PDO::FETCH_BOTH);
// ['id' => 1, 0 => 1, 'nom' => 'Jean', 1 => 'Jean']

// Objet (stdClass)
$ligne = $stmt->fetch(PDO::FETCH_OBJ);
// stdClass { id: 1, nom: 'Jean' }

// Récupérer dans une classe spécifique
$utilisateur = $stmt->fetch(PDO::FETCH_CLASS, Utilisateur::class);
```

## fetchColumn() — Une Valeur Unique

```php
<?php
$stmt = $pdo->query('SELECT COUNT(*) FROM utilisateurs');
$nombre = $stmt->fetchColumn();
echo "Total d'utilisateurs : $nombre";

// Colonne spécifique (index 0)
$stmt = $pdo->query('SELECT id, nom, email FROM utilisateurs LIMIT 1');
$email = $stmt->fetchColumn(2); // Troisième colonne (email)
```

## Itérer les Résultats

```php
<?php
$stmt = $pdo->query('SELECT * FROM utilisateurs');

// Efficace en mémoire : récupère une ligne à la fois
while ($utilisateur = $stmt->fetch()) {
    echo $utilisateur['nom'] . "\n";
}

// Ou directement avec foreach
foreach ($stmt as $utilisateur) {
    echo $utilisateur['nom'] . "\n";
}
```

## Récupérer en tant qu'Objets

```php
<?php
class Utilisateur {
    public int $id;
    public string $nom;
    public string $email;
}

$stmt = $pdo->query('SELECT id, nom, email FROM utilisateurs');
$stmt->setFetchMode(PDO::FETCH_CLASS, Utilisateur::class);

foreach ($stmt as $utilisateur) {
    // $utilisateur est une instance de Utilisateur
    echo $utilisateur->nom;
}

// Ou tout récupérer en une fois
$utilisateurs = $stmt->fetchAll(PDO::FETCH_CLASS, Utilisateur::class);
```

## Astuces Pratiques

```php
<?php
// Vérifier si des résultats existent
$stmt->execute();
if ($stmt->rowCount() > 0) {
    // A des résultats
}

// Ou simplement
if ($utilisateur = $stmt->fetch()) {
    // Trouvé
} else {
    // Non trouvé
}

// Obtenir le nombre de colonnes
$nombreColonnes = $stmt->columnCount();
```

## Exemples de code

**Différentes stratégies de récupération**

```php
<?php
// 1. Listing paginé
function getUtilisateurs(PDO $pdo, int $page = 1, int $parPage = 10): array {
    $offset = ($page - 1) * $parPage;

    $stmt = $pdo->prepare(
        'SELECT * FROM utilisateurs ORDER BY cree_le DESC LIMIT :limite OFFSET :offset'
    );
    $stmt->bindValue(':limite',  $parPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset',  $offset,  PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

// 2. Paires clé-valeur (utiles pour les listes déroulantes)
function getCategoriesPourSelecteur(PDO $pdo): array {
    $stmt = $pdo->query('SELECT id, nom FROM categories ORDER BY nom');
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    // Retourne : [1 => 'Électronique', 2 => 'Vêtements', ...]
}

// 3. Données groupées
function getUtilisateursParRole(PDO $pdo): array {
    $stmt = $pdo->query('SELECT role, nom FROM utilisateurs ORDER BY role, nom');
    return $stmt->fetchAll(PDO::FETCH_GROUP | PDO::FETCH_COLUMN);
    // Retourne : ['admin' => ['Alice', 'Bob'], 'user' => ['Charlie', ...]]
}

// 4. Enregistrement unique avec gestion du null
function trouverUtilisateurOuEchouer(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id = :id');
    $stmt->execute([':id' => $id]);

    $utilisateur = $stmt->fetch();

    if (!$utilisateur) {
        throw new RuntimeException("Utilisateur introuvable : $id");
    }

    return $utilisateur;
}
?>
```

## Ressources

- [PDOStatement::fetch](https://www.php.net/manual/fr/pdostatement.fetch.php) — Documentation et modes de la méthode fetch

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
