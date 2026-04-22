---
source_course: "php-security"
source_lesson: "php-security-encryption-basics"
---

# Fondamentaux du Chiffrement

Le chiffrement **protège la confidentialité des données**. Comprendre quand et comment l'utiliser est crucial.

## Hachage vs Chiffrement

| Hachage                       | Chiffrement                               |
| ----------------------------- | ----------------------------------------- |
| Sens unique (irréversible)    | Double sens (réversible)                  |
| Même entrée = même sortie     | Différent à chaque fois (avec IV)         |
| Pour mots de passe, intégrité | Pour les données que vous devez récupérer |
| password_hash(), hash()       | openssl_encrypt()                         |

## Chiffrement Symétrique (AES)

```php
<?php
// La même clé chiffre et déchiffre
function encrypt(string $plaintext, string $key): string {
    $iv = random_bytes(16);  // Vecteur d'initialisation

    $ciphertext = openssl_encrypt(
        $plaintext,
        'AES-256-GCM',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag  // Étiquette d'authentification
    );

    // Combiner IV + étiquette + texte chiffré
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt(string $encrypted, string $key): string|false {
    $data = base64_decode($encrypted);

    $iv = substr($data, 0, 16);
    $tag = substr($data, 16, 16);
    $ciphertext = substr($data, 32);

    return openssl_decrypt(
        $ciphertext,
        'AES-256-GCM',
        $key,
        OPENSSL_RAW_DATA,
        $iv,
        $tag
    );
}
```

## Générer des Clés Sécurisées

```php
<?php
// Pour AES-256, on a besoin de 32 octets
$key = random_bytes(32);

// Stocker la clé en sécurité (pas dans le code !)
// Utiliser des variables d'environnement ou un service de gestion de clés
$key = $_ENV['ENCRYPTION_KEY'];

// Ou dériver depuis un mot de passe
$key = hash('sha256', $password, true);  // 32 octets
```

## Génération Aléatoire Sécurisée

```php
<?php
// Utiliser random_bytes() pour la randomness cryptographique
$token = bin2hex(random_bytes(32));  // 64 caractères hexadécimaux
$apiKey = base64_encode(random_bytes(32));  // Chaîne Base64

// Ne JAMAIS utiliser pour la sécurité :
rand();
mt_rand();
uniqid();
array_rand();
```

## Quand Utiliser le Chiffrement

```php
<?php
// Chiffrer : Les données que vous devez récupérer
// - Numéros de carte de crédit
// - Documents personnels
// - Clés API (stockées)
// - Données utilisateur sensibles

// Hasher : Les données que vous avez seulement besoin de vérifier
// - Mots de passe
// - Intégrité des fichiers
// - Signatures numériques
```

## Exemple Concret

**Service de chiffrement AES-256-GCM prêt pour la production**

```php
<?php
declare(strict_types=1);

// Service de chiffrement sécurisé
class EncryptionService {
    private const CIPHER = 'AES-256-GCM';
    private const IV_LENGTH = 16;
    private const TAG_LENGTH = 16;

    public function __construct(
        private string $key
    ) {
        if (strlen($key) !== 32) {
            throw new InvalidArgumentException('La clé doit être de 32 octets pour AES-256');
        }
    }

    public function encrypt(string $plaintext): string {
        $iv = random_bytes(self::IV_LENGTH);
        $tag = '';

        $ciphertext = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            self::TAG_LENGTH
        );

        if ($ciphertext === false) {
            throw new RuntimeException('Chiffrement échoué : ' . openssl_error_string());
        }

        return base64_encode($iv . $tag . $ciphertext);
    }

    public function decrypt(string $encrypted): string {
        $data = base64_decode($encrypted, true);

        if ($data === false || strlen($data) < self::IV_LENGTH + self::TAG_LENGTH + 1) {
            throw new InvalidArgumentException('Données chiffrées invalides');
        }

        $iv = substr($data, 0, self::IV_LENGTH);
        $tag = substr($data, self::IV_LENGTH, self::TAG_LENGTH);
        $ciphertext = substr($data, self::IV_LENGTH + self::TAG_LENGTH);

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::CIPHER,
            $this->key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($plaintext === false) {
            throw new RuntimeException('Déchiffrement échoué - les données peuvent avoir été altérées');
        }

        return $plaintext;
    }
}

// Utilisation
$key = hex2bin($_ENV['ENCRYPTION_KEY']);  // 64 chars hex = 32 octets
$encryptor = new EncryptionService($key);

$encrypted = $encryptor->encrypt('Données sensibles');
$decrypted = $encryptor->decrypt($encrypted);
?>
```

## Les Grimoires

- [OpenSSL Encrypt (Documentation Officielle)](https://www.php.net/manual/en/function.openssl-encrypt.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
