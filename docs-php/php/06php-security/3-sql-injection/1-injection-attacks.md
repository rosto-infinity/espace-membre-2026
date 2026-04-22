---
source_course: "php-security"
source_lesson: "php-security-sql-injection-attacks"
---

# Comprendre l'Injection SQL

L'injection SQL est l'une des vulnérabilités web **les plus dangereuses et les plus courantes**. Elle permet aux attaquants d'exécuter des commandes SQL arbitraires.

## Comment Fonctionne l'Injection SQL

```php
<?php
// CODE VULNÉRABLE - Ne jamais faire ça !
$username = $_POST['username'];
$query = "SELECT * FROM users WHERE username = '$username'";

// Entrée normale : "jean"
// Requête : SELECT * FROM users WHERE username = 'jean'

// Entrée malveillante : "' OR '1'='1"
// Requête : SELECT * FROM users WHERE username = '' OR '1'='1'
// Ceci retourne TOUS les utilisateurs !

// Pire encore : "'; DROP TABLE users; --"
// Requête : SELECT * FROM users WHERE username = ''; DROP TABLE users; --'
// Ceci SUPPRIME toute la table !
```

## Types d'Injection SQL

### 1. Injection Classique (In-band)

```php
<?php
// L'attaquant voit les résultats directement
$id = $_GET['id'];  // Entrée : "1 UNION SELECT username, password FROM users"
$query = "SELECT name FROM products WHERE id = $id";
// Retourne tous les noms d'utilisateurs et mots de passe !
```

### 2. Injection Aveugle (Blind)

```php
<?php
// L'attaquant déduit les données depuis le comportement
$id = $_GET['id'];  // Entrée : "1 AND 1=1" vs "1 AND 1=2"
// Des réponses différentes révèlent des informations
```

### 3. Injection Basée sur le Temps

```php
<?php
// Entrée : "1; SELECT SLEEP(5)--"
// Si la page met 5 secondes, l'injection fonctionne
```

## Exemples d'Attaques Réelles

```php
<?php
// Contournement de connexion
$username = "admin'--";
$password = "n'importe quoi";
$query = "SELECT * FROM users WHERE username='$username' AND password='$password'";
// Devient : SELECT * FROM users WHERE username='admin'--' AND password='n'importe quoi'
// Le -- commente la vérification du mot de passe !

// Extraction de données
$id = "1 UNION SELECT credit_card, cvv, expiry FROM payments--";
$query = "SELECT name, price FROM products WHERE id = $id";
// Retourne les données de carte de crédit au lieu des infos produit !
```

## Les Dommages

- **Vol de données** : Voler des bases de données entières
- **Modification de données** : Altérer des enregistrements, prix, permissions
- **Suppression de données** : Supprimer des tables, tronquer des données
- **Contournement d'authentification** : Se connecter en tant que n'importe quel utilisateur
- **Exécution de code à distance** : Certaines bases de données autorisent des commandes OS

## Les Grimoires

- [Prévention de l'Injection SQL (Documentation Officielle)](https://www.php.net/manual/en/security.database.sql-injection.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
