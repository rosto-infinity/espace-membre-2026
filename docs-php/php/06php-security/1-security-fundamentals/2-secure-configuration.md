---
source_course: "php-security"
source_lesson: "php-security-secure-configuration"
---

# Configuration PHP Sécurisée

Une configuration PHP correcte est **votre première ligne de défense**. De nombreuses attaques exploitent des serveurs mal configurés.

## Paramètres Critiques de php.ini

```ini
; Désactiver les fonctions dangereuses
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

; Limiter les opérations sur les fichiers
open_basedir = /var/www/html:/tmp

; Masquer la version PHP
expose_php = Off

; Gestion des erreurs (production)
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log
error_reporting = E_ALL

; Sécurité des sessions
session.cookie_httponly = On
session.cookie_secure = On
session.cookie_samesite = Strict
session.use_strict_mode = On
session.use_only_cookies = On

; Limites d'upload
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 5

; Limites de ressources
max_execution_time = 30
max_input_time = 60
memory_limit = 128M
post_max_size = 10M
```

## Configuration à l'Exécution

```php
<?php
// Définir à l'exécution (si non verrouillé dans php.ini)
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Vérifier les paramètres
if (ini_get('display_errors')) {
    throw new RuntimeException('display_errors doit être désactivé en production');
}
```

## Configuration Basée sur l'Environnement

```php
<?php
// config/security.php
return match($_ENV['APP_ENV'] ?? 'production') {
    'development' => [
        'display_errors' => true,
        'debug' => true,
    ],
    'production' => [
        'display_errors' => false,
        'debug' => false,
        'force_https' => true,
    ],
    default => throw new RuntimeException('Environnement inconnu'),
};
```

## Headers de Sécurité

```php
<?php
function setSecurityHeaders(): void {
    // Prévenir le clickjacking
    header('X-Frame-Options: DENY');

    // Prévenir le sniffing MIME
    header('X-Content-Type-Options: nosniff');

    // Protection XSS (navigateurs anciens)
    header('X-XSS-Protection: 1; mode=block');

    // Politique de Sécurité du Contenu
    header("Content-Security-Policy: default-src 'self'; script-src 'self'");

    // Forcer HTTPS
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // Politique Referrer
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
```

## Exemple Concret

**Classe de configuration de sécurité complète**

```php
<?php
declare(strict_types=1);

// Classe de configuration de sécurité
final class SecurityConfig {
    private static bool $initialized = false;

    public static function initialize(): void {
        if (self::$initialized) return;

        // Vérifications en production
        if ($_ENV['APP_ENV'] === 'production') {
            self::enforceProductionSettings();
        }

        // Définir les headers de sécurité
        self::setHeaders();

        // Configurer la session
        self::configureSession();

        self::$initialized = true;
    }

    private static function enforceProductionSettings(): void {
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        // Forcer HTTPS
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
            exit;
        }
    }

    private static function setHeaders(): void {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');

        if ($_ENV['APP_ENV'] === 'production') {
            header('Strict-Transport-Security: max-age=31536000');
        }
    }

    private static function configureSession(): void {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
    }
}

// Appeler tôt dans le bootstrap
SecurityConfig::initialize();
?>
```

## Les Grimoires

- [Configuration de Sécurité (Documentation Officielle)](https://www.php.net/manual/en/security.general.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
