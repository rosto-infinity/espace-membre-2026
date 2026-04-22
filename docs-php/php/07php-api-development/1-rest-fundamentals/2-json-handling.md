---
source_course: "php-api-development"
source_lesson: "php-api-development-json-handling"
---

# Traitement des Requêtes & Réponses JSON

Les APIs modernes communiquent via JSON. PHP offre d'excellents outils pour le **traitement JSON**.

## Lire les Requêtes JSON

```php
<?php
// Obtenir le corps brut de la requête POST
$json = file_get_contents('php://input');

// Décoder le JSON
$data = json_decode($json, associative: true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalide']);
    exit;
}

// Accéder aux données
$name = $data['name'] ?? null;
$email = $data['email'] ?? null;
```

## Envoyer des Réponses JSON

```php
<?php
function jsonResponse(mixed $data, int $status = 200): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    echo json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}

// Utilisation
jsonResponse(['id' => 1, 'name' => 'Jean'], 201);
```

## Options d'Encodage JSON

```php
<?php
$data = [
    'id' => 1,
    'name' => 'Test',
    'emoji' => '🎉',
    'url' => 'https://example.com/path?q=1',
];

// Par défaut
echo json_encode($data);
// {"id":1,"name":"Test","emoji":"\ud83c\udf89","url":"https:\/\/example.com\/path?q=1"}

// Avec options
echo json_encode($data,
    JSON_UNESCAPED_UNICODE |  // Garder les emojis lisibles
    JSON_UNESCAPED_SLASHES |  // Ne pas échapper /
    JSON_PRETTY_PRINT         // Format lisible
);
/*
{
    "id": 1,
    "name": "Test",
    "emoji": "🎉",
    "url": "https://example.com/path?q=1"
}
*/
```

## Gestion des Erreurs

```php
<?php
function decodeJson(string $json): array {
    try {
        return json_decode(
            $json,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    } catch (JsonException $e) {
        throw new BadRequestException('JSON invalide : ' . $e->getMessage());
    }
}

function encodeJson(mixed $data): string {
    try {
        return json_encode($data, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new RuntimeException('Encodage JSON échoué : ' . $e->getMessage());
    }
}
```

## Format de Réponse Cohérent

```php
<?php
class ApiResponse {
    public static function success(mixed $data, int $status = 200): never {
        self::send(['data' => $data], $status);
    }

    public static function created(mixed $data): never {
        self::send(['data' => $data], 201);
    }

    public static function noContent(): never {
        http_response_code(204);
        exit;
    }

    public static function error(string $message, int $status = 400, ?array $errors = null): never {
        $response = ['error' => ['message' => $message]];
        if ($errors !== null) {
            $response['error']['details'] = $errors;
        }
        self::send($response, $status);
    }

    private static function send(array $data, int $status): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}

// Utilisation
ApiResponse::success(['id' => 1, 'name' => 'Jean']);
// {"data":{"id":1,"name":"Jean"}}

ApiResponse::error('Validation échouée', 422, ['email' => 'Format invalide']);
// {"error":{"message":"Validation échouée","details":{"email":"Format invalide"}}}
```

## Exemple Concret

**Gestionnaire complet de requêtes/réponses JSON pour une API**

```php
<?php
declare(strict_types=1);

// Gestionnaire complet JSON API
class JsonApiHandler {
    private array $requestData = [];

    public function __construct() {
        $this->parseRequest();
    }

    private function parseRequest(): void {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'application/json')) {
            $json = file_get_contents('php://input');

            if ($json !== '' && $json !== '[]' && $json !== '{}') {
                try {
                    $this->requestData = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                } catch (JsonException $e) {
                    $this->errorResponse('JSON invalide : ' . $e->getMessage(), 400);
                }
            }
        }
    }

    public function input(string $key, mixed $default = null): mixed {
        return $this->requestData[$key] ?? $_GET[$key] ?? $default;
    }

    public function all(): array {
        return array_merge($_GET, $this->requestData);
    }

    public function validate(array $rules): array {
        $data = [];
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $this->input($field);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && ($value === null || $value === '')) {
                    $errors[$field] = "$field est requis";
                    break;
                }

                if ($rule === 'email' && $value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field] = "$field doit être un email valide";
                    break;
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if ($value && strlen($value) < $min) {
                        $errors[$field] = "$field doit contenir au moins $min caractères";
                        break;
                    }
                }
            }

            if (!isset($errors[$field])) {
                $data[$field] = $value;
            }
        }

        if ($errors) {
            $this->errorResponse('Validation échouée', 422, $errors);
        }

        return $data;
    }

    public function successResponse(mixed $data, int $status = 200): never {
        $this->sendJson(['data' => $data], $status);
    }

    public function errorResponse(string $message, int $status = 400, ?array $details = null): never {
        $error = ['message' => $message];
        if ($details) {
            $error['details'] = $details;
        }
        $this->sendJson(['error' => $error], $status);
    }

    private function sendJson(array $data, int $status): never {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }
}

// Utilisation
$api = new JsonApiHandler();

$data = $api->validate([
    'name' => ['required', 'min:2'],
    'email' => ['required', 'email'],
]);

// Si la validation réussit, $data contient les données validées
$api->successResponse(['user' => $data], 201);
?>
```

## Les Grimoires

- [json_encode (Documentation Officielle)](https://www.php.net/manual/en/function.json-encode.php)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
