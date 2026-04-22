---
source_course: "php-essentials"
source_lesson: "php-essentials-input-validation"
---

# Validation & Assainissement des Entrées

**Ne faites jamais confiance aux données saisies par l'utilisateur !** Validez et assainissez toujours les données avant de les utiliser.

## Validation vs Assainissement

- **Validation** : Vérifier si les données respectent les exigences (rejeter si invalide).
- **Assainissement (Sanitization)** : Nettoyer/modifier les données pour les rendre sûres.

## Fonctions de Filtre PHP

```php
<?php
// Valider - retourne false si invalide
$email  = filter_var($input, FILTER_VALIDATE_EMAIL);
$url    = filter_var($input, FILTER_VALIDATE_URL);
$entier = filter_var($input, FILTER_VALIDATE_INT);
$float  = filter_var($input, FILTER_VALIDATE_FLOAT);
$bool   = filter_var($input, FILTER_VALIDATE_BOOL);

// Avec des options
$age = filter_var($input, FILTER_VALIDATE_INT, [
    'options' => ['min_range' => 1, 'max_range' => 120]
]);
```

## Filtres d'Assainissement

```php
<?php
// Assainir - nettoie les données
$email   = filter_var($input, FILTER_SANITIZE_EMAIL);
$chaine  = filter_var($input, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$entier  = filter_var($input, FILTER_SANITIZE_NUMBER_INT);
$url     = filter_var($input, FILTER_SANITIZE_URL);
```

## Filtrer les Superglobales Directement

```php
<?php
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$page  = filter_input(INPUT_GET,  'page',  FILTER_VALIDATE_INT);
```

## Prévenir les Failles XSS (Cross-Site Scripting)

```php
<?php
$saisieUtilisateur = '<script>alert("XSS")</script>';

// DANGEREUX :
echo $saisieUtilisateur; // Exécute le JavaScript !

// SÉCURISÉ :
echo htmlspecialchars($saisieUtilisateur, ENT_QUOTES, 'UTF-8');
// Affiche: &lt;script&gt;alert("XSS")&lt;/script&gt;
```

## Modèles de Validation Courants

```php
<?php
function validerFormulaire(array $data): array {
    $erreurs = [];

    // Champ requis
    if (empty($data['nom'])) {
        $erreurs['nom'] = 'Le nom est requis';
    } elseif (strlen($data['nom']) < 2) {
        $erreurs['nom'] = 'Le nom doit contenir au moins 2 caractères';
    }

    // Validation d'email
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $erreurs['email'] = 'Adresse email invalide';
    }

    // Robustesse du mot de passe
    if (strlen($data['motdepasse']) < 8) {
        $erreurs['motdepasse'] = 'Le mot de passe doit contenir au moins 8 caractères';
    }

    // Plage numérique
    $age = filter_var($data['age'], FILTER_VALIDATE_INT);
    if ($age === false || $age < 18 || $age > 120) {
        $erreurs['age'] = "L'âge doit être compris entre 18 et 120";
    }

    return $erreurs;
}
```

## Validation par Liste Blanche

```php
<?php
$couleurs_autorisees = ['rouge', 'vert', 'bleu'];
$couleur = $_POST['couleur'] ?? '';

if (!in_array($couleur, $couleurs_autorisees, true)) {
    $erreur = 'Couleur invalide sélectionnée';
}
```

## Exemples de code

**Classe de validation fluide pour les formulaires**

```php
<?php
class Validateur {
    private array $erreurs = [];

    public function requis(string $champ, mixed $valeur): self {
        if (empty(trim($valeur))) {
            $this->erreurs[$champ] = ucfirst($champ) . ' est requis';
        }
        return $this;
    }

    public function email(string $champ, string $valeur): self {
        if (!filter_var($valeur, FILTER_VALIDATE_EMAIL)) {
            $this->erreurs[$champ] = 'Format email invalide';
        }
        return $this;
    }

    public function longueurMin(string $champ, string $valeur, int $min): self {
        if (strlen($valeur) < $min) {
            $this->erreurs[$champ] = "$champ doit faire au moins $min caractères";
        }
        return $this;
    }

    public function estValide(): bool {
        return empty($this->erreurs);
    }

    public function getErreurs(): array {
        return $this->erreurs;
    }
}

// Utilisation
$validateur = new Validateur();
$validateur
    ->requis('nom',   $_POST['nom']       ?? '')
    ->email('email',  $_POST['email']     ?? '')
    ->longueurMin('motdepasse', $_POST['motdepasse'] ?? '', 8);

if ($validateur->estValide()) {
    // Traiter le formulaire
} else {
    $erreurs = $validateur->getErreurs();
}
?>
```

## Ressources

- [Extension Filtre PHP](https://www.php.net/manual/fr/book.filter.php) — Documentation de l'extension PHP Filter
- [Types de Filtres](https://www.php.net/manual/fr/filter.filters.php) — Filtres disponibles et leurs options

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
