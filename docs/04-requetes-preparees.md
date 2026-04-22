# 04 — Les Requêtes Préparées et Paramètres Nommés

## Le danger des injections SQL

Avant de coder quoi que ce soit, comprenez ce danger :

```php
// ❌ CODE DANGEREUX - Ne jamais faire ça !
$email = $_POST['email']; // L'utilisateur tape : ' OR '1'='1
$sql = "SELECT * FROM membres WHERE mail = '$email'";
// La requête devient : SELECT * FROM membres WHERE mail = '' OR '1'='1'
// Résultat : TOUS les membres sont retournés ! Catastrophique.
```

C'est une **injection SQL**. Un attaquant peut ainsi lire, modifier ou supprimer toute votre base de données.

---

## La solution : les requêtes préparées

PDO sépare le **code SQL** des **données utilisateur**. Elles ne se mélangent jamais.

### Méthode avec paramètres positionnels (` ? `)
```php
$stmt = $pdo->prepare("SELECT * FROM membres WHERE mail = ?");
$stmt->execute([$email]); // Le ? est remplacé par $email en toute sécurité
```

### Méthode avec paramètres nommés (`:nom`) — **Recommandée**
```php
$stmt = $pdo->prepare("SELECT * FROM membres WHERE mail = :mail");
$stmt->execute([':mail' => $email]);
```

---

## Pourquoi les paramètres nommés sont meilleurs ?

```php
// Positionnels → L'ordre compte, difficile à lire
$stmt = $pdo->prepare("INSERT INTO membres(pseudo, mail, motdepasse) VALUES(?, ?, ?)");
$stmt->execute([$pseudo, $mail, $mdp_hash]);

// Nommés → L'ordre n'importe pas, très lisible
$stmt = $pdo->prepare("INSERT INTO membres(pseudo, mail, motdepasse) VALUES(:pseudo, :mail, :mdp)");
$stmt->execute([
    ':pseudo' => $pseudo,
    ':mail'   => $mail,
    ':mdp'    => $mdp_hash
]);
```

Avec les paramètres nommés, si vous inversez l'ordre dans le tableau `execute`, ça marche quand même. Avec les `?`, l'ordre est obligatoire.

---

## Les 3 opérations fondamentales

### SELECT — Récupérer des données
```php
// Récupérer UN membre par son ID
$stmt = $pdo->prepare("SELECT * FROM membres WHERE id = :id");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(); // Retourne un seul tableau associatif

// Récupérer TOUS les membres
$stmt = $pdo->query("SELECT * FROM membres ORDER BY created_at DESC");
$users = $stmt->fetchAll(); // Retourne un tableau de tableaux
```

### INSERT — Insérer des données
```php
$stmt = $pdo->prepare(
    "INSERT INTO membres(pseudo, mail, motdepasse) VALUES(:pseudo, :mail, :mdp)"
);
$stmt->execute([
    ':pseudo' => $pseudo,
    ':mail'   => $mail,
    ':mdp'    => password_hash($mdp, PASSWORD_DEFAULT)
]);

// Obtenir l'ID du nouvel enregistrement
$newId = $pdo->lastInsertId();
```

### UPDATE — Modifier des données
```php
$stmt = $pdo->prepare("UPDATE membres SET pseudo = :val WHERE id = :id");
$stmt->execute([':val' => $nouveauPseudo, ':id' => $userId]);

// Combien de lignes ont été modifiées ?
$lignesModifiees = $stmt->rowCount();
```

---

## Vérifier si une valeur existe déjà

Très utile pour vérifier si un pseudo ou email est déjà pris :

```php
$stmt = $pdo->prepare("SELECT id FROM membres WHERE pseudo = :pseudo");
$stmt->execute([':pseudo' => $pseudo]);

if ($stmt->rowCount() > 0) {
    echo "Ce pseudo est déjà utilisé !";
}
```

> `rowCount()` retourne le nombre de lignes trouvées.

---

## `fetch()` vs `fetchAll()`

| Méthode | Retourne | Quand l'utiliser |
|---|---|---|
| `fetch()` | Un seul tableau | Quand on attend 1 résultat (ex: chercher par ID) |
| `fetchAll()` | Tableau de tableaux | Quand on attend plusieurs résultats |

```php
$user = $stmt->fetch();    // ['id' => 1, 'pseudo' => 'Alice', ...]
$users = $stmt->fetchAll(); // [['id' => 1, ...], ['id' => 2, ...], ...]
```

---

## Dans notre projet — Exemple réel

```php
// Dans inscription.php : vérifier si l'email est déjà utilisé
$reqmail = $pdo->prepare("SELECT id FROM membres WHERE mail = :mail");
$reqmail->execute([':mail' => $mail]);
if ($reqmail->rowCount() > 0) {
    return "Adresse mail déjà utilisée !";
}

// Insérer le nouveau membre
$sql = "INSERT INTO membres(pseudo, mail, motdepasse) VALUES(:pseudo, :mail, :mdp)";
$reqInsert = $pdo->prepare($sql);
$reqInsert->execute([
    ':pseudo' => $pseudo,
    ':mail'   => $mail,
    ':mdp'    => password_hash($mdp, PASSWORD_DEFAULT)
]);
```

---

> 🛡️ **Règle absolue** : Toute donnée provenant d'un utilisateur (`$_POST`, `$_GET`, `$_COOKIE`) doit passer par une requête préparée. Sans exception.
