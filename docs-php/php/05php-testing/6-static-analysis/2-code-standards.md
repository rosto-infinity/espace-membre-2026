---
source_course: "php-testing"
source_lesson: "php-testing-code-standards"
---

# Standards de Code avec PHP_CodeSniffer

PHP_CodeSniffer **applique des standards de codage cohérents** dans tout votre projet.

## Installation

```bash
composer require --dev squizlabs/php_codesniffer
```

## Utilisation de Base

```bash
# Vérifier le code
./vendor/bin/phpcs src/

# Corriger automatiquement
./vendor/bin/phpcbf src/

# Standard spécifique
./vendor/bin/phpcs --standard=PSR12 src/
```

## Configuration (phpcs.xml)

```xml
<?xml version="1.0"?>
<ruleset name="MonProjet">
    <description>Standard de codage pour MonProjet</description>

    <file>src</file>
    <file>tests</file>

    <exclude-pattern>*/vendor/*</exclude-pattern>
    <exclude-pattern>*/cache/*</exclude-pattern>

    <!-- Utiliser PSR-12 comme base -->
    <rule ref="PSR12"/>

    <!-- Règles personnalisées -->
    <rule ref="Generic.Files.LineLength">
        <properties>
            <property name="lineLimit" value="120"/>
            <property name="absoluteLineLimit" value="150"/>
        </properties>
    </rule>

    <!-- Exclure des règles spécifiques -->
    <rule ref="PSR12.Files.FileHeader.SpacingAfterBlock">
        <exclude-pattern>*/tests/*</exclude-pattern>
    </rule>
</ruleset>
```

## Standards Courants

- **PSR-1** : Standard de codage de base
- **PSR-12** : Style de codage étendu (recommandé)
- **Squiz** : Standard complet
- **PEAR** : Standard legacy

## Exemples de Violations

```php
<?php
// Violation : Accolade ouvrante sur la mauvaise ligne (PSR-12)
class User
{ // Doit être sur la même ligne
    // Violation : Visibilité manquante
    function getName() { return $this->name; }

    // Violation : Espacement inconsistant
    public function setName( string $name ):void {
        $this->name=$name;  // Espaces manquants autour de =
    }
}

// Corrigé :
class User {
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
```

## Combiner les Outils

```json
// composer.json scripts
{
  "scripts": {
    "test": "phpunit",
    "analyse": "phpstan analyse",
    "cs-check": "phpcs",
    "cs-fix": "phpcbf",
    "quality": ["@cs-check", "@analyse", "@test"]
  }
}
```

```bash
composer quality  # Exécuter toutes les vérifications
```

## Exemple Complet

**Script de qualité combinant tous les outils d'analyse**

```php
<?php
declare(strict_types=1);

class QualityGate
{
    private array $results = [];

    public function run(): int
    {
        $this->check('Syntaxe PHP', $this->checkSyntax());
        $this->check('Style de Code (PHPCS)', $this->runPhpcs());
        $this->check('Analyse Statique (PHPStan)', $this->runPhpstan());
        $this->check('Tests Unitaires (PHPUnit)', $this->runTests());

        $this->printResults();

        return $this->hasFailures() ? 1 : 0;
    }

    private function checkSyntax(): bool
    {
        exec('find src tests -name "*.php" -exec php -l {} \; 2>&1', $output, $code);
        return $code === 0;
    }

    private function runPhpcs(): bool
    {
        exec('./vendor/bin/phpcs --report=summary 2>&1', $output, $code);
        return $code === 0;
    }

    private function runPhpstan(): bool
    {
        exec('./vendor/bin/phpstan analyse --no-progress 2>&1', $output, $code);
        return $code === 0;
    }

    private function runTests(): bool
    {
        exec('./vendor/bin/phpunit --testdox 2>&1', $output, $code);
        return $code === 0;
    }

    private function check(string $name, bool $passed): void
    {
        $this->results[$name] = $passed;
    }

    private function printResults(): void
    {
        echo "\n=== Résultats du Contrôle Qualité ===\n";
        foreach ($this->results as $name => $passed) {
            $status = $passed ? '✅ SUCCÈS' : '❌ ÉCHEC';
            echo "$status: $name\n";
        }
    }

    private function hasFailures(): bool
    {
        return in_array(false, $this->results, true);
    }
}

// Exécuter: php quality-gate.php
$gate = new QualityGate();
exit($gate->run());
?>
```

## Les Grimoires

- [PHP_CodeSniffer (GitHub)](https://github.com/squizlabs/PHP_CodeSniffer)

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
