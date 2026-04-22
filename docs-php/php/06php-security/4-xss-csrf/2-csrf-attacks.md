---
source_course: "php-security"
source_lesson: "php-security-csrf-attacks"
---

# Cross-Site Request Forgery (CSRF)

Le CSRF **trompe les utilisateurs pour qu'ils effectuent des actions non souhaitées**, en exploitant leur session authentifiée.

## Comment Fonctionne le CSRF

```html
<!-- Le site malveillant contient : -->
<img src="https://banque.com/transfert?vers=attaquant&montant=10000" />

<!-- Ou un formulaire caché qui s'auto-soumet : -->
<form action="https://banque.com/transfert" method="POST" id="csrf">
  <input type="hidden" name="vers" value="attaquant" />
  <input type="hidden" name="montant" value="10000" />
</form>
<script>
  document.getElementById("csrf").submit();
</script>
```

Si l'utilisateur est connecté à banque.com, la requête inclut automatiquement son cookie de session !

## Protection : Tokens CSRF

```php
<?php
session_start();

// Générer un token (une fois par session)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function getCsrfToken(): string {
    return $_SESSION['csrf_token'];
}

function validateCsrfToken(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
```

## Utiliser les Tokens CSRF dans les Formulaires

```php
<?php
// Dans le formulaire
?>
<form method="POST" action="/transfert">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCsrfToken()) ?>">
    <input type="text" name="montant">
    <button type="submit">Transférer</button>
</form>

<?php
// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($token)) {
        http_response_code(403);
        die('Token CSRF invalide');
    }

    // Traiter la requête valide
}
```

## Double Soumission de Cookie

```php
<?php
// Définir le token dans un cookie ET dans le formulaire
$token = bin2hex(random_bytes(32));
setcookie('csrf_token', $token, [
    'httponly' => false,  // JS doit pouvoir le lire
    'samesite' => 'Strict',
    'secure' => true,
]);

// Vérifier que les deux correspondent
function validateDoubleSubmit(string $formToken): bool {
    $cookieToken = $_COOKIE['csrf_token'] ?? '';
    return hash_equals($cookieToken, $formToken);
}
```

## Cookies SameSite

```php
<?php
// Défense moderne : cookies SameSite
setcookie('session_id', $sessionId, [
    'expires' => time() + 3600,
    'path' => '/',
    'secure' => true,
    'httponly' => true,
    'samesite' => 'Strict'  // Non envoyé lors des requêtes cross-site
]);

// Ou dans la config de session
ini_set('session.cookie_samesite', 'Strict');
```

## Exemple Complet

**Classe de protection CSRF complète**

```php
<?php
declare(strict_types=1);

// Classe de protection CSRF complète
class CsrfProtection {
    private const TOKEN_LENGTH = 32;
    private const TOKEN_NAME = 'csrf_token';

    public function __construct() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function getToken(): string {
        if (empty($_SESSION[self::TOKEN_NAME])) {
            $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::TOKEN_NAME];
    }

    public function getTokenField(): string {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            $token
        );
    }

    public function validate(?string $token = null): bool {
        $token ??= $_POST[self::TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $sessionToken = $_SESSION[self::TOKEN_NAME] ?? '';

        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    public function validateOrFail(): void {
        if (!$this->validate()) {
            http_response_code(403);
            throw new RuntimeException('Validation CSRF échouée');
        }
    }

    public function regenerate(): string {
        $_SESSION[self::TOKEN_NAME] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        return $_SESSION[self::TOKEN_NAME];
    }
}

// Utilisation
$csrf = new CsrfProtection();

// Dans le formulaire
echo '<form method="POST">';
echo $csrf->getTokenField();
echo '<button type="submit">Soumettre</button></form>';

// À la soumission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf->validateOrFail();
    // Traiter le formulaire...
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
