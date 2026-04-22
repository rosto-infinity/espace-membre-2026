# 09 — La Connexion et les Sessions (connexion.php)

## Les Sessions PHP : comment ça fonctionne ?

Une **session** est un moyen de stocker des informations sur l'utilisateur entre les pages. HTTP est "sans état" (stateless) : chaque requête est indépendante. Les sessions permettent de "mémoriser" qu'un utilisateur est connecté.

```
Navigateur                    Serveur PHP
    │                              │
    │── GET /index.php ──────────→ │ (Pas de session = visiteur)
    │← HTML ────────────────────── │
    │                              │
    │── POST /connexion.php ──────→│ (Identifiants envoyés)
    │                              │ → Vérifie en BDD
    │                              │ → session_start()
    │                              │ → $_SESSION['id'] = 5
    │← Redirect /profil.php ───── │ (Crée un cookie de session)
    │                              │
    │── GET /profil.php ─────────→ │ (Cookie envoyé automatiquement)
    │                              │ → PHP récupère la session
    │← HTML (page de profil) ───── │ ($_SESSION['id'] = 5 ✓)
```

---

## Le fichier `connexion.php`

```php
<?php

declare(strict_types=1);
session_start();
require_once 'db.php';
require_once 'flash.php';

/**
 * Authenticate a user
 */
function authenticateUser(PDO $pdo, string $mailconnect, string $mdpconnect): string {
    if (empty($mailconnect) || empty($mdpconnect)) {
        return "Tous les champs doivent être complétés !";
    }

    $requser = $pdo->prepare("SELECT * FROM membres WHERE mail = :mail");
    $requser->execute([':mail' => $mailconnect]);
    $userinfo = $requser->fetch();

    if (!$userinfo) {
        return "Compte inexistant !";
    }

    if (!password_verify($mdpconnect, $userinfo['motdepasse'])) {
        return "Mauvais mot de passe !";
    }

    // Stocker les informations en session
    $_SESSION['id']     = $userinfo['id'];
    $_SESSION['pseudo'] = $userinfo['pseudo'];
    $_SESSION['mail']   = $userinfo['mail'];
    $_SESSION['role']   = $userinfo['role'];

    return "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailconnect = filter_input(INPUT_POST, 'mailconnect', FILTER_SANITIZE_EMAIL) ?? '';
    $mdpconnect  = $_POST['mdpconnect'] ?? '';

    $result = authenticateUser($pdo, $mailconnect, $mdpconnect);

    if ($result === "success") {
        flash_set('success', "Heureux de vous revoir !");
        header("Location: profil.php?id=" . $_SESSION['id']);
        exit();
    } else {
        flash_set('error', $result);
        header("Location: connexion.php");
        exit();
    }
}

include 'header.php';
?>

<div class="card">
    <h2 class="text-center" style="margin-bottom: 2rem;">Connexion</h2>

    <form method="POST" action="">
        <div class="form-group">
            <label for="mailconnect">E-mail</label>
            <input type="email" name="mailconnect" id="mailconnect" placeholder="Votre mail" required />
        </div>

        <div class="form-group">
            <label for="mdpconnect">Mot de passe</label>
            <input type="password" name="mdpconnect" id="mdpconnect" placeholder="Votre mot de passe" required />
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
            Se connecter
        </button>

        <p class="text-center mt-4" style="font-size: 0.9rem; color: #94a3b8;">
            Pas encore de compte ? <a href="inscription.php" style="color: #42b883; text-decoration: none;">S'inscrire</a>
        </p>
    </form>
</div>

<?php include 'footer.php'; ?>
```

---

## Analyse détaillée

### `password_verify()` — La vérification du mot de passe

```php
if (!password_verify($mdpconnect, $userinfo['motdepasse'])) {
    return "Mauvais mot de passe !";
}
```

- `$mdpconnect` : Le mot de passe tapé par l'utilisateur (en clair)
- `$userinfo['motdepasse']` : Le hash stocké en BDD
- `password_verify()` compare intelligemment les deux. Il comprend le format bcrypt.

```
password_hash("MonMdp123")  → "$2y$12$dhm3EJA..." (stocké en BDD)
password_verify("MonMdp123", "$2y$12$dhm3EJA...") → true ✓
password_verify("MauvaisMdp", "$2y$12$dhm3EJA...") → false ✗
```

### Ce qu'on stocke en session

```php
$_SESSION['id']     = $userinfo['id'];     // Identifiant unique
$_SESSION['pseudo'] = $userinfo['pseudo']; // Nom affiché
$_SESSION['mail']   = $userinfo['mail'];   // Email
$_SESSION['role']   = $userinfo['role'];   // 'user' ou 'admin'
```

Ces 4 informations sont disponibles sur toutes les pages tant que l'utilisateur est connecté.

### Accéder aux données de session sur n'importe quelle page

```php
<?php
session_start(); // Toujours en premier !

if (isset($_SESSION['id'])) {
    echo "Bonjour " . $_SESSION['pseudo'] . " !";
} else {
    echo "Vous n'êtes pas connecté.";
}
```

---

## Protection des pages privées

Sur chaque page qui nécessite d'être connecté :

```php
<?php
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit();
}

// Ici, l'utilisateur est forcément connecté
```

---

## Message d'erreur générique

Notez qu'on affiche "Compte inexistant" plutôt que "Email non trouvé". Cela évite de confirmer à un attaquant que l'email existe dans la base.

En production, on afficherait un message encore plus générique :
```
"Identifiants incorrects." 
```

Cela ne donne aucune information utile à quelqu'un qui essaie de deviner les comptes.

---

> 🔑 **Mémoriser** : `session_start()` → `$_SESSION['id'] = ...` → `header("Location: ...")` → `exit()`. C'est le flux de connexion.
