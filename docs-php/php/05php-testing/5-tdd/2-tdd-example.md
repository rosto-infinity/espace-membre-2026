---
source_course: "php-testing"
source_lesson: "php-testing-tdd-example"
---

# TDD en Pratique

Construisons **un validateur de mot de passe en utilisant le TDD**.

## Exigences

- Minimum 8 caractères
- Au moins une lettre majuscule
- Au moins une lettre minuscule
- Au moins un chiffre
- Au moins un caractère spécial

## Étape 1 : Premier Test (ROUGE)

```php
<?php
class PasswordValidatorTest extends TestCase
{
    private PasswordValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PasswordValidator();
    }

    public function testRejectsShortPassword(): void
    {
        $result = $this->validator->validate('Court1!');

        $this->assertFalse($result->isValid);
        $this->assertContains(
            'Le mot de passe doit contenir au moins 8 caractères',
            $result->errors
        );
    }
}
```

## Étape 2 : Implémentation Minimale (VERT)

```php
<?php
class ValidationResult
{
    public function __construct(
        public bool $isValid,
        public array $errors = []
    ) {}
}

class PasswordValidator
{
    public function validate(string $password): ValidationResult
    {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caractères';
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors
        );
    }
}
```

## Étape 3 : Ajouter Plus de Tests

```php
<?php
public function testRejectsPasswordWithoutUppercase(): void
{
    $result = $this->validator->validate('minuscule1!');

    $this->assertFalse($result->isValid);
    $this->assertContains(
        'Le mot de passe doit contenir une lettre majuscule',
        $result->errors
    );
}

public function testRejectsPasswordWithoutLowercase(): void
{
    $result = $this->validator->validate('MAJUSCULE1!');

    $this->assertFalse($result->isValid);
}

public function testRejectsPasswordWithoutNumber(): void
{
    $result = $this->validator->validate('SansChiffre!');

    $this->assertFalse($result->isValid);
}

public function testRejectsPasswordWithoutSpecialChar(): void
{
    $result = $this->validator->validate('SansSpecial1');

    $this->assertFalse($result->isValid);
}

public function testAcceptsValidPassword(): void
{
    $result = $this->validator->validate('ValidP@ss1');

    $this->assertTrue($result->isValid);
    $this->assertEmpty($result->errors);
}
```

## Étape 4 : Implémentation Complète

```php
<?php
class PasswordValidator
{
    private array $rules = [];

    public function __construct()
    {
        $this->rules = [
            ['/^.{8,}$/', 'Le mot de passe doit contenir au moins 8 caractères'],
            ['/[A-Z]/', 'Le mot de passe doit contenir une lettre majuscule'],
            ['/[a-z]/', 'Le mot de passe doit contenir une lettre minuscule'],
            ['/[0-9]/', 'Le mot de passe doit contenir un chiffre'],
            ['/[!@#$%^&*(),.?":{}|<>]/', 'Le mot de passe doit contenir un caractère spécial'],
        ];
    }

    public function validate(string $password): ValidationResult
    {
        $errors = [];

        foreach ($this->rules as [$pattern, $message]) {
            if (!preg_match($pattern, $password)) {
                $errors[] = $message;
            }
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors
        );
    }
}
```

## Exemple Complet

**TDD appliqué à un raccourcisseur d'URL**

```php
<?php
declare(strict_types=1);

// Suite de tests finale
class UrlShortenerTest extends TestCase
{
    private UrlShortener $shortener;
    private InMemoryUrlRepository $repository;

    protected function setUp(): void
    {
        $this->repository = new InMemoryUrlRepository();
        $this->shortener = new UrlShortener($this->repository);
    }

    public function testShortenUrlReturnsShortCode(): void
    {
        $code = $this->shortener->shorten('https://exemple.com/très/long/url');

        $this->assertNotEmpty($code);
        $this->assertEquals(6, strlen($code));
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $code);
    }

    public function testExpandReturnsOriginalUrl(): void
    {
        $url = 'https://exemple.com/test';
        $code = $this->shortener->shorten($url);

        $expanded = $this->shortener->expand($code);

        $this->assertEquals($url, $expanded);
    }

    public function testSameUrlReturnsSameCode(): void
    {
        $url = 'https://exemple.com/test';

        $code1 = $this->shortener->shorten($url);
        $code2 = $this->shortener->shorten($url);

        $this->assertEquals($code1, $code2);
    }

    public function testExpandUnknownCodeThrows(): void
    {
        $this->expectException(NotFoundException::class);

        $this->shortener->expand('introuvable');
    }

    public function testRejectsInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->shortener->shorten('pas-une-url-valide');
    }
}

// Implémentation construite via TDD
class UrlShortener
{
    public function __construct(
        private UrlRepository $repository,
        private int $codeLength = 6
    ) {}

    public function shorten(string $url): string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('URL invalide');
        }

        $existing = $this->repository->findByUrl($url);
        if ($existing) {
            return $existing->code;
        }

        do {
            $code = $this->generateCode();
        } while ($this->repository->findByCode($code));

        $this->repository->save(new ShortUrl($code, $url));

        return $code;
    }

    public function expand(string $code): string
    {
        $shortUrl = $this->repository->findByCode($code);

        if (!$shortUrl) {
            throw new NotFoundException("URL introuvable pour le code : $code");
        }

        return $shortUrl->url;
    }

    private function generateCode(): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        for ($i = 0; $i < $this->codeLength; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
