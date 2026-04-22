# 13 — Édition du Profil : Avatar & Logique POST (editionprofil.php) — Partie 2

## La fonction `handleAvatar()` — Upload de fichier

```php
/**
 * 4. Handle Avatar Update
 */
function handleAvatar(PDO $pdo, int $id, array $file): ?string {
    if (empty($file['name'])) return null; // Pas de fichier sélectionné

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $path = "membres/avatars/";

    // Vérification du format (whitelist)
    if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) return "Format invalide.";

    // Vérification du poids (2 Mo max)
    if ($file['size'] > 2097152) return "Fichier trop lourd.";

    // Créer le dossier s'il n'existe pas
    if (!is_dir($path)) mkdir($path, 0777, true);

    // Nommer le fichier avec l'ID de l'utilisateur
    $filename = "$id.$ext";

    // Déplacer le fichier uploadé
    if (move_uploaded_file($file['tmp_name'], $path . $filename)) {
        $pdo->prepare("UPDATE membres SET avatar = :v WHERE id = :id")
            ->execute(['v' => $filename, 'id' => $id]);
        return "success";
    }

    return "Erreur upload.";
}
```

---

## Comment fonctionne l'upload en PHP ?

Quand un formulaire a `enctype="multipart/form-data"` et un `<input type="file">`, PHP remplit le superglobal `$_FILES` :

```php
// Si l'utilisateur a uploadé un fichier nommé "avatar" dans le formulaire :
$_FILES['avatar'] = [
    'name'     => 'photo.jpg',     // Nom original du fichier
    'type'     => 'image/jpeg',    // Type MIME
    'tmp_name' => '/tmp/phpXXXXX', // Chemin temporaire côté serveur
    'error'    => 0,               // 0 = pas d'erreur
    'size'     => 154000           // Taille en octets
];
```

Le fichier est d'abord stocké dans un dossier **temporaire** du serveur. On doit le "déplacer" vers notre dossier final avec `move_uploaded_file()`.

---

## Whitelist des extensions (sécurité)

```php
if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) return "Format invalide.";
```

On n'autorise que les images. Si quelqu'un essaie d'uploader un fichier PHP (`hack.php`), c'est refusé. C'est une **whitelist** (liste d'autorisés) plutôt qu'une blacklist (liste d'interdits).

> La whitelist est toujours plus sûre que la blacklist. La blacklist peut oublier un format dangereux.

---

## Nommage du fichier avec l'ID utilisateur

```php
$filename = "$id.$ext"; // ex: "5.jpg"
```

On nomme le fichier avec l'ID de l'utilisateur. Avantages :
- Pas de collision de noms
- On sait à qui appartient chaque avatar
- Si l'utilisateur change son avatar, l'ancien est écrasé automatiquement

---

## La logique POST principale

```php
// Initial fetch (avant le POST)
$requser = $pdo->prepare("SELECT * FROM membres WHERE id = :id");
$requser->execute([':id' => $_SESSION['id']]);
$user = $requser->fetch();

$erreur = null;
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_SESSION['id'];

    // Appeler les 4 fonctions
    $resPseudo = handlePseudo($pdo, $uid, strip_tags($_POST['newpseudo'] ?? ''), $user['pseudo']);
    $resMail   = handleEmail($pdo, $uid, filter_input(INPUT_POST, 'newmail', FILTER_SANITIZE_EMAIL), $user['mail']);
    $resPass   = handlePassword($pdo, $uid, $_POST['newmdp1'] ?? '', $_POST['newmdp2'] ?? '');
    $resAvatar = handleAvatar($pdo, $uid, $_FILES['avatar'] ?? []);

    // Collecter la première erreur éventuelle avec match
    $erreur = match(true) {
        is_string($resPseudo) && $resPseudo !== "success" => $resPseudo,
        is_string($resMail) && $resMail !== "success"     => $resMail,
        is_string($resPass) && $resPass !== "success"     => $resPass,
        is_string($resAvatar) && $resAvatar !== "success" => $resAvatar,
        default => null
    };

    if (!$erreur && ($resPseudo || $resMail || $resPass || $resAvatar)) {
        flash_set('success', "Profil mis à jour avec succès !");
        header("Location: editionprofil.php");
        exit();
    } elseif ($erreur) {
        flash_set('error', $erreur);
        header("Location: editionprofil.php");
        exit();
    }
}
```

---

## `match(true)` — La sélection élégante de la première erreur

```php
$erreur = match(true) {
    is_string($resPseudo) && $resPseudo !== "success" => $resPseudo,
    is_string($resMail)   && $resMail   !== "success" => $resMail,
    // ...
    default => null
};
```

`match` évalue chaque condition de haut en bas et s'arrête à la première vraie. C'est l'équivalent d'un long `if/elseif/else`, mais plus lisible.

- Si `$resPseudo` est une erreur → c'est elle qu'on affiche
- Sinon, on passe à `$resMail`, etc.
- Si aucune erreur → `$erreur = null`

---

## Le formulaire HTML

```php
include 'header.php';
?>

<div class="dashboard-grid">
    <aside class="sidebar">
        <ul class="sidebar-nav">
            <li><a href="profil.php?id=<?= $_SESSION['id'] ?>">Tableau de bord</a></li>
            <li><a href="#" class="active">Paramètres du profil</a></li>
            <li><a href="deconnexion.php" style="color: #ef4444;">Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="card" style="margin: 0; max-width: none;">
            <h2 style="margin-bottom: 2rem;">Édition du profil</h2>

            <!-- IMPORTANT : enctype="multipart/form-data" obligatoire pour l'upload -->
            <form method="POST" action="" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Pseudo</label>
                        <!-- value="" pré-remplit le champ avec la valeur actuelle -->
                        <input type="text" name="newpseudo" value="<?= htmlspecialchars($user['pseudo']); ?>" />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="newmail" value="<?= htmlspecialchars($user['mail']); ?>" />
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Nouveau mot de passe</label>
                        <input type="password" name="newmdp1" placeholder="Laissez vide pour ne pas changer" />
                    </div>
                    <div class="form-group">
                        <label>Confirmation</label>
                        <input type="password" name="newmdp2" placeholder="Confirmez le nouveau mdp" />
                    </div>
                </div>

                <div class="form-group">
                    <label>Avatar</label>
                    <div style="display: flex; align-items: center; gap: 1rem;">
                        <?php if (!empty($user['avatar'])): ?>
                            <!-- Afficher l'avatar actuel -->
                            <img src="membres/avatars/<?= htmlspecialchars($user['avatar']) ?>" 
                                 style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                        <input type="file" name="avatar" />
                    </div>
                </div>

                <div style="margin-top: 2rem; display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    <a href="profil.php?id=<?= $_SESSION['id'] ?>" class="btn btn-outline">Annuler</a>
                </div>
            </form>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
```

---

## Pourquoi `enctype="multipart/form-data"` ?

Sans cet attribut sur le formulaire, les fichiers ne sont pas envoyés au serveur. C'est **obligatoire** pour tout formulaire avec un `<input type="file">`.

---

> 🎯 **Récapitulatif** : 4 fonctions dédiées → appel dans le POST → match() pour collecter l'erreur → flash + redirect. Ce pattern est propre, maintenable et évolutif.
