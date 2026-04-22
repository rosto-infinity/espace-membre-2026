---
source_course: "php-security"
source_lesson: "php-security-validation-basics"
---

# Les Fondamentaux de la Validation des Entrées

**Ne jamais faire confiance aux entrées utilisateur.** C'est la règle d'or de la sécurité web. Chaque donnée provenant des utilisateurs doit être validée.

## Validation vs Assainissement

- **Validation** : Vérifier si les données répondent aux exigences (rejeter si invalide)
- **Assainissement** : Nettoyer/modifier les données pour les rendre sûres

```php
<?php
// Validation - rejeter les mauvaises données
$email = $_POST['email'];
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    throw new ValidationException('Format d\'email invalide');
}

// Assainissement - nettoyer les données
$email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
```

## Fonctions de Filtre

### Filtres de Validation

```php
<?php
// Retourne la valeur filtrée ou false si invalide
filter_var($email, FILTER_VALIDATE_EMAIL);
filter_var($url, FILTER_VALIDATE_URL);
filter_var($ip, FILTER_VALIDATE_IP);
filter_var($int, FILTER_VALIDATE_INT);
filter_var($float, FILTER_VALIDATE_FLOAT);
filter_var($bool, FILTER_VALIDATE_BOOL);

// Avec des options
$age = filter_var($input, FILTER_VALIDATE_INT, [
    'options' => [
        'min_range' => 0,
        'max_range' => 120
    ]
]);

// Directement depuis les superglobales
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$search = filter_input(INPUT_POST, 'search', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
```

### Filtres d'Assainissement

```php
<?php
// Supprimer/encoder les caractères dangereux
filter_var($input, FILTER_SANITIZE_EMAIL);
filter_var($input, FILTER_SANITIZE_URL);
filter_var($input, FILTER_SANITIZE_NUMBER_INT);
filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);  // Encoder HTML
```

## Validation par Liste Blanche

Toujours préférer la liste blanche à la liste noire :

```php
<?php
// MAUVAIS : Liste noire (incomplète)
$forbidden = ['<script>', 'javascript:', 'onerror'];
if (str_contains_any($input, $forbidden)) {
    // Les attaquants peuvent contourner ça !
}

// BON : Liste blanche (valeurs explicitement autorisées)
$allowedStatuses = ['pending', 'approved', 'rejected'];
$status = $_POST['status'];

if (!in_array($status, $allowedStatuses, true)) {
    throw new ValidationException('Statut invalide');
}

// BON : Correspondance de pattern pour les formats
if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    throw new ValidationException('Format de nom d\'utilisateur invalide');
}
```

## Dangers de la Coercition de Types

```php
<?php
// La comparaison lâche de PHP est dangereuse
$password = $_POST['password'];

// MAUVAIS : Attaque par jonglage de types
if ($password == $storedPassword) { /* vulnérable */ }

// BON : Comparaison stricte
if ($password === $storedPassword) { /* sûr */ }

// Mieux : Utiliser password_verify()
if (password_verify($password, $hash)) { /* meilleur */ }
```

## Exemple Concret

**Classe de validation d'entrées complète**

```php
<?php
declare(strict_types=1);

class InputValidator {
    private array $errors = [];
    private array $validated = [];

    public function validate(array $rules, array $data): bool {
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule => $param) {
                if (!$this->applyRule($field, $value, $rule, $param)) {
                    break; // Arrêter à la première erreur pour ce champ
                }
            }

            if (!isset($this->errors[$field])) {
                $this->validated[$field] = $value;
            }
        }

        return empty($this->errors);
    }

    private function applyRule(string $field, mixed $value, string $rule, mixed $param): bool {
        return match($rule) {
            'required' => $this->validateRequired($field, $value),
            'email' => $this->validateEmail($field, $value),
            'min' => $this->validateMin($field, $value, $param),
            'max' => $this->validateMax($field, $value, $param),
            'in' => $this->validateIn($field, $value, $param),
            'regex' => $this->validateRegex($field, $value, $param),
            default => true,
        };
    }

    private function validateRequired(string $field, mixed $value): bool {
        if (empty($value) && $value !== '0') {
            $this->errors[$field] = "$field est requis";
            return false;
        }
        return true;
    }

    private function validateEmail(string $field, mixed $value): bool {
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = "$field doit être un email valide";
            return false;
        }
        return true;
    }

    private function validateIn(string $field, mixed $value, array $allowed): bool {
        if ($value && !in_array($value, $allowed, true)) {
            $this->errors[$field] = "$field contient une valeur invalide";
            return false;
        }
        return true;
    }

    public function getErrors(): array { return $this->errors; }
    public function getValidated(): array { return $this->validated; }
}

// Utilisation
$validator = new InputValidator();
$valid = $validator->validate([
    'email' => ['required' => true, 'email' => true],
    'status' => ['required' => true, 'in' => ['active', 'inactive']],
    'age' => ['min' => 18, 'max' => 120],
], $_POST);

if (!$valid) {
    $errors = $validator->getErrors();
}
?>
```

## Les Grimoires

- [Fonctions de Filtre (Documentation Officielle)](https://www.php.net/manual/en/book.filter.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
