# 14 — Le Dashboard Administrateur (admin.php)

## L'objectif de la page Admin

Cette page est réservée aux utilisateurs ayant le rôle `'admin'`. Elle permet de visualiser l'ensemble des membres inscrits sur la plateforme. C'est le centre de contrôle du projet.

---

## Sécurisation de l'accès

C'est l'étape la plus importante : on ne doit JAMAIS laisser un utilisateur normal accéder à cette page.

```php
<?php
declare(strict_types=1);
session_start();
require_once 'db.php';
require_once 'Role.php';

// 1. Vérifier si l'utilisateur est connecté
// 2. Vérifier si son rôle correspond à Role::ADMIN
if (!isset($_SESSION['id']) || $_SESSION['role'] !== Role::ADMIN->value) {
    header("Location: connexion.php");
    exit();
}
```

---

## Récupération des données

Contrairement au profil, ici on veut **tous** les membres, triés du plus récent au plus ancien.

```php
// Récupérer tous les membres
$stmt = $pdo->query("SELECT * FROM membres ORDER BY created_at DESC");
$users = $stmt->fetchAll();
```

---

## Affichage sous forme de tableau

Le dashboard utilise un tableau HTML pour une lecture claire des données.

```php
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
                        <th style="padding: 1rem;">Inscription</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr style="border-bottom: 1px solid #f8fafc;">
                            <td style="padding: 1rem;">
                                <?php if ($user['avatar']): ?>
                                    <img src="membres/avatars/<?= htmlspecialchars($user['avatar']) ?>" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                <?php else: ?>
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #64748b;">
                                        <?= strtoupper(substr($user['pseudo'], 0, 1)) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 1rem; font-weight: 500;"><?= htmlspecialchars($user['pseudo']) ?></td>
                            <td style="padding: 1rem; color: #64748b;"><?= htmlspecialchars($user['mail']) ?></td>
                            <td style="padding: 1rem;">
                                <span style="padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; background: <?= $user['role'] === 'admin' ? '#f0fdf4' : '#f1f5f9' ?>; color: <?= $user['role'] === 'admin' ? '#16a34a' : '#64748b' ?>; border: 1px solid <?= $user['role'] === 'admin' ? '#bbf7d0' : '#e2e8f0' ?>;">
                                    <?= Role::from($user['role'])->label() ?>
                                </span>
                            </td>
                            <td style="padding: 1rem; color: #94a3b8; font-size: 0.85rem;">
                                <?= date('d/m/Y', strtotime($user['created_at'])) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</div>

<?php include 'footer.php'; ?>
```

---

## Concepts Clés pour les Juniors

### 1. Sécurité par le rôle
On vérifie non seulement que l'utilisateur est connecté, mais aussi que son rôle stocké en session est bien `'admin'`. C'est le principe du **RBAC** (Role-Based Access Control).

### 2. Le formatage de date
```php
date('d/m/Y', strtotime($user['created_at']))
```
- `strtotime` : Transforme la chaîne de caractères SQL (`2026-04-22 08:15:00`) en un timestamp PHP.
- `date('d/m/Y', ...)` : Affiche la date au format français (Jour/Mois/Année).

### 3. Affichage conditionnel (Badges)
On utilise une condition ternaire ou un `if` pour changer la couleur du badge selon que l'utilisateur est un admin ou un simple utilisateur. Cela permet une lecture rapide des informations importantes.

---

> 🛠️ **Prochaine étape** : Vous pourriez ajouter des boutons de suppression ou de modification de rôle dans ce tableau pour rendre le dashboard vraiment interactif.
