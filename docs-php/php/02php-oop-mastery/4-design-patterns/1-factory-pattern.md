---
source_course: "php-oop-mastery"
source_lesson: "php-oop-mastery-factory-pattern"
---

# Le Pattern Fabrique (Factory Pattern)

Le pattern Fabrique **crée des objets sans spécifier leur classe exacte**. Il centralise la logique de création d'objets.

## La Fabrique Simple (Simple Factory)

```php
<?php
interface Logger {
    public function log(string $message): void;
}

class FileLogger implements Logger {
    public function log(string $message): void {
        file_put_contents('app.log', $message . "\n", FILE_APPEND);
    }
}

class DatabaseLogger implements Logger {
    public function log(string $message): void {
        // Sauvegarder en base de données
    }
}

class ConsoleLogger implements Logger {
    public function log(string $message): void {
        echo "[LOG] $message\n";
    }
}

class LoggerFactory {
    public static function create(string $type): Logger {
        return match($type) {
            'file' => new FileLogger(),
            'database' => new DatabaseLogger(),
            'console' => new ConsoleLogger(),
            default => throw new InvalidArgumentException("Logger inconnu : $type"),
        };
    }
}

// Utilisation
$logger = LoggerFactory::create('file');
$logger->log('Application démarrée');
```

## Le Pattern Méthode de Fabrique (Factory Method)

```php
<?php
abstract class Document {
    abstract public function createPages(): array;

    public function render(): string {
        $output = '';
        foreach ($this->createPages() as $page) {
            $output .= $page->render();
        }
        return $output;
    }
}

class Resume extends Document {
    public function createPages(): array {
        return [
            new SkillsPage(),
            new ExperiencePage(),
            new EducationPage(),
        ];
    }
}

class Report extends Document {
    public function createPages(): array {
        return [
            new IntroductionPage(),
            new ResultsPage(),
            new ConclusionPage(),
        ];
    }
}
```

## La Fabrique Abstraite (Abstract Factory)

```php
<?php
interface Button {
    public function render(): string;
}

interface Checkbox {
    public function render(): string;
}

// Fabrique Abstraite
interface UIFactory {
    public function createButton(): Button;
    public function createCheckbox(): Checkbox;
}

// Implémentations concrètes
class DarkButton implements Button {
    public function render(): string {
        return '<button class="dark">Cliquer</button>';
    }
}

class DarkCheckbox implements Checkbox {
    public function render(): string {
        return '<input type="checkbox" class="dark">';
    }
}

class DarkUIFactory implements UIFactory {
    public function createButton(): Button {
        return new DarkButton();
    }

    public function createCheckbox(): Checkbox {
        return new DarkCheckbox();
    }
}

// Le code client fonctionne avec n'importe quelle fabrique
function renderForm(UIFactory $factory): string {
    $button = $factory->createButton();
    $checkbox = $factory->createCheckbox();

    return $checkbox->render() . $button->render();
}
```

## Les Grimoires

- [Factory Pattern (exemples PHP sur Refactoring.Guru)](https://refactoring.guru/design-patterns/factory-method/php/example)

---

> 📘 _Cette leçon fait partie du cours [Maîtrise de la POO en PHP](/php/php-oop-mastery/) sur la plateforme d'apprentissage RostoDev._
