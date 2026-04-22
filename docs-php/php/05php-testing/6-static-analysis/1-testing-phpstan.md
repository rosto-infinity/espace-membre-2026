---
source_course: "php-testing"
source_lesson: "php-testing-phpstan"
---

# PHPStan : Analyse Statique

PHPStan **trouve les bugs sans exécuter le code** en analysant les types et le flux de contrôle.

## Installation

```bash
composer require --dev phpstan/phpstan
```

## Utilisation de Base

```bash
# Analyser le répertoire src
./vendor/bin/phpstan analyse src

# Niveau spécifique (0-9)
./vendor/bin/phpstan analyse src --level=5

# Avec fichier de configuration
./vendor/bin/phpstan analyse -c phpstan.neon
```

## Configuration (phpstan.neon)

```yaml
parameters:
  level: 6
  paths:
    - src
    - tests
  excludePaths:
    - src/Legacy/*
  checkMissingIterableValueType: false
```

## Ce que PHPStan Détecte

```php
<?php
class UserService
{
    public function getUser(int $id): User
    {
        $user = $this->repository->find($id);
        return $user;  // ERREUR: find() retourne ?User, mais la méthode retourne User
    }

    public function processUser(User $user): void
    {
        echo $user->nmae;  // ERREUR: La propriété 'nmae' n'existe pas (faute de frappe !)
    }

    public function calculate(int $value): int
    {
        return $value / 2;  // ERREUR: La division retourne float, pas int
    }
}
```

## Corriger les Problèmes

```php
<?php
class UserService
{
    public function getUser(int $id): ?User  // Correction: Autoriser null
    {
        return $this->repository->find($id);
    }

    // Ou lancer une exception
    public function getUserOrFail(int $id): User
    {
        $user = $this->repository->find($id);
        if ($user === null) {
            throw new UserNotFoundException();
        }
        return $user;  // PHPStan sait que ce n'est pas null ici
    }

    public function calculate(int $value): int
    {
        return (int) ($value / 2);  // Correction: Caster en int
        // Ou utiliser intdiv($value, 2)
    }
}
```

## Niveaux PHPStan

| Niveau | Vérifications                            |
| ------ | ---------------------------------------- |
| 0      | Vérifications de base, classes inconnues |
| 1      | Variables potentiellement indéfinies     |
| 2      | Méthodes inconnues sur types connus      |
| 3      | Types de retour, types de propriétés     |
| 4      | Vérifications de code mort de base       |
| 5      | Types d'arguments                        |
| 6      | Typehints manquants                      |
| 7      | Types union vérifiés                     |
| 8      | Vérifications null, appels sur nullables |
| 9      | Le type mixed est interdit               |

## Les Grimoires

- [Documentation PHPStan (Officielle)](https://phpstan.org/user-guide/getting-started)

---

> 📘 _Cette leçon fait partie du cours [Tests & Assurance Qualité PHP](/php/php-testing/) sur la plateforme d'apprentissage RostoDev._
