# 10 — La Déconnexion (deconnexion.php)

## Pourquoi détruire la session ?

Quand un utilisateur se déconnecte, il faut supprimer toutes ses informations de la session. Sinon, n'importe qui qui accède au même navigateur pourrait utiliser le compte.

---

## Le fichier `deconnexion.php`

```php
<?php
session_start();
require_once 'flash.php';

// 1. Vider le tableau $_SESSION
$_SESSION = array();

// 2. Stocker le message flash APRÈS avoir vidé la session,
//    mais AVANT de détruire la session
flash_set('success', "Vous avez été déconnecté avec succès.");

// 3. Rediriger
header('Location: connexion.php');
exit();
```

---

## Explication pas à pas

### Étape 1 : Vider `$_SESSION`

```php
$_SESSION = array();
```

On remplace le tableau `$_SESSION` par un tableau vide. Toutes les données (id, pseudo, mail, role) sont effacées.

### Étape 2 : Message flash après vidage

```php
flash_set('success', "Vous avez été déconnecté avec succès.");
```

**Attention** : On appelle `flash_set()` APRÈS avoir vidé `$_SESSION`. C'est pour éviter que la session ne contienne à la fois les données utilisateur ET le message flash.

`flash_set()` réécrit juste `$_SESSION['flash']`, ce qui est correct.

### Pourquoi ne pas appeler `session_destroy()` ?

```php
// On pourrait faire :
session_destroy();
```

`session_destroy()` détruit le fichier de session côté serveur. Mais cela **supprime également le message flash** qu'on vient de stocker ! En ne l'appelant pas, on garde la session (et donc le message) disponible pour la prochaine page.

---

## Le flux complet

```
[Clic sur "Déconnexion"]
         ↓
[deconnexion.php se charge]
         ↓
[$_SESSION = array()]        ← Tout effacé
         ↓
[flash_set('success', ...)]  ← Message stocké dans $_SESSION['flash']
         ↓
[header("Location: connexion.php")]
         ↓
[connexion.php se charge]
         ↓
[header.php → flash_get()]   ← Lit et supprime le message
         ↓
["Vous avez été déconnecté" affiché]
```

---

## Ce qu'il faut retenir

| Action | Code |
|---|---|
| Vider la session | `$_SESSION = array();` |
| Détruire la session (optionnel) | `session_destroy();` |
| Rediriger | `header("Location: page.php"); exit();` |

---

## Protéger contre les accès non autorisés

Si un utilisateur va directement sur `profil.php` après s'être déconnecté :

```php
// En haut de profil.php, editionprofil.php, admin.php...
session_start();

if (!isset($_SESSION['id'])) {
    header("Location: connexion.php");
    exit();
}
```

Comme `$_SESSION['id']` a été effacé, la condition est vraie et l'utilisateur est redirigé.

---

> 🔒 **Bonne pratique** : Ne mettez jamais de données sensibles dans un formulaire caché ou dans une URL (`$_GET`). La session est le seul endroit sécurisé pour stocker l'identité de l'utilisateur côté serveur.
