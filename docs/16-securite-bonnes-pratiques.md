# 16 — Sécurité : Les Bonnes Pratiques en PHP 8.4

La sécurité n'est pas un plugin, c'est une façon de coder. Voici le résumé des 5 piliers de sécurité que nous avons implémentés dans ce projet.

---

## 1. Paramètres Nommés PDO (Contre les injections SQL)

**Ne jamais** concaténer de variables dans vos requêtes SQL. Utilisez toujours `:nom`.

```php
// ✅ BIEN
$stmt = $pdo->prepare("SELECT * FROM membres WHERE mail = :mail");
$stmt->execute([':mail' => $email]);

// ❌ MAL (Injection possible !)
$pdo->query("SELECT * FROM membres WHERE mail = '$email'");
```

---

## 2. Hashage des Mots de Passe (Contre le vol de données)

On ne stocke jamais de mots de passe lisibles. Si votre base de données est volée, les comptes de vos utilisateurs restent protégés.

```php
// ✅ Hashage (à l'inscription/modification)
$hash = password_hash($password, PASSWORD_DEFAULT);

// ✅ Vérification (à la connexion)
if (password_verify($password_entre, $hash_bdd)) { ... }
```

---

## 3. `htmlspecialchars()` (Contre les failles XSS)

Les failles XSS permettent à un utilisateur malveillant d'injecter du JavaScript sur votre page (via son pseudo par exemple). `htmlspecialchars()` neutralise cela.

```php
// ✅ BIEN
<h2>Bienvenue, <?= htmlspecialchars($user['pseudo']) ?></h2>

// ❌ DANGEREUX
<h2>Bienvenue, <?= $user['pseudo'] ?></h2>
```

---

## 4. `session_start()` et `session_regenerate_id()`

Les sessions doivent être gérées avec soin. 

- Toujours appeler `session_start()` avant tout affichage.
- Pour encore plus de sécurité, vous pouvez appeler `session_regenerate_id(true)` lors de la connexion pour éviter le "Session Fixation".

---

## 5. Validation et Assainissement (Sanitization)

Ne faites jamais confiance aux données envoyées par l'utilisateur.

```php
// ✅ Nettoyer un pseudo (supprimer les balises HTML)
$pseudo = strip_tags($_POST['pseudo']);

// ✅ Valider un email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ... }

// ✅ Forcer un type entier pour un ID
$id = (int)$_GET['id'];
```

---

## Bonus : Types Stricts

En utilisant `declare(strict_types=1);` en haut de vos fichiers, vous obligez PHP à être rigoureux avec les types de données. Cela évite des bugs silencieux qui peuvent devenir des failles de sécurité.

---

> 🛡️ **Règle d'or** : Considérez que **toute entrée utilisateur est hostile**. Filtrez à l'entrée (POST/GET), nettoyez au traitement, et échappez à la sortie (HTML).
