---
source_course: "php-security"
source_lesson: "php-security-password-security"
---

# Hachage & Stockage des Mots de Passe

Ne jamais stocker les mots de passe en clair. **Utilisez un hachage cryptographique approprié**.

## La Fonction password_hash()

```php
<?php
// Créer un hash
$password = 'mot_de_passe_utilisateur';
$hash = password_hash($password, PASSWORD_DEFAULT);
// Sortie : $2y$10$... (60+ caractères)

// PASSWORD_DEFAULT utilise bcrypt (actuellement)
// Génère automatiquement un sel sécurisé
// Épreuve du temps : l'algorithme peut changer
```

## Vérifier les Mots de Passe

```php
<?php
$submitted = $_POST['password'];
$storedHash = $user['password_hash'];  // Depuis la base de données

if (password_verify($submitted, $storedHash)) {
    // Le mot de passe est correct
    login($user);
} else {
    // Le mot de passe est incorrect
    // Ne pas révéler quel champ était incorrect !
    throw new AuthenticationException('Identifiants invalides');
}
```

## Options d'Algorithme

```php
<?php
// Bcrypt (par défaut, recommandé pour la plupart des cas)
$hash = password_hash($password, PASSWORD_BCRYPT, [
    'cost' => 12  // Plus élevé = plus lent = plus sécurisé
]);

// Argon2id (PHP 7.3+, meilleur pour les nouveaux projets)
$hash = password_hash($password, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,  // 64 Mo
    'time_cost' => 4,        // 4 itérations
    'threads' => 3           // 3 threads
]);
```

## Re-hachage Quand Nécessaire

```php
<?php
function login(string $password, string $hash): bool {
    if (!password_verify($password, $hash)) {
        return false;
    }

    // Vérifier si le hash doit être mis à niveau
    if (password_needs_rehash($hash, PASSWORD_DEFAULT)) {
        $newHash = password_hash($password, PASSWORD_DEFAULT);
        updateUserPasswordHash($user->id, $newHash);
    }

    return true;
}
```

## Erreurs Courantes

```php
<?php
// MAUVAIS : Stockage en clair
$user->password = $password;

// MAUVAIS : Hachage simple (crackable)
$hash = md5($password);
$hash = sha1($password);
$hash = hash('sha256', $password);

// MAUVAIS : Hash sans sel (tables arc-en-ciel)
$hash = hash('sha256', $password);

// MAUVAIS : Sel statique (si divulgué, tous les mots de passe sont vulnérables)
$hash = hash('sha256', 'sel_statique' . $password);

// BON : password_hash() avec un sel unique par mot de passe
$hash = password_hash($password, PASSWORD_DEFAULT);
```

## Attaques de Timing

```php
<?php
// MAUVAIS : Retour précoce révèle des informations
if (strlen($password) < 8) {
    return false;  // Réponse rapide = mot de passe trop court
}

// MAUVAIS : Comparaison de chaînes avec timing variable
if ($hash === $expected) {  // Le timing varie selon la position
    return true;
}

// BON : Comparaison en temps constant
if (hash_equals($expected, $hash)) {
    return true;
}
```

## Exemple Complet

**Service de mot de passe avec validation de robustesse**

```php
<?php
declare(strict_types=1);

class PasswordService {
    private const ALGORITHM = PASSWORD_ARGON2ID;
    private const OPTIONS = [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ];

    public function hash(string $password): string {
        $this->validateStrength($password);
        return password_hash($password, self::ALGORITHM, self::OPTIONS);
    }

    public function verify(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, self::ALGORITHM, self::OPTIONS);
    }

    public function validateStrength(string $password): void {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir une lettre majuscule';
        }

        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir une lettre minuscule';
        }

        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir un chiffre';
        }

        if ($errors) {
            throw new WeakPasswordException(implode('. ', $errors));
        }
    }
}

// Inscription
$passwordService = new PasswordService();
$hash = $passwordService->hash($_POST['password']);
$stmt->execute(['password_hash' => $hash]);

// Connexion
if ($passwordService->verify($_POST['password'], $user['password_hash'])) {
    if ($passwordService->needsRehash($user['password_hash'])) {
        $newHash = $passwordService->hash($_POST['password']);
        // Mettre à jour en base de données
    }
    // Connexion réussie
}
?>
```

## Les Grimoires

- [Hachage des Mots de Passe (Documentation Officielle)](https://www.php.net/manual/en/function.password-hash.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
