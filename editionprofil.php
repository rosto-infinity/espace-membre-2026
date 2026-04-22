<?php
declare(strict_types=1);
session_start();
require 'db.php';
require 'flash.php';

if (!isset($_SESSION['id'])) {
    header('Location: connexion.php');
    exit();
}

/**
 * Utility: Check if a value is already taken
 */
function isTaken(PDO $pdo, string $col, string $val, int $id): bool {
    $stmt = $pdo->prepare("SELECT id FROM membres WHERE $col = :val AND id != :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    return (bool)$stmt->fetch();
}

/**
 * 1. Handle Pseudo Update
 */
function handlePseudo(PDO $pdo, int $id, string $val, string $current): ?string {
    if (empty($val) || $val === $current) return null;
    if (strlen($val) > 255) return "Pseudo trop long.";
    if (isTaken($pdo, 'pseudo', $val, $id)) return "Pseudo déjà utilisé.";
    
    $stmt = $pdo->prepare("UPDATE membres SET pseudo = :val WHERE id = :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    $_SESSION['pseudo'] = $val;
    return "success";
}

/**
 * 2. Handle Email Update
 */
function handleEmail(PDO $pdo, int $id, ?string $val, string $current): ?string {
    if (empty($val) || $val === $current) return null;
    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) return "Email invalide.";
    if (isTaken($pdo, 'mail', $val, $id)) return "Email déjà utilisé.";

    $stmt = $pdo->prepare("UPDATE membres SET mail = :val WHERE id = :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    $_SESSION['mail'] = $val;
    return "success";
}

/**
 * 3. Handle Password Update
 */
function handlePassword(PDO $pdo, int $id, string $p1, string $p2): ?string {
    if (empty($p1)) return null;
    if ($p1 !== $p2) return "Les mots de passe ne correspondent pas.";
    if (strlen($p1) < 8) return "8 caractères minimum.";

    $stmt = $pdo->prepare("UPDATE membres SET motdepasse = :val WHERE id = :id");
    $stmt->execute([':val' => password_hash($p1, PASSWORD_DEFAULT), ':id' => $id]);
    return "success";
}

/**
 * 4. Handle Avatar Update
 */
// function handleAvatar(PDO $pdo, int $id, array $file): ?string {
//     if (empty($file['name'])) return null;
//     $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
//     if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) return "Format invalide.";
//     if ($file['size'] > 2097152) return "Fichier trop lourd.";

//     if (!is_dir("membres/avatars/")) mkdir("membres/avatars/", 0777, true);
//     $filename = "{$id}.{$ext}";
    
//     if (move_uploaded_file($file['tmp_name'], "membres/avatars/$filename")) {
//         $stmt = $pdo->prepare("UPDATE membres SET avatar = :val WHERE id = :id");
//         $stmt->execute([':val' => $filename, ':id' => $id]);
//         return "success";
//     }
//     return "Erreur upload.";
// }
function handleAvatar(PDO $pdo, int $id, array $file): ?string {
    if (empty($file['name'])) return null;

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $path = "membres/avatars/";

    if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) return "Format invalide.";
    if ($file['size'] > 2097152) return "Fichier trop lourd.";

    if (!is_dir($path)) mkdir($path, 0777, true);
    $filename = "$id.$ext";

   if (move_uploaded_file($file['tmp_name'], $path . $filename)) {
        $pdo->prepare("UPDATE membres SET avatar = :v WHERE id = :id")
            ->execute(['v' => $filename, 'id' => $id]);
        return "success";
    }

    return "Erreur upload.";
}


// Initial fetch
$requser = $pdo->prepare("SELECT * FROM membres WHERE id = :id");
$requser->execute([':id' => $_SESSION['id']]);
$user = $requser->fetch();

$erreur = null;
$msg = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid = (int)$_SESSION['id'];

    $resPseudo = handlePseudo($pdo, $uid, strip_tags($_POST['newpseudo'] ?? ''), $user['pseudo']);
    $resMail = handleEmail($pdo, $uid, filter_input(INPUT_POST, 'newmail', FILTER_SANITIZE_EMAIL), $user['mail']);
    $resPass = handlePassword($pdo, $uid, $_POST['newmdp1'] ?? '', $_POST['newmdp2'] ?? '');
    $resAvatar = handleAvatar($pdo, $uid, $_FILES['avatar'] ?? []);

    // Collect first error if any
    $erreur = match(true) {
        is_string($resPseudo) && $resPseudo !== "success" => $resPseudo,
        is_string($resMail) && $resMail !== "success" => $resMail,
        is_string($resPass) && $resPass !== "success" => $resPass,
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

    // Refresh user data
    $requser->execute([':id' => $uid]);
    $user = $requser->fetch();
}

include 'header.php';
?>

<div class="dashboard-grid">
    <aside class="sidebar">
        <ul class="sidebar-nav">
            <li><a href="profil.php?id=<?= $_SESSION['id'] ?>">Tableau de bord</a></li>
            <li><a href="#" class="active">Paramètres du profil</a></li>
            <li><a href="deconnexion.php" style="color: var(--error-red);">Déconnexion</a></li>
        </ul>
    </aside>

    <main class="main-content">
        <div class="card" style="margin: 0; max-width: none;">
            <h2 style="margin-bottom: 2rem;">Édition du profil</h2>

            <?php if ($erreur): ?>
                <div class="alert alert-error"><?= $erreur ?></div>
            <?php endif; ?>

            <?php if ($msg): ?>
                <div class="alert alert-success"><?= $msg ?></div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div class="form-group">
                        <label>Pseudo</label>
                        <input type="text" name="newpseudo" placeholder="Pseudo" value="<?= htmlspecialchars($user['pseudo']); ?>" />
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="newmail" placeholder="Mail" value="<?= htmlspecialchars($user['mail']); ?>" />
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
                            <img src="membres/avatars/<?= htmlspecialchars($user['avatar']) ?>" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                        <?php endif; ?>
                        <input type="file" name="avatar" style="flex: 1;" />
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