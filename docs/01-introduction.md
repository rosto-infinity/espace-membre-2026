# 01 — Introduction & Situation Problème

## Bienvenue dans ce cours

Ce cours vous guidera pas à pas dans la création d'un **Espace Membre** complet en PHP 8.4.

---

## La Situation Problème

Imaginez que vous êtes développeur dans une petite entreprise. Le responsable technique vous dit :

> "Notre site est sur PHP 5.6, le code est un désordre total. On a des failles de sécurité SQL partout, pas de gestion de rôles, et l'expérience utilisateur est horrible. Il faut tout refaire proprement."

Votre mission est donc de créer un **Espace Membre moderne** qui permet de :

1. S'inscrire et se connecter
2. Consulter et modifier son profil
3. Gérer un avatar (photo de profil)
4. Avoir un système de rôles : **Admin** et **Utilisateur**
5. L'administrateur peut voir **tous les membres** dans un dashboard

---

## Ce que vous allez apprendre

À la fin de ce cours, vous saurez :

- Connecter PHP à une base de données **MySQL avec PDO** (sécurisé)
- Utiliser les **requêtes préparées avec paramètres nommés** (`:nom`)
- Gérer les **sessions PHP** (connexion, déconnexion)
- Hasher les mots de passe avec `password_hash()`
- Créer des **fonctions PHP modulaires** (pas de répétition de code)
- Implémenter un système de **rôles** avec les Enums PHP 8.1
- Afficher des **messages flash** (succès/erreur) après une redirection

---

## Stack technique

| Outil | Rôle |
|---|---|
| PHP 8.4 | Langage côté serveur |
| MySQL 8 | Base de données |
| PDO | Extension PHP pour accéder à la BDD |
| Sessions PHP | Stocker l'état de connexion |
| HTML/CSS | Interface utilisateur |

---

## Structure des fichiers que nous allons créer

```
espace-membre/
│
├── db.php            ← Connexion à la base de données
├── flash.php         ← Système de notifications (sessions)
├── Role.php          ← Enum des rôles (Admin/User)
├── header.php        ← En-tête HTML partagé
├── footer.php        ← Pied de page HTML partagé
│
├── index.php         ← Page d'accueil (présentation du projet)
├── inscription.php   ← Formulaire d'inscription
├── connexion.php     ← Formulaire de connexion
├── profil.php        ← Dashboard de l'utilisateur
├── editionprofil.php ← Modification du profil
├── admin.php         ← Dashboard Administrateur
├── deconnexion.php   ← Déconnexion
│
├── membres.sql       ← Structure de la base de données
└── index.css         ← Styles CSS
```

---

## Prérequis

- Avoir PHP 8.1+ installé (idéalement PHP 8.4)
- Avoir MySQL ou MariaDB installé
- Avoir un serveur local (MAMP, XAMPP, Laragon ou Valet)
- Connaître les bases du HTML et du PHP (variables, conditions, boucles)

---

> 💡 **Conseil pour les juniors** : Ne sautez pas les étapes. Lisez chaque fichier en entier avant de copier le code. Comprendre vaut mieux que copier.
