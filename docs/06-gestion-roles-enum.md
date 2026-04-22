# 06 — Gestion des Rôles avec les Enums PHP 8.1 (Role.php)

## Qu'est-ce qu'un Enum ?

Un Enum (abréviation de "Enumeration") est une façon de définir une **liste fixe de valeurs possibles**.

Sans Enum, vous pourriez écrire `'admin'`, `'Admin'`, `'ADMIN'` ou `'Administrateur'` par erreur. L'Enum garantit que la valeur est toujours correcte.

---

## Avant les Enums (l'ancienne façon)

```php
// ❌ Risqué : des constantes simples
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// On peut toujours se tromper en tapant :
$role = 'Admine'; // Faute de frappe, PHP ne signale rien
```

---

## Avec un Enum PHP 8.1 (notre approche)

```php
<?php

declare(strict_types=1);

/**
 * Énumération des rôles utilisateurs de l'application
 * Permet une gestion typée et sécurisée des permissions.
 */
enum Role: string
{
    case ADMIN = 'admin';
    case USER = 'user';

    /**
     * Retourne le libellé lisible du rôle (ex: "Administrateur")
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Administrateur',
            self::USER => 'Utilisateur',
        };
    }

    /**
     * Vérifie si le rôle actuel est celui d'un administrateur
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
```

---

## Explication du code

### `enum Role: string`
- `Role` est le nom de l'Enum
- `: string` signifie que chaque cas est associé à une chaîne de caractères

### `case ADMIN = 'admin'`
- `ADMIN` est le nom interne PHP (en majuscules par convention)
- `'admin'` est la valeur stockée en base de données

### La méthode `label()`
Transforme la valeur technique en libellé lisible :
```php
Role::ADMIN->label(); // Retourne "Administrateur"
Role::USER->label();  // Retourne "Utilisateur"
```

### La méthode `isAdmin()`
```php
Role::ADMIN->isAdmin(); // Retourne true
Role::USER->isAdmin();  // Retourne false
```

---

## Comment l'utiliser dans le projet

### Protéger une page admin

```php
<?php
require_once 'Role.php';

// Bloquer l'accès si pas admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== Role::ADMIN->value) {
    header("Location: connexion.php");
    exit();
}
```

> `Role::ADMIN->value` retourne la chaîne `'admin'` qu'on compare à la session.

### Convertir depuis la base de données

```php
// La BDD retourne la chaîne 'admin' ou 'user'
$role = Role::from($user['role']); // Convertit en objet Enum

if ($role->isAdmin()) {
    echo "Bienvenue, administrateur !";
}

echo $role->label(); // Affiche "Administrateur" ou "Utilisateur"
```

### Dans les templates HTML (admin.php)

```php
<?php foreach ($users as $u): ?>
    <span>
        <?= Role::from($u['role'])->label() ?>
    </span>
<?php endforeach; ?>
```

---

## La valeur stockée en BDD vs la valeur PHP

| Ce qu'on écrit en PHP | Ce qui est en BDD |
|---|---|
| `Role::ADMIN` | `'admin'` |
| `Role::USER` | `'user'` |
| `Role::ADMIN->value` | `'admin'` (chaîne) |

---

## Dans le header.php : afficher le menu Admin

```php
<?php if($_SESSION['role'] === 'admin'): ?>
    <li>
        <a href="admin.php">Admin</a>
    </li>
<?php endif; ?>
```

On compare directement avec la chaîne `'admin'` pour rester simple dans les vues.

---

## Promouvoir un utilisateur en Admin

Pour l'instant, on le fait manuellement en SQL. En pratique, un admin devrait pouvoir le faire depuis l'interface, mais c'est une fonctionnalité future.

```sql
UPDATE membres SET role = 'admin' WHERE id = 5;
```

---

> 🎓 **Pour les juniors** : L'Enum garantit que votre code n'acceptera jamais une valeur de rôle inconnue. Si quelqu'un tente `Role::from('superadmin')`, PHP lancera une exception.
