# 07 — Le Header & Footer Partagés

## Pourquoi des fichiers partagés ?

Imaginez devoir copier-coller le même code HTML (navigation, liens CSS, etc.) dans chaque fichier PHP. Si vous devez modifier un seul lien, vous devez le changer partout. Catastrophique.

La solution : **un seul fichier** pour l'en-tête, inclus dans toutes les pages.

---

## Le fichier `header.php`

```php
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Espace Membre - PHP 8.4</title>
    <link rel="stylesheet" href="index.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <span>RostoDev</span>
        </a>
        
        <ul class="nav-links">
            <li>
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active-link' : '' ?>">
                    Accueil
                </a>
            </li>
            <?php if(isset($_SESSION['id'])): ?>
                <li>
                    <a href="profil.php?id=<?= $_SESSION['id'] ?>" 
                       class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active-link' : '' ?>">
                        Mon Profil
                    </a>
                </li>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <li>
                        <a href="admin.php" 
                           class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active-link' : '' ?>">
                            Admin
                        </a>
                    </li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-auth">
            <?php if(isset($_SESSION['id'])): ?>
                <a href="deconnexion.php" class="btn btn-outline">Déconnexion</a>
            <?php else: ?>
                <a href="connexion.php" class="btn btn-outline">Connexion</a>
                <a href="inscription.php" class="btn btn-primary">Inscription</a>
            <?php endif; ?>
        </div>
    </nav>

<?php 
require_once 'flash.php';
$flash = flash_get();
?>
    <div class="container">
        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" style="margin-bottom: 2rem;">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>
```

---

## Explications clés

### La navigation dynamique
```php
class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active-link' : '' ?>"
```
- `$_SERVER['PHP_SELF']` : Contient le chemin du fichier actuel (ex: `/inscription.php`)
- `basename(...)` : Garde uniquement le nom du fichier (ex: `inscription.php`)
- Si le fichier actuel correspond, on ajoute la classe CSS `active-link`

### Menu conditionnel selon la session
```php
<?php if(isset($_SESSION['id'])): ?>
    <!-- Affiché uniquement si connecté -->
<?php endif; ?>

<?php if($_SESSION['role'] === 'admin'): ?>
    <!-- Affiché uniquement pour les admins -->
<?php endif; ?>
```

### Affichage automatique des messages flash
Le header appelle `flash_get()` qui récupère et supprime le message de la session, puis l'affiche.

---

## Le fichier `footer.php`

```php
    </div><!-- /.container -->

    <footer style="text-align: center; padding: 2rem; color: #94a3b8; font-size: 0.85rem;">
        &copy; <?= date('Y') ?> RostoDev - Propulsé par PHP 8.4
    </footer>
</body>
</html>
```

- `date('Y')` : Affiche l'année actuelle automatiquement (pas besoin de le mettre à jour chaque année)

---

## Comment inclure ces fichiers dans chaque page

```php
<?php
declare(strict_types=1);
session_start();
require_once 'db.php';

// ... traitement PHP ...

include 'header.php'; // ← Inclure le header AVANT le HTML de la page
?>

<div>
    <!-- Votre contenu ici -->
</div>

<?php include 'footer.php'; ?> <!-- ← Inclure le footer APRÈS -->
```

---

## `include` vs `require_once`

| Fonction | Comportement si le fichier n'existe pas |
|---|---|
| `include` | Avertissement, le script continue |
| `require` | Erreur fatale, le script s'arrête |
| `include_once` | Inclus une seule fois, avertissement si manquant |
| `require_once` | Inclus une seule fois, erreur fatale si manquant |

→ On utilise `require_once` pour les fichiers critiques comme `db.php` et `flash.php`.
→ On utilise `include` pour les templates visuels comme `header.php` et `footer.php`.

---

> 💡 **Astuce** : Le `session_start()` doit être appelé **dans chaque page** AVANT d'inclure le header, sinon la session n'est pas disponible quand le header essaie de lire `$_SESSION`.
