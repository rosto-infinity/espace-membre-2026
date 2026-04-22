# 03 — Connexion à la BDD avec PDO (db.php)

## Pourquoi PDO et pas `mysqli_` ?

Il existe deux façons de parler à MySQL en PHP :
- `mysqli_` : Fonctionne uniquement avec MySQL
- **PDO** (PHP Data Objects) : Fonctionne avec MySQL, PostgreSQL, SQLite, etc.

On utilise PDO car :
1. Il est **universel** (peut changer de base de données sans réécrire le code)
2. Il a un meilleur système de **gestion d'erreurs**
3. Les **requêtes préparées** (sécurité anti-injections SQL) sont plus simples

---

## Le fichier `db.php`

Ce fichier est inclus dans **toutes les pages** qui ont besoin de la base de données.

```php
<?php
$dsn = 'mysql:host=127.0.0.1;dbname=espace_membre_2026';
$username = 'valet';
$password = 'valet';
$options = [];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
```

---

## Explication ligne par ligne

### Le DSN (Data Source Name)
```php
$dsn = 'mysql:host=127.0.0.1;dbname=espace_membre_2026';
```
Le DSN est une "adresse" qui indique à PDO :
- **`mysql:`** → Le type de base de données
- **`host=127.0.0.1`** → L'adresse du serveur (127.0.0.1 = votre propre ordinateur)
- **`dbname=espace_membre_2026`** → Le nom de la base de données

### Les identifiants
```php
$username = 'valet';  // Votre nom d'utilisateur MySQL
$password = 'valet';  // Votre mot de passe MySQL
```
> ⚠️ En production (site en ligne), ces informations sont dans un fichier `.env`, jamais dans le code !

### Le bloc try/catch
```php
try {
    $pdo = new PDO($dsn, $username, $password, $options);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
```

- **`try`** : On essaie de se connecter
- **`new PDO(...)`** : On crée la connexion
- **`setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)`** : Si une requête SQL échoue, PHP lancera une **exception** (erreur rattrapable) au lieu d'échouer silencieusement
- **`catch`** : Si la connexion échoue, on arrête tout et on affiche l'erreur

---

## Comment l'utiliser dans les autres fichiers

Dans chaque fichier PHP qui a besoin de la BDD :

```php
<?php
require_once 'db.php'; // La variable $pdo est maintenant disponible

// Maintenant on peut faire des requêtes :
$stmt = $pdo->prepare("SELECT * FROM membres");
$stmt->execute();
$membres = $stmt->fetchAll();
```

---

## Les erreurs fréquentes des juniors

### ❌ Oublier `require_once`
Si vous oubliez d'inclure `db.php`, PHP dira :
```
Undefined variable: pdo
```

### ❌ Mauvais nom de base de données
```php
$dsn = 'mysql:host=127.0.0.1;dbname=MAUVAIS_NOM';
// Erreur : SQLSTATE[HY000] [1049] Unknown database
```

### ❌ Mauvais mot de passe MySQL
```php
$password = 'mauvais_mdp';
// Erreur : SQLSTATE[HY000] [1045] Access denied for user
```

---

> 🔑 **Retenez** : `db.php` crée la variable `$pdo`. Incluez ce fichier en haut de chaque page PHP qui parle à la base de données.
