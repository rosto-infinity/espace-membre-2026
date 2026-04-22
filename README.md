# 🚀 Espace Membre PHP 8.4 — Modernisation & TP

Ce projet est une démonstration complète de la création d'un **Espace Membre moderne** en utilisant les standards de **PHP 8.4**. Il sert de support pédagogique (TP) pour apprendre à migrer du code "legacy" vers une architecture propre, sécurisée et performante.

---

## 🏢 Situation Problème
Une entreprise souhaite migrer son ancien portail client (PHP 5.6) vers une architecture moderne **PHP 8.4**. L'objectif est de supprimer la dette technique, d'assurer une sécurité maximale contre les injections SQL et d'offrir une expérience utilisateur fluide type "SPA" avec un design épuré.

## 🎓 Objectifs du TP
- **Inscription optimisée** sans friction (UX focus).
- **Gestion CRUD complète** du profil utilisateur (Avatar, Email, Pseudo, Mot de passe).
- **Administration centralisée** des membres pour la gestion d'équipe.
- **Feedback Utilisateur** via un système de notifications Flash (Sessions).
- **Sécurisation avancée** via PDO, Paramètres nommés et Typage Strict.

---

## 🏗️ Architecture & Backend
- **Migration PHP 8.4** : Utilisation du `strict_types=1`, des types de retour et du `match`.
- **Sécurité SQL** : Utilisation systématique des paramètres nommés PDO (`:id`) pour prévenir toute injection.
- **Gestion des Rôles** : Système robuste basé sur l'Enum `Role` (PHP 8.1+).
- **Fonctions Modulaires** : Refactorisation de la logique métier dans des fonctions assistants réutilisables.

## 🎨 Design & UI (Vue.js Inspired)
- **Design System** : Palette de couleurs Vue.js (`#42b883`) avec variables CSS.
- **Dashboard Responsive** : Mise en page avec sidebar latérale et navigation dynamique.
- **Composants Premium** : Boutons stylisés, cartes (cards) avec ombres portées et avatars circulaires.

---

## 📚 Documentation Complète (Step-by-Step)

Nous avons rédigé une documentation complète de 20 chapitres pour vous guider dans la création du projet :

### 1. Fondations & Sécurité
- [01 — Introduction & Situation Problème](docs/01-introduction.md)
- [02 — La Base de Données (SQL)](docs/02-base-de-donnees.md)
- [03 — Connexion PDO](docs/03-connexion-pdo.md)
- [04 — Requêtes Préparées & Paramètres Nommés](docs/04-requetes-preparees.md)
- [05 — Système de Notifications Flash](docs/05-notifications-flash.md)
- [06 — Gestion des Rôles (Enum)](docs/06-gestion-roles-enum.md)

### 2. Interface & Navigation
- [07 — Header & Footer Partagés](docs/07-header-footer.md)
- [15 — Page d'Accueil & Présentation](docs/15-accueil-presentation.md)
- [17 — Design Système & UX](docs/17-design-system-ux.md)

### 3. Fonctionnalités Utilisateur
- [08 — Inscription](docs/08-inscription.md)
- [09 — Connexion & Sessions](docs/09-connexion-sessions.md)
- [10 — Déconnexion](docs/10-deconnexion.md)
- [11 — Dashboard Profil](docs/11-dashboard-profil.md)
- [12 — Édition du Profil (Partie 1)](docs/12-edition-profil-partie1.md)
- [13 — Édition du Profil (Partie 2 : Avatar)](docs/13-edition-profil-partie2.md)

### 4. Administration & Maintenance
- [14 — Dashboard Administrateur](docs/14-dashboard-admin.md)
- [16 — Sécurité : Les Bonnes Pratiques](docs/16-securite-bonnes-pratiques.md)
- [18 — Tests & Débogage](docs/18-tests-debogage.md)
- [19 — Mise en Ligne & Déploiement](docs/19-deploiement.md)
- [20 — Conclusion & Roadmap](docs/20-conclusion.md)

---

## 🚀 Installation Rapide

1. **Cloner le projet** ou copier les fichiers.
2. **Base de données** : Importer le fichier `membres.sql` dans votre serveur MySQL.
3. **Configuration** : Modifier les identifiants dans `db.php`.
4. **Lancer** : Accéder à `index.php` via votre serveur local (Apache/Nginx).

---
*Projet réalisé dans le cadre d'un TP de modernisation d'application web.*
