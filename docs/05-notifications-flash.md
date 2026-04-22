# 05 — Les Notifications Flash (flash.php)

## Le problème : rediriger et afficher un message

Quand un utilisateur soumet un formulaire, on veut :
1. Traiter les données (PHP)
2. Rediriger vers une autre page
3. Afficher un message "Succès !" ou "Erreur !" sur cette nouvelle page

Le problème c'est qu'après une redirection (`header("Location: ...")`), les variables PHP disparaissent. Comment garder le message ?

---

## La solution : les Sessions Flash

On stocke le message dans la **session** avant de rediriger. Sur la page suivante, on lit et on **supprime immédiatement** le message de la session (d'où le nom "flash" : il apparaît une seule fois).

---

## Le fichier `flash.php`

```php
<?php

declare(strict_types=1);

/**
 * Enregistre un message flash en session
 * @param 'success'|'error' $type
 */
function flash_set(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Récupère et supprime le message flash de la session
 * Retourne null s'il n'y a pas de message en attente
 */
function flash_get(): ?array
{
    if (!isset($_SESSION['flash'])) return null;
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}
```

---

## Comment ça fonctionne ?

### Étape 1 : On enregistre le message (avant la redirection)

```php
// Dans connexion.php, après une connexion réussie :
flash_set('success', "Heureux de vous revoir !");
header("Location: profil.php?id=" . $_SESSION['id']);
exit();
```

```php
// En cas d'erreur :
flash_set('error', "Mauvais mot de passe !");
header("Location: connexion.php");
exit();
```

### Étape 2 : On affiche le message (dans le header, inclus dans toutes les pages)

```php
// Dans header.php :
<?php
require_once 'flash.php';
$flash = flash_get(); // On récupère ET supprime le message
?>

<?php if ($flash): ?>
    <div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($flash['message']) ?>
    </div>
<?php endif; ?>
```

---

## Pourquoi c'est dans le `header.php` ?

Parce que le header est inclus dans **toutes les pages**. En mettant le code d'affichage flash dans le header, le message apparaîtra automatiquement sur la bonne page, peu importe d'où vient la redirection.

On n'a pas besoin de répéter le code d'affichage dans chaque fichier.

---

## Le CSS des alertes (à copier dans votre index.css)

```css
/* Alertes */
.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-size: 0.9rem;
}

.alert-error {
    background-color: rgba(239, 68, 68, 0.05);
    border: 1px solid #ef4444;
    color: #ef4444;
}

.alert-success {
    background-color: rgba(66, 184, 131, 0.05);
    border: 1px solid #42b883;
    color: #42b883;
}
```

---

## Résumé du flux

```
[Formulaire soumis]
       ↓
[PHP traite les données]
       ↓
[flash_set('success', 'Message OK !')]   ← Stocke en session
       ↓
[header("Location: autre-page.php")]     ← Redirige
       ↓
[autre-page.php se charge]
       ↓
[header.php appelle flash_get()]         ← Lit ET supprime de la session
       ↓
[Message affiché à l'utilisateur]        ← Une seule fois !
```

---

## Points importants

### ❗ `session_start()` doit être appelé AVANT
Les sessions ne fonctionnent pas sans `session_start()` en tout début de fichier.

### ❗ `flash_get()` supprime le message
Après `flash_get()`, le message est **effacé** de la session. Il n'apparaîtra pas sur les pages suivantes.

### ❗ `exit()` après la redirection
Toujours mettre `exit()` après `header("Location: ...")` pour arrêter l'exécution du script.

```php
header("Location: connexion.php");
exit(); // ← OBLIGATOIRE
```

---

> 💡 Ce pattern (stocker en session → rediriger → lire et afficher) est appelé le **Pattern PRG** (Post-Redirect-Get). C'est une bonne pratique qui évite que l'utilisateur re-soumette le formulaire en rafraîchissant la page.
