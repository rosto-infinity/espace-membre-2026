---
source_course: "php-security"
source_lesson: "php-security-session-security"
---

# Gestion Sécurisée des Sessions

Les sessions maintiennent l'état utilisateur entre les requêtes. Une **mauvaise gestion des sessions mène au détournement de comptes**.

## Configuration Sécurisée des Sessions

```php
<?php
// Avant session_start()
ini_set('session.cookie_httponly', '1');    // Pas d'accès JS
ini_set('session.cookie_secure', '1');       // HTTPS seulement
ini_set('session.cookie_samesite', 'Strict'); // Pas de cross-site
ini_set('session.use_strict_mode', '1');     // Rejeter les IDs inconnus
ini_set('session.use_only_cookies', '1');    // Pas de sessions URL

session_start();
```

## Prévention de la Fixation de Session

```php
<?php
// Régénérer l'ID après un changement de privilèges
function login(User $user): void {
    // Régénérer l'ID de session pour prévenir la fixation
    session_regenerate_id(true);  // true = supprimer l'ancienne session

    $_SESSION['user_id'] = $user->id;
    $_SESSION['logged_in_at'] = time();
    $_SESSION['ip'] = $_SERVER['REMOTE_ADDR'];
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
}
```

## Validation de Session

```php
<?php
function validateSession(): bool {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    // Vérifier les indicateurs de détournement de session
    if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
        // IP changée - suspect, mais peut être légitime
        // Logger et éventuellement invalider
    }

    if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
        // User agent changé - probablement un détournement
        session_destroy();
        return false;
    }

    // Vérifier l'âge de la session
    $maxAge = 3600; // 1 heure
    if (time() - $_SESSION['logged_in_at'] > $maxAge) {
        session_destroy();
        return false;
    }

    return true;
}
```

## Déconnexion Sécurisée

```php
<?php
function logout(): void {
    // Vider les données de session
    $_SESSION = [];

    // Supprimer le cookie de session
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    // Détruire la session
    session_destroy();
}
```

## Tokens "Se Souvenir de Moi"

```php
<?php
function createRememberToken(int $userId): string {
    $selector = bin2hex(random_bytes(16));
    $validator = bin2hex(random_bytes(32));
    $hashedValidator = hash('sha256', $validator);

    // Stocker le sélecteur + validateur hashé en base de données
    $stmt = $pdo->prepare(
        'INSERT INTO remember_tokens (user_id, selector, hashed_validator, expires_at)
         VALUES (:user_id, :selector, :hashed_validator, :expires_at)'
    );
    $stmt->execute([
        'user_id' => $userId,
        'selector' => $selector,
        'hashed_validator' => $hashedValidator,
        'expires_at' => date('Y-m-d H:i:s', time() + 86400 * 30),
    ]);

    // Retourner selector:validator à définir dans le cookie
    return $selector . ':' . $validator;
}
```

## Les Grimoires

- [Sécurité des Sessions (Documentation Officielle)](https://www.php.net/manual/en/session.security.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
