---
source_course: "php-essentials"
source_lesson: "php-essentials-conditional-statements"
---

# Instructions Conditionnelles

Les instructions conditionnelles permettent à votre code de prendre des décisions et d'exécuter différents blocs selon des conditions.

## L'instruction `if`

La forme la plus simple de condition :

```php
<?php
$age = 18;

if ($age >= 18) {
    echo "Vous êtes majeur.";
}
```

## L'instruction `if-else`

Gérer deux résultats possibles :

```php
<?php
$score = 75;

if ($score >= 60) {
    echo "Vous avez réussi !";
} else {
    echo "Vous avez échoué.";
}
```

## La chaîne `if-elseif-else`

Gérer plusieurs conditions :

```php
<?php
$note = 85;

if ($note >= 90) {
    echo "A - Excellent !";
} elseif ($note >= 80) {
    echo "B - Très bien !";
} elseif ($note >= 70) {
    echo "C - Satisfaisant";
} elseif ($note >= 60) {
    echo "D - À améliorer";
} else {
    echo "F - Insuffisant";
}
```

## L'Opérateur Ternaire

Un raccourci pratique pour les conditions `if-else` simples :

```php
<?php
$age = 20;

// Ternaire : condition ? valeur_si_vrai : valeur_si_faux
$statut = $age >= 18 ? "adulte" : "mineur";

// Équivalent à :
if ($age >= 18) {
    $statut = "adulte";
} else {
    $statut = "mineur";
}
```

## L'Opérateur Null Coalescing (`??`)

Retourne la première valeur non-null :

```php
<?php
$nomUtilisateur = $_GET['user'] ?? 'Invité';
// Si $_GET['user'] est null/indéfini, utilise 'Invité'

// On peut enchaîner plusieurs :
$nom = $prenom ?? $surnom ?? 'Anonyme';
```

## Assignation Null Coalescing (`??=`)

N'affecte que si la variable est null (PHP 7.4+) :

```php
<?php
$config['timeout'] ??= 30;
// Affecte 30 seulement si $config['timeout'] est null
```

## Opérateurs de Comparaison

| Opérateur | Description                  | Exemple             |
| --------- | ---------------------------- | ------------------- |
| `==`      | Valeur égale                 | `5 == "5"` → true   |
| `===`     | Identique (valeur & type)    | `5 === "5"` → false |
| `!=`      | Différent                    | `5 != 3` → true     |
| `!==`     | Non identique                | `5 !== "5"` → true  |
| `<>`      | Différent (alt.)             | `5 <> 3` → true     |
| `<`       | Inférieur à                  | `3 < 5` → true      |
| `>`       | Supérieur à                  | `5 > 3` → true      |
| `<=`      | Inférieur ou égal            | `3 <= 3` → true     |
| `>=`      | Supérieur ou égal            | `5 >= 5` → true     |
| `<=>`     | Vaisseau spatial (spaceship) | `1 <=> 2` → -1      |

## Exemples de code

**Logique conditionnelle pour l'authentification utilisateur**

```php
<?php
// Exemple concret : vérification de l'authentification
$utilisateur = $_SESSION['user'] ?? null;
$role = $utilisateur['role'] ?? 'invité';

if ($role === 'admin') {
    echo "Bienvenue, Administrateur !";
    // Afficher le tableau de bord admin
} elseif ($role === 'membre') {
    echo "Bienvenue de retour, Membre !";
    // Afficher l'espace membre
} else {
    echo "Veuillez vous connecter pour continuer.";
    // Afficher le formulaire de connexion
}

// Utilisation du ternaire pour la logique d'affichage
$salutation = $utilisateur ? "Bonjour, {$utilisateur['nom']}" : "Bonjour, Invité";
echo $salutation;
?>
```

## Ressources

- [Structures de Contrôle PHP](https://www.php.net/manual/fr/language.control-structures.php) — Guide officiel de toutes les structures de contrôle PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
