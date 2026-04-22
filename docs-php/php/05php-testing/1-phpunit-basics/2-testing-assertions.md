---
source_course: "php-testing"
source_lesson: "php-testing-assertions"
---

# Les Assertions en Profondeur

Les assertions sont **le cœur des tests**. Elles vérifient les résultats attendus.

## Assertions de Base

```php
<?php
class AssertionExamplesTest extends TestCase
{
    public function testBasicAssertions(): void
    {
        // Égalité
        $this->assertEquals('hello', 'hello');      // Comparaison lâche
        $this->assertSame('hello', 'hello');        // Stricte (===)
        $this->assertNotEquals(1, 2);

        // Booléen
        $this->assertTrue(true);
        $this->assertFalse(false);

        // Null
        $this->assertNull(null);
        $this->assertNotNull('valeur');

        // Type
        $this->assertIsArray([1, 2, 3]);
        $this->assertIsString('texte');
        $this->assertIsInt(42);
        $this->assertIsBool(true);
        $this->assertInstanceOf(User::class, new User());
    }
}
```

## Assertions de Tableaux

```php
<?php
public function testArrayAssertions(): void
{
    $array = ['name' => 'Jean', 'age' => 30, 'city' => 'Paris'];

    // Contient
    $this->assertArrayHasKey('name', $array);
    $this->assertArrayNotHasKey('email', $array);
    $this->assertContains(30, $array);  // La valeur existe

    // Comptage
    $this->assertCount(3, $array);
    $this->assertNotEmpty($array);
    $this->assertEmpty([]);

    // Égalité
    $this->assertEquals(['a', 'b'], ['a', 'b']);
    $this->assertEqualsCanonicalizing(['b', 'a'], ['a', 'b']);  // Ordre ignoré
}
```

## Assertions de Chaînes

```php
<?php
public function testStringAssertions(): void
{
    $string = 'Bonjour, Monde !';

    $this->assertStringStartsWith('Bonjour', $string);
    $this->assertStringEndsWith('!', $string);
    $this->assertStringContainsString('Monde', $string);
    $this->assertStringNotContainsString('Au revoir', $string);

    // Insensible à la casse
    $this->assertStringContainsStringIgnoringCase('monde', $string);

    // Regex
    $this->assertMatchesRegularExpression('/Bonjour.*Monde/', $string);
}
```

## Assertions d'Exceptions

```php
<?php
public function testExceptionIsThrown(): void
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('La valeur doit être positive');
    $this->expectExceptionCode(400);

    // Code qui lance l'exception
    throw new InvalidArgumentException('La valeur doit être positive', 400);
}

// Alternative : Approche par callback
public function testExceptionWithCallback(): void
{
    $exception = null;

    try {
        $this->calculator->divide(1, 0);
    } catch (DivisionByZeroError $e) {
        $exception = $e;
    }

    $this->assertNotNull($exception);
    $this->assertStringContainsString('zéro', $exception->getMessage());
}
```

## Messages d'Échec Personnalisés

```php
<?php
public function testWithCustomMessage(): void
{
    $actual = 2 + 2;
    $expected = 4;

    $this->assertEquals(
        $expected,
        $actual,
        'L\'addition 2 + 2 doit renvoyer 4'
    );
}
```

## Exemple Complet

**Tests complets avec différentes assertions**

```php
<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\UserValidator;
use App\User;

class UserValidatorTest extends TestCase
{
    private UserValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UserValidator();
    }

    public function testValidEmailPasses(): void
    {
        $result = $this->validator->validateEmail('jean@exemple.com');
        $this->assertTrue($result);
    }

    public function testInvalidEmailFails(): void
    {
        $result = $this->validator->validateEmail('pas-un-email');
        $this->assertFalse($result);
    }

    /**
     * @dataProvider invalidEmailProvider
     */
    public function testVariousInvalidEmails(string $email): void
    {
        $this->assertFalse($this->validator->validateEmail($email));
    }

    public static function invalidEmailProvider(): array
    {
        return [
            'manque @' => ['invalide'],
            'manque domaine' => ['test@'],
            'manque local' => ['@exemple.com'],
            'espaces' => ['test @exemple.com'],
            'double @' => ['test@@exemple.com'],
        ];
    }

    public function testPasswordStrength(): void
    {
        // Mot de passe faible
        $result = $this->validator->validatePassword('123');
        $this->assertFalse($result['valid']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertContains('Le mot de passe doit contenir au moins 8 caractères', $result['errors']);

        // Mot de passe fort
        $result = $this->validator->validatePassword('SecureP@ss123');
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }

    public function testValidateUserThrowsOnInvalidData(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email est requis');

        $this->validator->validateUser(['name' => 'Jean']);
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
