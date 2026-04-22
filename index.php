<?php
declare(strict_types=1);
session_start();

include 'header.php';
?>

<div class="card card-full">
    <?php if (isset($_SESSION['id'])): ?>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2 style="margin: 0;">Bon retour, <?= htmlspecialchars($_SESSION['pseudo']) ?> !</h2>
                <p style="color: #64748b; margin: 0.5rem 0 0 0;">Vous êtes connecté à votre espace personnel.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="profil.php?id=<?= $_SESSION['id'] ?>" class="btn btn-primary">Mon Profil</a>
                <a href="deconnexion.php" class="btn btn-outline" style="border-color: #cbd5e1; color: #64748b;">Déconnecter</a>
            </div>
        </div>
    <?php else: ?>
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 2rem; border-bottom: 1px solid #f1f5f9; padding-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="margin: 0; font-size: 1.4rem;">Espace Membre — PHP 8.4</h1>
                <p style="color: #64748b; margin: 0.5rem 0 0 0;">Modernisation d'un espace membre en PHP procédural moderne.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="connexion.php" class="btn btn-primary">Connexion</a>
                <a href="inscription.php" class="btn btn-outline">Inscription</a>
            </div>
        </div>
    <?php endif; ?>

    <section>
        <h3 style="margin-bottom: 1.5rem; font-size: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <span style="background: var(--vue-green); color: white; width: 22px; height: 22px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; font-size: 0.75rem; flex-shrink: 0;">✓</span>
            Suivi des Tâches du TP (Milestones)
        </h3>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">

            <!-- Backend -->
            <div style="padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;">
                <h4 style="color: var(--vue-green-dark); margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 600;">🏗️ Architecture &amp; Backend</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.83rem; color: #475569;">
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Migration PHP 8.4 (<code>strict_types</code>, types de retour)</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Refactorisation fonctionnelle modulaire</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Sécurité SQL — paramètres nommés PDO (<code>:nom</code>)</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Gestion des rôles via <code>enum Role</code> PHP 8.1</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Fonctions dédiées : validation pseudo, email, mot de passe</li>
                </ul>
            </div>

            <!-- Design -->
            <div style="padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;">
                <h4 style="color: var(--vue-green-dark); margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 600;">🎨 Design &amp; UI (Vue.js Inspired)</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.83rem; color: #475569;">
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Design system — variables CSS palette verte/sombre</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Dashboard responsive avec sidebar latérale</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Composants premium : boutons, cartes, avatars circulaires</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green); flex-shrink: 0; margin-top: 2px;">●</span> Navigation dynamique — liens actifs &amp; menus conditionnels</li>
                </ul>
            </div>

            <!-- Situation Problème & TP -->
            <div style="padding: 1.25rem; border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; grid-column: span 1; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <h4 style="color: var(--vue-green-dark); margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">🏢 Situation Problème</h4>
                <p style="font-size: 0.83rem; color: #475569; line-height: 1.5; margin-bottom: 1.5rem;">
                    Une entreprise doit migrer son ancien portail client (PHP 5.6) vers une architecture moderne **PHP 8.4**. L'objectif est de supprimer la dette technique, d'assurer une sécurité maximale contre les injections SQL et d'offrir une expérience utilisateur fluide type "SPA" avec un design épuré.
                </p>
                
                <h4 style="color: var(--vue-green-dark); margin: 0 0 1rem 0; font-size: 0.9rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">🎓 Objectifs du TP</h4>
                <ul style="list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 0.6rem; font-size: 0.83rem; color: #475569;">
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green);">●</span> Inscription optimisée sans friction (UX focus)</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green);">●</span> Gestion CRUD complète du profil utilisateur</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green);">●</span> Administration centralisée des membres</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green);">●</span> Notifications Flash (Sessions) pour le feedback</li>
                    <li style="display: flex; gap: 0.5rem; align-items: flex-start;"><span style="color: var(--vue-green);">●</span> Sécurisation avancée via PDO et Typage Strict</li>
                </ul>
            </div>

        </div>

        <!-- Backlog -->
        <div style="margin-top: 1.5rem; padding: 1rem 1.25rem; border-radius: 8px; background: #f1f5f9; display: flex; flex-wrap: wrap; align-items: center; gap: 0.75rem;">
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600;">🚀 Prochaines étapes :</span>
            <span style="padding: 3px 10px; background: white; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 0.75rem; color: #64748b;">Middleware Sécurité</span>
            <span style="padding: 3px 10px; background: white; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 0.75rem; color: #64748b;">Mode Sombre</span>
            <span style="padding: 3px 10px; background: white; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 0.75rem; color: #64748b;">Filtres Admin</span>
            <span style="padding: 3px 10px; background: white; border: 1px solid #e2e8f0; border-radius: 999px; font-size: 0.75rem; color: #64748b;">Suppression de Compte</span>
        </div>
    </section>
</div>

<?php include 'footer.php'; ?>
