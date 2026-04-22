# 15 — La Page d'Accueil et Présentation (index.php)

## Pourquoi une page d'accueil séparée ?

Dans notre projet, l'accueil (`index.php`) ne sert plus seulement à s'inscrire. C'est la vitrine du projet. Elle présente les objectifs, les technologies utilisées et propose des actions intelligentes selon que l'utilisateur est connecté ou non.

---

## Logique de redirection et affichage

Si l'utilisateur est déjà connecté, on lui propose d'accéder à son profil ou de se déconnecter. S'il est visiteur, on lui propose de se connecter ou de s'inscrire.

```php
<?php
declare(strict_types=1);
session_start();

include 'header.php';
?>

<div class="card card-full">
    <?php if (isset($_SESSION['id'])): ?>
        <!-- Section pour les utilisateurs connectés -->
        <div style="display: flex; align-items: center; justify-content: space-between; ...">
            <div>
                <h2>Bon retour, <?= htmlspecialchars($_SESSION['pseudo']) ?> !</h2>
                <p>Vous êtes connecté à votre espace personnel.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="profil.php?id=<?= $_SESSION['id'] ?>" class="btn btn-primary">Mon Profil</a>
                <a href="deconnexion.php" class="btn btn-outline">Déconnecter</a>
            </div>
        </div>
    <?php else: ?>
        <!-- Section pour les visiteurs -->
        <div style="display: flex; align-items: center; justify-content: space-between; ...">
            <div>
                <h1>Espace Membre — PHP 8.4</h1>
                <p>Modernisation d'un espace membre en PHP procédural moderne.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="connexion.php" class="btn btn-primary">Connexion</a>
                <a href="inscription.php" class="btn btn-outline">Inscription</a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Présentation du Projet (Visible par tous) -->
    <section style="margin-top: 3rem;">
        <h3>Suivi des Tâches du TP (Milestones)</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.25rem;">
            <!-- Exemple de bloc : Architecture -->
            <div class="milestone-card">
                <h4>🏗️ Architecture & Backend</h4>
                <ul>
                    <li>Migration PHP 8.4</li>
                    <li>Sécurité SQL (Paramètres nommés)</li>
                    <li>Gestion des rôles (Enums)</li>
                </ul>
            </div>
            
            <!-- Autres blocs... -->
        </div>
    </section>
</div>
```

---

## Concepts Clés

### 1. La classe `.card-full`
Nous avons créé une classe CSS spéciale pour que l'accueil occupe toute la largeur disponible, contrairement aux formulaires de connexion qui sont étroits et centrés.

### 2. Le message de bienvenue
C'est un élément de **User Experience (UX)**. Plutôt que de montrer un formulaire d'inscription à quelqu'un qui a déjà un compte, on lui souhaite la bienvenue.

### 3. La structure "Grille" (CSS Grid)
```css
display: grid;
grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
```
C'est la méthode moderne pour créer des mises en page responsives. Le navigateur calcule automatiquement le nombre de colonnes selon la largeur de l'écran.

---

## Pourquoi est-ce important pour un junior ?

L'`index.php` est souvent la première page qu'un client ou un recruteur voit. Elle doit être claire :
- Quel est le but du site ?
- Comment je m'inscris ?
- Qu'est-ce qui a été accompli techniquement ?

C'est aussi l'endroit idéal pour montrer votre rigueur en expliquant votre méthodologie (comme le suivi des tâches).

---

> 💡 **Astuce** : Remarquez que l'`index.php` n'a presque pas de logique de traitement de données (pas de BDD, pas de POST). C'est une page de "VUE" pure qui délègue les actions aux autres fichiers.
