<?php

declare(strict_types=1);
session_start();
require 'db.php';

if (!isset($_GET['id'])) {
    header("Location: connexion.php");
    exit();
}

$getid = (int)$_GET['id'];
$requser = $pdo->prepare('SELECT * FROM membres WHERE id = :id');
$requser->execute([':id' => $getid]);
$userinfo = $requser->fetch();

if (!$userinfo) {
    die("Utilisateur introuvable.");
}

// Mettre à jour le rôle en session au cas où il a été modifié en BDD
if (isset($_SESSION['id']) && (int)$_SESSION['id'] === (int)$userinfo['id']) {
    $_SESSION['role'] = $userinfo['role'];
}

include 'header.php';
?>

<div class="dashboard-grid">
    <aside class="sidebar">
        <div class="text-center" style="margin-bottom: 2rem;">
            <?php if (!empty($userinfo['avatar'])): ?>
                <img src="membres/avatars/<?= htmlspecialchars($userinfo['avatar']) ?>" class="avatar-large" alt="Avatar">
            <?php else: ?>
                <div class="avatar-large" style="background: var(--vue-green-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2rem; color: var(--vue-green);">
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
                    <li><a href="admin.php" style="color: var(--vue-green); font-weight: 600;">Dashboard Admin</a></li>
                <?php endif; ?>
                <li><a href="editionprofil.php">Paramètres du profil</a></li>
                <li><a href="deconnexion.php" style="color: var(--error-red);">Déconnexion</a></li>
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

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem;">
            <div class="card" style="margin: 0; max-width: none; padding: 1.5rem; border-left: 4px solid var(--vue-green);">
                <h4 style="color: var(--vue-green); margin-bottom: 0.5rem;">Informations</h4>
                <p style="font-size: 0.9rem; color: #94a3b8;">Email: <?= htmlspecialchars($userinfo['mail']) ?></p>
                <p style="font-size: 0.9rem; color: #94a3b8;">ID Membre: #<?= $userinfo['id'] ?></p>
            </div>

            <div class="card" style="margin: 0; max-width: none; padding: 1.5rem; border-left: 4px solid var(--accent-green);">
                <h4 style="color: var(--accent-green); margin-bottom: 0.5rem;">Statut du compte</h4>
                <p style="font-size: 0.9rem; color: #94a3b8;">Type: Utilisateur Standard</p>
                <p style="font-size: 0.9rem; color: #94a3b8;">Vérifié: <span style="color: var(--vue-green);">Oui</span></p>
            </div>
        </div>

        <?php if (!isset($_SESSION['id']) || $userinfo['id'] !== (int)$_SESSION['id']): ?>
            <div class="alert alert-error mt-4">
                Vous consultez ce profil en tant que visiteur. Les options de modification sont masquées.
            </div>
        <?php endif; ?>
    </main>
</div>

<?php include 'footer.php'; ?>