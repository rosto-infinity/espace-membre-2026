---
source_course: "php-security"
source_lesson: "php-security-multi-factor-auth"
---

# Implémenter l'Authentification Multi-Facteurs

L'AMF ajoute **une couche de sécurité supplémentaire** au-delà des mots de passe. TOTP (Time-based One-Time Password) est l'approche la plus courante.

## Implémentation TOTP

```php
<?php
class TotpAuthenticator
{
    private const PERIOD = 30;  // Secondes par code
    private const DIGITS = 6;

    public function generateSecret(int $length = 20): string
    {
        // Générer un secret aléatoire
        $secret = random_bytes($length);
        return $this->base32Encode($secret);
    }

    public function getQrCodeUri(string $secret, string $email, string $issuer): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=%d&period=%d',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer),
            self::DIGITS,
            self::PERIOD
        );
    }

    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = time();

        // Vérifier la fenêtre de temps actuelle et adjacentes
        for ($i = -$window; $i <= $window; $i++) {
            $checkTime = $timestamp + ($i * self::PERIOD);
            $expectedCode = $this->generateCode($secret, $checkTime);

            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    private function generateCode(string $secret, int $timestamp): string
    {
        $counter = intdiv($timestamp, self::PERIOD);
        $counterBytes = pack('J', $counter);  // Big-endian 64 bits

        $hash = hash_hmac('sha1', $counterBytes, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0f;

        $code = (
            (ord($hash[$offset]) & 0x7f) << 24 |
            (ord($hash[$offset + 1]) & 0xff) << 16 |
            (ord($hash[$offset + 2]) & 0xff) << 8 |
            (ord($hash[$offset + 3]) & 0xff)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private function base32Encode(string $data): string { /* ... */ }
    private function base32Decode(string $data): string { /* ... */ }
}
```

## Codes de Secours

```php
<?php
class BackupCodes
{
    private const CODE_COUNT = 10;
    private const CODE_LENGTH = 8;

    public function generate(): array
    {
        $codes = [];

        for ($i = 0; $i < self::CODE_COUNT; $i++) {
            $codes[] = $this->generateCode();
        }

        return $codes;
    }

    private function generateCode(): string
    {
        return strtoupper(bin2hex(random_bytes(self::CODE_LENGTH / 2)));
    }

    public function store(int $userId, array $codes): void
    {
        // Hasher les codes avant le stockage
        foreach ($codes as $code) {
            $hash = password_hash($code, PASSWORD_DEFAULT);
            $this->db->insert('backup_codes', [
                'user_id' => $userId,
                'code_hash' => $hash,
                'used' => false,
            ]);
        }
    }

    public function verify(int $userId, string $code): bool
    {
        $codes = $this->db->query(
            'SELECT id, code_hash FROM backup_codes WHERE user_id = ? AND used = 0',
            [$userId]
        );

        foreach ($codes as $storedCode) {
            if (password_verify($code, $storedCode['code_hash'])) {
                // Marquer comme utilisé
                $this->db->update('backup_codes',
                    ['used' => true],
                    ['id' => $storedCode['id']]
                );
                return true;
            }
        }

        return false;
    }
}
```

## Flux AMF Complet

```php
<?php
class AuthController
{
    public function login(Request $request): Response
    {
        $user = $this->validateCredentials(
            $request->get('email'),
            $request->get('password')
        );

        if (!$user) {
            return $this->error('Identifiants invalides');
        }

        // Vérifier si l'AMF est activée
        if ($user->mfa_enabled) {
            // Créer une session temporaire pour la vérification AMF
            $_SESSION['mfa_user_id'] = $user->id;
            $_SESSION['mfa_expires'] = time() + 300;  // 5 minutes

            return $this->redirect('/mfa-verify');
        }

        // Pas d'AMF - compléter la connexion
        $this->completeLogin($user);
        return $this->redirect('/dashboard');
    }

    public function verifyMfa(Request $request): Response
    {
        // Vérifier la session temporaire
        if (!isset($_SESSION['mfa_user_id']) ||
            $_SESSION['mfa_expires'] < time()) {
            return $this->redirect('/login');
        }

        $userId = $_SESSION['mfa_user_id'];
        $user = $this->userRepo->find($userId);
        $code = $request->get('code');

        // Essayer TOTP d'abord
        if ($this->totp->verify($user->mfa_secret, $code)) {
            unset($_SESSION['mfa_user_id'], $_SESSION['mfa_expires']);
            $this->completeLogin($user);
            return $this->redirect('/dashboard');
        }

        // Essayer le code de secours
        if ($this->backupCodes->verify($userId, $code)) {
            unset($_SESSION['mfa_user_id'], $_SESSION['mfa_expires']);
            $this->completeLogin($user);
            return $this->redirect('/dashboard');
        }

        return $this->error('Code invalide');
    }
}
```

## Les Grimoires

- [Spécification TOTP (RFC 6238)](https://datatracker.ietf.org/doc/html/rfc6238)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
