---
source_course: "php-essentials"
source_lesson: "php-essentials-handling-forms"
---

# Traiter les Soumissions de Formulaires

Les formulaires sont le principal moyen d'interaction des utilisateurs avec les applications PHP. Savoir gérer les données de formulaire de façon sécurisée est essentiel.

## Méthodes GET vs POST

### Méthode GET

- Données visibles dans l'URL
- Peut être mis en favori (bookmarkable)
- Taille de données limitée (~2000 caractères)
- Utiliser pour : recherches, filtres, données non sensibles

### Méthode POST

- Données dans le corps de la requête (non visible dans l'URL)
- Ne peut pas être mis en favori
- Pas de limite de taille
- Utiliser pour : formulaires, données sensibles, envoi de fichiers

## Traitement de Formulaire de Base

```html
<!-- contact.html -->
<form action="traitement.php" method="POST">
  <input type="text" name="nom" required />
  <input type="email" name="email" required />
  <textarea name="message"></textarea>
  <button type="submit">Envoyer</button>
</form>
```

```php
<?php
// traitement.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = $_POST['nom']     ?? '';
    $email   = $_POST['email']   ?? '';
    $message = $_POST['message'] ?? '';

    // Traiter les données...
}
```

## Les Superglobales

```php
<?php
$_GET['param'];      // Paramètres de l'URL (query string)
$_POST['champ'];     // Données du formulaire en POST
$_REQUEST['cle'];    // Combinaison GET + POST (à éviter)
$_SERVER['cle'];     // Informations sur le serveur
$_FILES['upload'];   // Fichiers envoyés
$_COOKIE['nom'];     // Valeurs des cookies
$_SESSION['user'];   // Données de session
```

## Formulaires Auto-soumis

```php
<?php
$message = '';
$nom = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom     = $_POST['nom'] ?? '';
    $message = "Bonjour, $nom !";
}
?>

<form method="POST" action="">
    <input type="text" name="nom" value="<?= htmlspecialchars($nom) ?>">
    <button type="submit">Envoyer</button>
</form>

<?php if ($message): ?>
    <p><?= htmlspecialchars($message) ?></p>
<?php endif; ?>
```

## Vérifier une Soumission de Formulaire

```php
<?php
// Méthode 1 : Vérifier la méthode de la requête
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Formulaire soumis
}

// Méthode 2 : Vérifier un champ spécifique
if (isset($_POST['submit'])) {
    // Le bouton a été cliqué
}

// Méthode 3 : Vérifier si des données POST existent
if (!empty($_POST)) {
    // Des données POST sont présentes
}
```

## Exemples de code

**Formulaire complet avec validation et gestion des erreurs**

```php
<?php
$erreurs  = [];
$succes   = false;
$donnees  = ['nom' => '', 'email' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $donnees = [
        'nom'     => trim($_POST['nom']     ?? ''),
        'email'   => trim($_POST['email']   ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    if (empty($donnees['nom'])) {
        $erreurs['nom'] = 'Le nom est requis';
    }

    if (!filter_var($donnees['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'Une adresse email valide est requise';
    }

    if (empty($erreurs)) {
        // Sauvegarder en BDD, envoyer un email, etc.
        $succes = true;
    }
}
?>

<?php if ($succes): ?>
    <p class="success">Merci pour votre message !</p>
<?php else: ?>
    <form method="POST">
        <input name="nom" value="<?= htmlspecialchars($donnees['nom']) ?>">
        <?php if (isset($erreurs['nom'])): ?>
            <span class="error"><?= $erreurs['nom'] ?></span>
        <?php endif; ?>

        <input name="email" value="<?= htmlspecialchars($donnees['email']) ?>">
        <textarea name="message"><?= htmlspecialchars($donnees['message']) ?></textarea>
        <button type="submit">Envoyer</button>
    </form>
<?php endif; ?>
```

## Ressources

- [Formulaires PHP](https://www.php.net/manual/fr/tutorial.forms.php) — Tutoriel officiel sur les formulaires PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
