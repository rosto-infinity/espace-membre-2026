---
source_course: "php-api-development"
source_lesson: "php-api-development-jwt-auth"
---

# L'Authentification JWT

Les JSON Web Tokens (JWT) sont une méthode d'authentification **sans état**, idéale pour les APIs.

## Structure d'un JWT

```
Header.Payload.Signature

eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.  <- Header (base64)
eyJ1c2VyX2lkIjoxLCJleHAiOjE3MDk5MjM0NTZ9.  <- Payload (base64)
SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c  <- Signature
```

## Créer des JWT Sans Librairie

```php
<?php
class JWT {
    private string $secret;

    public function __construct(string $secret) {
        $this->secret = $secret;
    }

    public function encode(array $payload, int $expiresIn = 3600): string {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiresIn;

        $headerEncoded = $this->base64UrlEncode(json_encode($header));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $this->secret,
            true
        );
        $signatureEncoded = $this->base64UrlEncode($signature);

        return "$headerEncoded.$payloadEncoded.$signatureEncoded";
    }

    public function decode(string $token): ?array {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Vérifier la signature
        $expectedSignature = hash_hmac(
            'sha256',
            "$headerEncoded.$payloadEncoded",
            $this->secret,
            true
        );

        if (!hash_equals($this->base64UrlEncode($expectedSignature), $signatureEncoded)) {
            return null;  // Signature invalide
        }

        $payload = json_decode($this->base64UrlDecode($payloadEncoded), true);

        // Vérifier l'expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;  // Token expiré
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
```

## Flux d'Authentification

```php
<?php
// Endpoint de connexion
$router->post('/auth/login', function() use ($jwt, $userRepo) {
    $input = json_decode(file_get_contents('php://input'), true);

    $user = $userRepo->findByEmail($input['email'] ?? '');

    if (!$user || !password_verify($input['password'] ?? '', $user->passwordHash)) {
        http_response_code(401);
        return ['error' => 'Identifiants invalides'];
    }

    $token = $jwt->encode([
        'user_id' => $user->id,
        'email' => $user->email,
        'role' => $user->role,
    ], 86400);  // 24 heures

    return [
        'token' => $token,
        'expires_in' => 86400,
        'token_type' => 'Bearer',
    ];
});
```

## Lire le Token

```php
<?php
function getAuthUser(JWT $jwt): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/^Bearer\s+(\S+)$/', $header, $matches)) {
        return null;
    }

    return $jwt->decode($matches[1]);
}

// Endpoint protégé
$router->get('/me', function() use ($jwt) {
    $user = getAuthUser($jwt);

    if (!$user) {
        http_response_code(401);
        return ['error' => 'Non autorisé'];
    }

    return ['user' => $user];
});
```

## Les Grimoires

- [Introduction aux JWT (jwt.io)](https://jwt.io/introduction)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
