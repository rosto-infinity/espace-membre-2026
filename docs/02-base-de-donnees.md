# 02 — La Base de Données (membres.sql)

## Pourquoi créer la base de données en premier ?

Avant d'écrire une seule ligne de PHP, on doit savoir **où on va stocker les données**. La base de données est le fondement de toute application web.

---

## Créer la base de données

Ouvrez phpMyAdmin (ou votre outil MySQL préféré) et créez une base de données nommée :

```
espace_membre_2026
```

---

## La table `membres`

Nous n'avons besoin que d'**une seule table** pour tout ce projet. Elle contient toutes les informations des utilisateurs.

```sql
CREATE TABLE `membres` (
  `id`         int          NOT NULL AUTO_INCREMENT,
  `pseudo`     varchar(255) NOT NULL,
  `mail`       varchar(255) NOT NULL,
  `motdepasse` varchar(255) NOT NULL,
  `role`       varchar(20)  NOT NULL DEFAULT 'user',
  `avatar`     varchar(255)          DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## Explication colonne par colonne

| Colonne | Type | Rôle | Point important |
|---|---|---|---|
| `id` | `int AUTO_INCREMENT` | Identifiant unique | Généré automatiquement |
| `pseudo` | `varchar(255)` | Nom d'utilisateur | 255 caractères max |
| `mail` | `varchar(255)` | Adresse email | Doit être unique |
| `motdepasse` | `varchar(255)` | Mot de passe **hashé** | Jamais en clair ! |
| `role` | `varchar(20)` | Rôle de l'utilisateur | `'user'` par défaut |
| `avatar` | `varchar(255)` | Nom du fichier image | `NULL` si pas d'avatar |
| `created_at` | `timestamp` | Date d'inscription | Automatique |
| `updated_at` | `timestamp` | Date de modification | Automatique |

---

## Points clés pour les juniors

### ❗ `DEFAULT 'user'` pour le rôle
Tous les nouveaux inscrits sont automatiquement des utilisateurs normaux. Pour promouvoir quelqu'un en admin, il faut modifier la BDD manuellement :

```sql
UPDATE membres SET role = 'admin' WHERE pseudo = 'votre_pseudo';
```

### ❗ `DEFAULT NULL` pour avatar
Le champ `avatar` accepte `NULL`. C'est obligatoire car au moment de l'inscription, l'utilisateur n'a pas encore de photo de profil.

> **Erreur fréquente** : Si vous mettez `avatar NOT NULL` sans valeur par défaut, PHP retournera une erreur fatale lors de l'inscription. On l'a vécu dans ce projet !

### ❗ Le mot de passe ne sera JAMAIS stocké en clair
PHP va le transformer en une chaîne hashée ressemblant à :
```
$2y$12$dhm3EJAWwY6GTwuY7Um/hOlR5WXlXwu60IIRo9RR0NKOP/1Q9JLjK
```

---

## Le fichier SQL complet (membres.sql)

```sql
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE TABLE `membres` (
  `id`         int          NOT NULL,
  `pseudo`     varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mail`       varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `motdepasse` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role`       varchar(20)  COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `avatar`     varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `membres`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `membres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

COMMIT;
```

---

> 📂 Importez ce fichier directement dans phpMyAdmin via l'onglet "Importer" pour créer la structure automatiquement.
