---
source_course: "php-testing"
source_lesson: "php-testing-data-providers"
---

# Les Fournisseurs de Données (Data Providers)

Les fournisseurs de données vous permettent **d'exécuter le même test avec différentes entrées**, réduisant la duplication de code.

## Fournisseur de Données de Base

```php
<?php
class MathTest extends TestCase
{
    /**
     * @dataProvider additionProvider
     */
    public function testAdd(int $a, int $b, int $expected): void
    {
        $calculator = new Calculator();
        $this->assertEquals($expected, $calculator->add($a, $b));
    }

    public static function additionProvider(): array
    {
        return [
            'nombres positifs' => [1, 2, 3],
            'nombres négatifs' => [-1, -2, -3],
            'signes mixtes' => [-1, 2, 1],
            'avec zéro' => [0, 5, 5],
        ];
    }
}

// Sortie :
// ✓ testAdd avec nombres positifs
// ✓ testAdd avec nombres négatifs
// ✓ testAdd avec signes mixtes
// ✓ testAdd avec zéro
```

## Utiliser les Attributs PHP 8

```php
<?php
use PHPUnit\Framework\Attributes\DataProvider;

class ValidatorTest extends TestCase
{
    #[DataProvider('emailProvider')]
    public function testEmailValidation(string $email, bool $expected): void
    {
        $validator = new EmailValidator();
        $this->assertEquals($expected, $validator->isValid($email));
    }

    public static function emailProvider(): array
    {
        return [
            'email valide' => ['user@example.com', true],
            'valide avec sous-domaine' => ['user@mail.example.com', true],
            'valide avec plus' => ['user+tag@example.com', true],
            'manque @' => ['userexample.com', false],
            'manque domaine' => ['user@', false],
            'caractères invalides' => ['user<>@example.com', false],
        ];
    }
}
```

## Fournisseur de Données Générateur

```php
<?php
class LargeDataTest extends TestCase
{
    /**
     * @dataProvider largeNumberProvider
     */
    public function testSquareRoot(float $input, float $expected): void
    {
        $this->assertEqualsWithDelta($expected, sqrt($input), 0.0001);
    }

    public static function largeNumberProvider(): \Generator
    {
        // Utiliser un générateur pour les grands ensembles (économe en mémoire)
        for ($i = 1; $i <= 100; $i++) {
            yield "sqrt de $i" => [$i * $i, (float) $i];
        }
    }
}
```

## Exemple Complet

**Tests complets avec fournisseurs de données pour la génération de slugs**

```php
<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SlugGeneratorTest extends TestCase
{
    private SlugGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SlugGenerator();
    }

    #[DataProvider('slugProvider')]
    public function testGenerateSlug(string $input, string $expected): void
    {
        $this->assertEquals($expected, $this->generator->generate($input));
    }

    public static function slugProvider(): array
    {
        return [
            'minuscules' => ['Bonjour Monde', 'bonjour-monde'],
            'caractères spéciaux' => ['Bonjour! Monde?', 'bonjour-monde'],
            'espaces multiples' => ['Bonjour    Monde', 'bonjour-monde'],
            'unicode' => ['Héllo Wörld', 'hello-world'],
            'chiffres' => ['Produit 123', 'produit-123'],
            'déjà slug' => ['bonjour-monde', 'bonjour-monde'],
            'espaces début/fin' => ['  Bonjour Monde  ', 'bonjour-monde'],
            'esperluette' => ['Sel & Poivre', 'sel-and-poivre'],
            'vide' => ['', ''],
        ];
    }

    #[DataProvider('maxLengthProvider')]
    public function testSlugMaxLength(string $input, int $maxLength, string $expected): void
    {
        $this->assertEquals(
            $expected,
            $this->generator->generate($input, $maxLength)
        );
    }

    public static function maxLengthProvider(): array
    {
        return [
            'tronquer long' => ['Voici un très long titre', 10, 'voici-un-t'],
            'pas tronquer court' => ['Court', 100, 'court'],
            'longueur exacte' => ['Bonjour', 7, 'bonjour'],
        ];
    }
}

// Implémentation SlugGenerator
class SlugGenerator
{
    public function generate(string $text, int $maxLength = 200): string
    {
        // Convertir en minuscules
        $slug = strtolower($text);

        // Remplacer & par 'and'
        $slug = str_replace('&', 'and', $slug);

        // Supprimer les accents
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug) ?: $slug;

        // Remplacer les non-alphanumériques par des tirets
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);

        // Supprimer les tirets en début/fin
        $slug = trim($slug, '-');

        // Tronquer
        if (strlen($slug) > $maxLength) {
            $slug = substr($slug, 0, $maxLength);
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }
}
?>
```

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
