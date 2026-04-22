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
            <svg width="32" height="32" viewBox="0 0 256 221" version="1.1" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid">
                <path d="M204.8,0 L256,0 L128,220.8 L0,0 L51.2,0 L128,132.48 L204.8,0 Z" fill="#41B883"></path>
                <path d="M0,0 L128,220.8 L256,0 L204.8,0 L128,132.48 L51.2,0 L0,0 Z" fill="#41B883"></path>
                <path d="M51.2,0 L128,132.48 L204.8,0 L153.6,0 L128,44.16 L102.4,0 L51.2,0 Z" fill="#35495E"></path>
            </svg>
            <span style="letter-spacing: -0.5px;">RostoDev</span>
        </a>
        
        <ul class="nav-links">
            <li><a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active-link' : '' ?>">Accueil</a></li>
            <?php if(isset($_SESSION['id'])): ?>
                <li><a href="profil.php?id=<?= $_SESSION['id'] ?>" class="<?= basename($_SERVER['PHP_SELF']) == 'profil.php' ? 'active-link' : '' ?>">Mon Profil</a></li>
                <?php if($_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admin.php' ? 'active-link' : '' ?>" style="color: var(--vue-green); font-weight: 600;">Admin</a></li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>

        <div class="nav-auth">
            <?php if(isset($_SESSION['id'])): ?>
                <a href="deconnexion.php" class="btn btn-outline" style="border-color: #cbd5e1; color: #64748b;">Déconnexion</a>
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
