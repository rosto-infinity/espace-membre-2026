---
source_course: "php-testing"
source_lesson: "php-testing-first-test"
---

# Écrire Votre Premier Test

PHPUnit est le **framework de test standard de PHP**. Les tests vérifient que votre code fonctionne comme prévu.

## Installation de PHPUnit

```bash
composer require --dev phpunit/phpunit
```

## Structure d'un Test

```php
<?php
// tests/CalculatorTest.php
namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Calculator;

class CalculatorTest extends TestCase
{
    public function testAddTwoNumbers(): void
    {
        // Arrange : Configurer le test
        $calculator = new Calculator();

        // Act : Effectuer l'action
        $result = $calculator->add(2, 3);

        // Assert : Vérifier le résultat
        $this->assertEquals(5, $result);
    }
}
```

## La Classe Testée

```php
<?php
// src/Calculator.php
namespace App;

class Calculator
{
    public function add(int $a, int $b): int
    {
        return $a + $b;
    }

    public function subtract(int $a, int $b): int
    {
        return $a - $b;
    }

    public function divide(int $a, int $b): float
    {
        if ($b === 0) {
            throw new \DivisionByZeroError('Impossible de diviser par zéro');
        }
        return $a / $b;
    }
}
```

## Exécuter les Tests

```bash
# Exécuter tous les tests
./vendor/bin/phpunit

# Exécuter un fichier de test spécifique
./vendor/bin/phpunit tests/CalculatorTest.php

# Exécuter une méthode de test spécifique
./vendor/bin/phpunit --filter testAddTwoNumbers

# Sortie verbeuse
./vendor/bin/phpunit -v
```

## Configuration PHPUnit

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
</phpunit>
```

## Conventions de Nommage des Tests

```php
<?php
// Méthodes commençant par 'test'
public function testUserCanBeCreated(): void {}

// Ou en utilisant l'annotation @test
/** @test */
public function user_can_be_created(): void {}

// En utilisant l'attribut #[Test] (PHP 8)
#[Test]
public function userCanBeCreated(): void {}
```

## Les Grimoires

- [Documentation PHPUnit (Officielle)](https://docs.phpunit.de/en/11.4/)

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
