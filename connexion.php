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

    // Set session variables
    $_SESSION['id'] = $userinfo['id'];
    $_SESSION['pseudo'] = $userinfo['pseudo'];
    $_SESSION['mail'] = $userinfo['mail'];
    $_SESSION['role'] = $userinfo['role'];

    return "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailconnect = filter_input(INPUT_POST, 'mailconnect', FILTER_SANITIZE_EMAIL) ?? '';
    $mdpconnect = $_POST['mdpconnect'] ?? '';

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
            Pas encore de compte ? <a href="index.php" style="color: var(--vue-green); text-decoration: none;">S'inscrire</a>
        </p>
    </form>
</div>

<?php include 'footer.php'; ?>