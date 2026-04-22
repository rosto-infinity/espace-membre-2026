---
source_course: "php-essentials"
source_lesson: "php-essentials-sessions-cookies"
---

# Sessions et Cookies

HTTP est sans état (stateless) — les sessions et les cookies permettent de maintenir un état entre les requêtes.

## Cookies

Stockés dans le navigateur du client :

```php
<?php
// Définir un cookie (doit être avant tout affichage)
setcookie('nom_utilisateur', 'Jean', [
    'expires'  => time() + (86400 * 30), // 30 jours
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,       // HTTPS uniquement
    'httponly' => true,       // Pas d'accès JavaScript
    'samesite' => 'Strict'    // Protection CSRF
]);

// Lire un cookie
$nomUtilisateur = $_COOKIE['nom_utilisateur'] ?? 'Invité';

// Supprimer un cookie (expiration dans le passé)
setcookie('nom_utilisateur', '', time() - 3600);
```

## Sessions

Stockage côté serveur, plus sécurisé pour les données sensibles :

```php
<?php
// Démarrer la session (doit être avant tout affichage)
session_start();

// Définir des données de session
$_SESSION['user_id']   = 123;
$_SESSION['nom']       = 'Jean';
$_SESSION['est_admin'] = false;

// Lire des données de session
$userId = $_SESSION['user_id'] ?? null;

// Vérifier si une variable de session existe
if (isset($_SESSION['nom'])) {
    echo "Bonjour, " . $_SESSION['nom'];
}

// Supprimer une variable de session spécifique
unset($_SESSION['donnees_temp']);

// Détruire la session entière (déconnexion)
session_destroy();
```

## Sécurité des Sessions

```php
<?php
// Configuration sécurisée (avant session_start)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure',   1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);

session_start();

// Régénérer l'ID après connexion (prévention du session fixation)
if ($connexionReussie) {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $utilisateur->id;
}
```

## Messages Flash

Messages à usage unique qui disparaissent après l'affichage :

```php
<?php
session_start();

function setFlash(string $type, string $message): void {
    $_SESSION['flash'][$type] = $message;
}

function getFlash(string $type): ?string {
    $message = $_SESSION['flash'][$type] ?? null;
    unset($_SESSION['flash'][$type]);
    return $message;
}

// Définir un message flash
setFlash('succes', 'Compte créé avec succès !');

// À la prochaine requête
if ($message = getFlash('succes')) {
    echo "<div class='alert succes'>$message</div>";
}
```

## Cookies vs Sessions

| Fonctionnalité   | Cookies                    | Sessions                |
| ---------------- | -------------------------- | ----------------------- |
| Stockage         | Client (navigateur)        | Serveur                 |
| Sécurité         | Moins sécurisé             | Plus sécurisé           |
| Limite de taille | ~4 Ko                      | Aucune limite           |
| Durée de vie     | Configurable               | Session navigateur      |
| Utilisation      | Préférences, "se souvenir" | Auth, données sensibles |

## Exemples de code

**Fonctions d'authentification basées sur les sessions**

```php
<?php
session_start();

function connecter(string $email, string $motdepasse): bool {
    // Vérifier les identifiants (dans une vraie app, interroger la BDD)
    $utilisateur = trouverUtilisateurParEmail($email);

    if ($utilisateur && password_verify($motdepasse, $utilisateur['hash_mdp'])) {
        // Régénérer l'ID de session pour la sécurité
        session_regenerate_id(true);

        $_SESSION['user_id']      = $utilisateur['id'];
        $_SESSION['user_email']   = $utilisateur['email'];
        $_SESSION['connecte_le']  = time();

        return true;
    }
    return false;
}

function estConnecte(): bool {
    return isset($_SESSION['user_id']);
}

function deconnecter(): void {
    $_SESSION = [];

    // Supprimer le cookie de session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 42000);
    }

    session_destroy();
}

function requireAuth(): void {
    if (!estConnecte()) {
        header('Location: /connexion.php');
        exit;
    }
}
?>
```

## Ressources

- [Sessions PHP](https://www.php.net/manual/fr/book.session.php) — Documentation complète sur la gestion des sessions
- [Sécurité des Sessions](https://www.php.net/manual/fr/session.security.php) — Bonnes pratiques pour des sessions sécurisées

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
