<?php

declare(strict_types=1);
session_start();
require_once 'db.php';
require_once 'Role.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== Role::ADMIN->value) {
    header("Location: connexion.php");
    exit();
}

// Fetch all users
$stmt = $pdo->query("SELECT * FROM membres ORDER BY created_at DESC");
$users = $stmt->fetchAll();

include 'header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr;">
    <main class="main-content">
        <div class="profile-header">
            <div class="user-meta">
                <h2>Dashboard Administrateur</h2>
                <p>Gestion des membres de l'espace.</p>
            </div>
            <div style="margin-left: auto;">
                <span class="btn btn-outline" style="cursor: default; border-color: var(--vue-green); color: var(--vue-green);">
                    Total: <?= count($users) ?> membres
                </span>
            </div>
        </div>

        <div class="card" style="margin: 2rem 0; max-width: none; overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse; min-width: 600px;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 1rem;">Avatar</th>
                        <th style="padding: 1rem;">Pseudo</th>
                        <th style="padding: 1rem;">Email</th>
                        <th style="padding: 1rem;">Rôle</th>
                        <th style="padding: 1rem;">Date d'inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                            <td style="padding: 1rem;">
                                <?php if (!empty($u['avatar'])): ?>
                                    <img src="membres/avatars/<?= htmlspecialchars($u['avatar']) ?>" style="width: 35px; height: 35px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 35px; height: 35px; border-radius: 50%; background: var(--vue-green-dark); display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: var(--vue-green);">
                                        <?= strtoupper(substr($u['pseudo'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-weight: 500;"><?= htmlspecialchars($u['pseudo']) ?></td>
                            <td style="padding: 1rem; color: #64748b;"><?= htmlspecialchars($u['mail']) ?></td>
                            <td style="padding: 1rem;">
                                <span style="padding: 0.25rem 0.75rem; border-radius: 999px; font-size: 0.75rem; font-weight: 600; 
                                    <?= $u['role'] === 'admin' ? 'background: #fef3c7; color: #92400e;' : 'background: #f1f5f9; color: #475569;' ?>">
                                    <?= Role::from($u['role'])->label() ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; color: #94a3b8; font-size: 0.85rem;">
                                <?= date('d/m/Y H:i', strtotime($u['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
