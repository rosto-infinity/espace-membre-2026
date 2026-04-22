---
source_course: "php-api-development"
source_lesson: "php-api-development-error-handling"
---

# Gestion Cohérente des Erreurs

Des réponses d'erreur cohérentes rendent votre API **prévisible et conviviale pour les développeurs**.

## Format de Réponse d'Erreur

```php
<?php
class ApiException extends Exception {
    public function __construct(
        string $message,
        public int $statusCode = 400,
        public ?array $errors = null,
        public ?string $code = null
    ) {
        parent::__construct($message);
    }

    public function toArray(): array {
        $response = [
            'error' => [
                'message' => $this->getMessage(),
                'code' => $this->code ?? $this->getDefaultCode(),
            ],
        ];

        if ($this->errors) {
            $response['error']['details'] = $this->errors;
        }

        return $response;
    }

    private function getDefaultCode(): string {
        return match($this->statusCode) {
            400 => 'BAD_REQUEST',
            401 => 'UNAUTHORIZED',
            403 => 'FORBIDDEN',
            404 => 'NOT_FOUND',
            422 => 'VALIDATION_ERROR',
            429 => 'RATE_LIMITED',
            500 => 'INTERNAL_ERROR',
            default => 'ERROR',
        };
    }
}

class ValidationException extends ApiException {
    public function __construct(array $errors) {
        parent::__construct('Validation échouée', 422, $errors, 'VALIDATION_ERROR');
    }
}

class NotFoundException extends ApiException {
    public function __construct(string $resource = 'Ressource') {
        parent::__construct("$resource introuvable", 404, null, 'NOT_FOUND');
    }
}

class UnauthorizedException extends ApiException {
    public function __construct(string $message = 'Authentification requise') {
        parent::__construct($message, 401, null, 'UNAUTHORIZED');
    }
}
```

## Gestionnaire Global des Erreurs

```php
<?php
class ErrorHandler {
    public static function register(): void {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e): void {
        if ($e instanceof ApiException) {
            self::sendResponse($e->statusCode, $e->toArray());
        } elseif ($e instanceof JsonException) {
            self::sendResponse(400, [
                'error' => [
                    'message' => 'JSON invalide',
                    'code' => 'INVALID_JSON',
                ],
            ]);
        } else {
            // Logger l'erreur réelle
            error_log($e->getMessage() . "\n" . $e->getTraceAsString());

            // Envoyer une erreur générique (ne pas exposer les détails internes)
            $message = getenv('APP_DEBUG') ? $e->getMessage() : 'Une erreur inattendue est survenue';
            self::sendResponse(500, [
                'error' => [
                    'message' => $message,
                    'code' => 'INTERNAL_ERROR',
                ],
            ]);
        }
    }

    public static function handleError(int $errno, string $errstr, string $errfile, int $errline): bool {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleShutdown(): void {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            self::handleException(new ErrorException(
                $error['message'], 0, $error['type'], $error['file'], $error['line']
            ));
        }
    }

    private static function sendResponse(int $status, array $body): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($body);
        exit;
    }
}

// Utilisation
ErrorHandler::register();

// Dans le contrôleur
throw new NotFoundException('Utilisateur');
// Retourne : {"error":{"message":"Utilisateur introuvable","code":"NOT_FOUND"}}

throw new ValidationException([
    'email' => 'Format d\'email invalide',
    'password' => 'Mot de passe trop court',
]);
// Retourne : {"error":{"message":"Validation échouée","code":"VALIDATION_ERROR","details":{...}}}
```

## Exemple Complet

**Gestion des erreurs conforme RFC 7807**

```php
<?php
declare(strict_types=1);

// Gestion complète des erreurs API

// Structure de réponse d'erreur (RFC 7807 - Problem Details)
class ProblemDetails {
    public function __construct(
        public string $type,
        public string $title,
        public int $status,
        public ?string $detail = null,
        public ?string $instance = null,
        public array $extensions = []
    ) {}

    public function toArray(): array {
        $result = [
            'type' => $this->type,
            'title' => $this->title,
            'status' => $this->status,
        ];

        if ($this->detail) $result['detail'] = $this->detail;
        if ($this->instance) $result['instance'] = $this->instance;

        return array_merge($result, $this->extensions);
    }

    public static function validation(array $errors): self {
        return new self(
            type: 'https://api.example.com/problems/validation-error',
            title: 'Validation Échouée',
            status: 422,
            detail: 'Un ou plusieurs champs ont échoué la validation',
            extensions: ['errors' => $errors]
        );
    }

    public static function notFound(string $resource, string|int $id): self {
        return new self(
            type: 'https://api.example.com/problems/not-found',
            title: 'Ressource Introuvable',
            status: 404,
            detail: "$resource avec l'ID $id est introuvable",
            instance: "/{$resource}s/$id"
        );
    }

    public static function unauthorized(): self {
        return new self(
            type: 'https://api.example.com/problems/unauthorized',
            title: 'Non Autorisé',
            status: 401,
            detail: 'Des identifiants d\'authentification valides sont requis'
        );
    }

    public static function forbidden(): self {
        return new self(
            type: 'https://api.example.com/problems/forbidden',
            title: 'Interdit',
            status: 403,
            detail: 'Vous n\'avez pas la permission d\'accéder à cette ressource'
        );
    }

    public static function rateLimited(int $retryAfter): self {
        return new self(
            type: 'https://api.example.com/problems/rate-limited',
            title: 'Trop de Requêtes',
            status: 429,
            detail: 'Limite de débit dépassée',
            extensions: ['retryAfter' => $retryAfter]
        );
    }
}

// Utilisation dans le contrôleur
class UserController extends Controller {
    public function show(string $id): never {
        $user = $this->users->find((int) $id);

        if (!$user) {
            $problem = ProblemDetails::notFound('User', $id);
            $this->problemResponse($problem);
        }

        $this->json(['data' => $user->toArray()]);
    }

    public function store(): never {
        $data = $this->input();
        $errors = $this->validate($data);

        if ($errors) {
            $problem = ProblemDetails::validation($errors);
            $this->problemResponse($problem);
        }

        $user = $this->users->create($data);
        $this->json(['data' => $user->toArray()], 201);
    }

    protected function problemResponse(ProblemDetails $problem): never {
        http_response_code($problem->status);
        header('Content-Type: application/problem+json');
        echo json_encode($problem->toArray());
        exit;
    }
}
?>
```

## Les Grimoires

- [RFC 7807 - Problem Details (IETF)](https://datatracker.ietf.org/doc/html/rfc7807)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
