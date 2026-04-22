---
source_course: "php-security"
source_lesson: "php-security-secure-error-handling"
---

# Gestion Sécurisée des Erreurs

Une gestion correcte des erreurs **prévient les fuites d'informations** tout en fournissant des capacités de débogage utiles.

## Gestionnaire d'Erreurs Personnalisé

```php
<?php
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline): bool {
    // Logger tous les détails
    error_log(sprintf(
        "[%s] %s dans %s:%d",
        match($errno) {
            E_WARNING => 'WARNING',
            E_NOTICE => 'NOTICE',
            E_USER_ERROR => 'USER_ERROR',
            default => 'ERROR',
        },
        $errstr,
        $errfile,
        $errline
    ));

    // En production, ne pas afficher à l'utilisateur
    if ($_ENV['APP_ENV'] === 'production') {
        return true;  // Ne pas exécuter le gestionnaire interne de PHP
    }

    return false;  // Laisser PHP gérer l'affichage en dev
});
```

## Gestionnaire d'Exceptions Personnalisé

```php
<?php
set_exception_handler(function(Throwable $e): void {
    // Générer un ID d'erreur unique pour le suivi
    $errorId = bin2hex(random_bytes(8));

    // Logger tous les détails de l'erreur
    error_log(sprintf(
        "[%s] %s non capturée : %s dans %s:%d\nTrace de la pile :\n%s",
        $errorId,
        get_class($e),
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    ));

    // Afficher une page d'erreur sûre à l'utilisateur
    http_response_code(500);

    if ($_ENV['APP_ENV'] === 'production') {
        echo "Une erreur est survenue. Référence : $errorId";
    } else {
        // Afficher les détails en développement
        echo "<pre>" . htmlspecialchars($e) . "</pre>";
    }
});
```

## Journalisation Structurée des Erreurs

```php
<?php
class SecureLogger
{
    public function logError(Throwable $e, array $context = []): string
    {
        $errorId = $this->generateErrorId();

        $logData = [
            'error_id' => $errorId,
            'timestamp' => date('c'),
            'type' => get_class($e),
            'message' => $e->getMessage(),
            'code' => $e->getCode(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
            'context' => $this->sanitizeContext($context),
        ];

        // Logger dans un emplacement sécurisé
        file_put_contents(
            '/var/log/app/errors.log',
            json_encode($logData) . "\n",
            FILE_APPEND | LOCK_EX
        );

        return $errorId;
    }

    private function sanitizeContext(array $context): array
    {
        $sensitive = ['password', 'token', 'secret', 'key', 'auth'];

        return array_map(function($value, $key) use ($sensitive) {
            foreach ($sensitive as $word) {
                if (stripos($key, $word) !== false) {
                    return '[MASQUÉ]';
                }
            }
            return $value;
        }, $context, array_keys($context));
    }
}
```

## Réponses d'Erreurs API

```php
<?php
class ApiErrorHandler
{
    public function handle(Throwable $e): array
    {
        $errorId = bin2hex(random_bytes(8));

        // Logger tous les détails en interne
        $this->logger->logError($e, ['error_id' => $errorId]);

        // Retourner une réponse sûre
        if ($e instanceof ValidationException) {
            return [
                'error' => 'validation_error',
                'message' => $e->getMessage(),
                'errors' => $e->getErrors(),
            ];
        }

        if ($e instanceof NotFoundException) {
            return [
                'error' => 'not_found',
                'message' => 'Ressource introuvable',
            ];
        }

        // Erreur générique pour tout le reste
        return [
            'error' => 'server_error',
            'message' => 'Une erreur inattendue est survenue',
            'reference' => $errorId,
        ];
    }
}
```

## Les Grimoires

- [Gestion des Erreurs (Documentation Officielle)](https://www.php.net/manual/en/book.errorfunc.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
