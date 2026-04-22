# 11 — Le Dashboard Utilisateur (profil.php)

## L'objectif de cette page

Afficher les informations du membre connecté dans un **tableau de bord** avec :
- Une barre latérale (sidebar) avec la navigation et l'avatar
- Le contenu principal avec les informations du compte

---

## Le code complet — `profil.php`

```php
<?php

declare(strict_types=1);
session_start();
require 'db.php';

// On récupère l'ID dans l'URL : profil.php?id=5
if (!isset($_GET['id'])) {
    header("Location: connexion.php");
    exit();
}

$getid = (int)$_GET['id'];

// Récupérer les infos du membre demandé
$requser = $pdo->prepare('SELECT * FROM membres WHERE id = :id');
$requser->execute([':id' => $getid]);
$userinfo = $requser->fetch();

// Si l'utilisateur n'existe pas
if (!$userinfo) {
    die("Utilisateur introuvable.");
}

// Mettre à jour le rôle en session si l'utilisateur regarde son propre profil
// (utile quand un admin change le rôle directement en BDD)
if (isset($_SESSION['id']) && (int)$_SESSION['id'] === (int)$userinfo['id']) {
    $_SESSION['role'] = $userinfo['role'];
}

include 'header.php';
?>

<div class="dashboard-grid">
    <aside class="sidebar">
        <div class="text-center" style="margin-bottom: 2rem;">
            <?php if (!empty($userinfo['avatar'])): ?>
                <img src="membres/avatars/<?= htmlspecialchars($userinfo['avatar']) ?>" 
                     class="avatar-large" 
                     alt="Avatar de <?= htmlspecialchars($userinfo['pseudo']) ?>">
            <?php else: ?>
                <!-- Initiale du pseudo si pas d'avatar -->
                <div class="avatar-large" style="background: #35495e; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: #42b883;">
                    <?= strtoupper(substr($userinfo['pseudo'], 0, 1)) ?>
                </div>
            <?php endif; ?>
            <h3 class="mt-4"><?= htmlspecialchars($userinfo['pseudo']) ?></h3>
            <p style="color: #94a3b8; font-size: 0.9rem;"><?= htmlspecialchars($userinfo['mail']) ?></p>
        </div>

        <ul class="sidebar-nav">
            <li><a href="#" class="active">Tableau de bord</a></li>
            <?php if (isset($_SESSION['id']) && $userinfo['id'] === (int)$_SESSION['id']): ?>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php">Dashboard Admin</a></li>
                <?php endif; ?>
                <li><a href="editionprofil.php">Paramètres du profil</a></li>
                <li><a href="deconnexion.php" style="color: #ef4444;">Déconnexion</a></li>
            <?php endif; ?>
        </ul>
    </aside>

    <main class="main-content">
        <div class="profile-header">
            <div class="user-meta">
                <h2>Bienvenue, <?= htmlspecialchars($userinfo['pseudo']) ?> !</h2>
                <p>Ceci est votre espace membre sécurisé.</p>
            </div>
            <div style="margin-left: auto;">
                <?php if (isset($_SESSION['id']) && $userinfo['id'] === (int)$_SESSION['id']): ?>
                    <a href="editionprofil.php" class="btn btn-primary">Éditer le profil</a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Cartes d'informations -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div class="card" style="margin: 0; max-width: none; padding: 1.5rem;">
                <h4>Informations</h4>
                <p>Email: <?= htmlspecialchars($userinfo['mail']) ?></p>
                <p>ID Membre: #<?= $userinfo['id'] ?></p>
            </div>

            <div class="card" style="margin: 0; max-width: none; padding: 1.5rem;">
                <h4>Statut du compte</h4>
                <p>Type: Utilisateur Standard</p>
                <p>Vérifié: Oui</p>
            </div>
        </div>

        <!-- Message si c'est un visiteur qui regarde le profil -->
        <?php if (!isset($_SESSION['id']) || $userinfo['id'] !== (int)$_SESSION['id']): ?>
            <div class="alert alert-error mt-4">
                Vous consultez ce profil en tant que visiteur. Les options de modification sont masquées.
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include 'footer.php'; ?>
```

---

## Points importants à comprendre

### L'ID dans l'URL : `?id=5`

```php
$getid = (int)$_GET['id'];
```

- `$_GET['id']` récupère la valeur dans l'URL
- `(int)` force la conversion en entier (si quelqu'un tape `?id=abc`, ça devient `0`)

### `htmlspecialchars()` — Protection contre le XSS

```php
<?= htmlspecialchars($userinfo['pseudo']) ?>
```

Si le pseudo contient du HTML comme `<script>alert('hack')</script>`, `htmlspecialchars()` le transforme en :
`&lt;script&gt;alert('hack')&lt;/script&gt;` (inoffensif, affiché comme texte)

### Mise à jour automatique du rôle en session

```php
if (isset($_SESSION['id']) && (int)$_SESSION['id'] === (int)$userinfo['id']) {
    $_SESSION['role'] = $userinfo['role'];
}
```

Si un administrateur a modifié votre rôle directement en BDD, votre session sera mise à jour automatiquement dès que vous consulterez votre profil.

### Différence entre "voir son profil" et "voir le profil d'un autre"

```php
// On affiche les boutons d'édition uniquement au propriétaire du profil
if (isset($_SESSION['id']) && $userinfo['id'] === (int)$_SESSION['id'])
```

Un utilisateur peut consulter le profil d'un autre (ex: `profil.php?id=3`) mais ne verra pas les boutons "Modifier" ou "Déconnexion".

### Avatar par initiale

```php
<?= strtoupper(substr($userinfo['pseudo'], 0, 1)) ?>
```

- `substr($userinfo['pseudo'], 0, 1)` : Prend le premier caractère du pseudo
- `strtoupper(...)` : Met en majuscule
- Résultat : `"alice"` → `"A"`

---

> 💡 **Attention aux ID dans l'URL** : En production, on vérifierait aussi que l'ID correspond à un utilisateur réel et que l'accès est autorisé. Ici on le fait avec `if (!$userinfo) die(...)`.
