---
source_course: "php-security"
source_lesson: "php-security-secure-tokens"
---

# Générer des Tokens Sécurisés

Les tokens aléatoires sécurisés sont **essentiels pour les IDs de session, tokens CSRF, liens de réinitialisation de mot de passe, et clés API**.

## Randomness Cryptographiquement Sécurisée

```php
<?php
// PHP 7+ - Toujours utiliser random_bytes() pour la sécurité
$bytes = random_bytes(32);  // 32 octets = 256 bits
$token = bin2hex($bytes);   // Chaîne hexadécimale de 64 caractères

// Pour les tokens URL-safe
$urlSafeToken = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
```

## Patterns de Génération de Tokens

```php
<?php
class TokenGenerator
{
    public function generateHex(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    public function generateUrlSafe(int $bytes = 32): string
    {
        // Base64 URL-safe (sans +, /, ou =)
        return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
    }

    public function generateAlphanumeric(int $length = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, 61)];
        }

        return $result;
    }

    public function generateUuid(): string
    {
        $data = random_bytes(16);

        // Définir la version à 4 (aléatoire)
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        // Définir les bits 6-7 à 10
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
```

## Tokens de Réinitialisation de Mot de Passe

```php
<?php
class PasswordReset
{
    private const TOKEN_EXPIRY = 3600;  // 1 heure

    public function createToken(int $userId): string
    {
        // Générer le token
        $selector = bin2hex(random_bytes(16));  // Pour la recherche
        $validator = bin2hex(random_bytes(32)); // Pour la vérification

        // Stocker le validateur hashé (le sélecteur est stocké en clair pour la recherche)
        $this->db->insert('password_resets', [
            'user_id' => $userId,
            'selector' => $selector,
            'hashed_validator' => hash('sha256', $validator),
            'expires_at' => date('Y-m-d H:i:s', time() + self::TOKEN_EXPIRY),
        ]);

        // Retourner le token combiné
        return $selector . ':' . $validator;
    }

    public function validateToken(string $token): ?int
    {
        [$selector, $validator] = explode(':', $token, 2);

        $record = $this->db->findOne('password_resets', [
            'selector' => $selector,
        ]);

        if (!$record) {
            return null;
        }

        // Vérifier l'expiration
        if (strtotime($record['expires_at']) < time()) {
            $this->db->delete('password_resets', ['id' => $record['id']]);
            return null;
        }

        // Vérifier le validateur (comparaison en temps constant)
        if (!hash_equals($record['hashed_validator'], hash('sha256', $validator))) {
            return null;
        }

        // Supprimer le token utilisé
        $this->db->delete('password_resets', ['id' => $record['id']]);

        return $record['user_id'];
    }
}
```

## Génération de Clés API

```php
<?php
class ApiKeyManager
{
    private const PREFIX = 'sk_';  // Pour l'identification

    public function generate(): array
    {
        // Partie visible (pour l'identification)
        $keyId = bin2hex(random_bytes(8));

        // Partie secrète
        $secret = bin2hex(random_bytes(32));

        // Clé complète montrée une fois à l'utilisateur
        $fullKey = self::PREFIX . $keyId . '_' . $secret;

        // Stocker seulement le hash du secret
        return [
            'full_key' => $fullKey,  // Montrer une fois, ne jamais stocker
            'key_id' => $keyId,      // Stocker pour la recherche
            'key_hash' => hash('sha256', $secret),  // Stocker pour la vérification
        ];
    }

    public function verify(string $apiKey): ?array
    {
        // Parser la clé
        if (!str_starts_with($apiKey, self::PREFIX)) {
            return null;
        }

        $parts = explode('_', substr($apiKey, strlen(self::PREFIX)), 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$keyId, $secret] = $parts;

        // Rechercher par key_id
        $record = $this->db->findOne('api_keys', ['key_id' => $keyId]);

        if (!$record || !$record['active']) {
            return null;
        }

        // Vérifier le secret
        if (!hash_equals($record['key_hash'], hash('sha256', $secret))) {
            return null;
        }

        return $record;
    }
}
```

## Les Grimoires

- [random_bytes() (Documentation Officielle)](https://www.php.net/manual/en/function.random-bytes.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
