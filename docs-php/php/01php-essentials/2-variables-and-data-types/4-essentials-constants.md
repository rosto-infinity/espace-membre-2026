---
source_course: "php-essentials"
source_lesson: "php-essentials-constants"
---

# Les Constantes en PHP

Les constantes sont comme des variables, mais leur valeur ne peut pas changer une fois définie. Elles sont idéales pour les valeurs de configuration et les données fixes.

## Définir des Constantes

### Avec `const` (Préféré en PHP 8+)

```php
<?php
const NOM_APP = "Mon Application";
const VERSION = "2.0";
const MAX_UTILISATEURS = 100;
const FONCTIONNALITES = ["auth", "api", "admin"]; // Les tableaux sont autorisés
```

### Avec la Fonction `define()`

```php
<?php
define("NOM_APP", "Mon Application");
define("MODE_DEBUG", true);

// Définition conditionnelle (uniquement possible avec define())
if (!defined("MAX_TENTATIVES")) {
    define("MAX_TENTATIVES", 3);
}
```

## `const` vs `define()`

| Fonctionnalité        | `const`     | `define()`          |
| --------------------- | ----------- | ------------------- |
| Portée                | Compilation | Exécution (runtime) |
| Conditionnel          | Non         | Oui                 |
| Dans les classes      | Oui         | Non                 |
| Insensible à la casse | Non         | Non (déprécié)      |
| Vitesse               | Plus rapide | Plus lent           |

## Constantes de Classe

```php
<?php
class StatutHTTP {
    public const OK = 200;
    public const NON_TROUVE = 404;
    public const ERREUR_SERVEUR = 500;

    private const SECRET = "cache"; // Contrôle de visibilité
}

echo StatutHTTP::OK; // 200
```

## Constantes Typées (PHP 8.3+)

```php
<?php
class Config {
    public const string NOM_APP = "MonApp";
    public const int TAILLE_MAX = 1024;
    public const array AUTORISES = ["a", "b"];
}
```

## Constantes Magiques

PHP fournit des constantes spéciales dont la valeur change en fonction du contexte :

```php
<?php
echo __FILE__;       // Chemin complet vers le fichier actuel
echo __DIR__;        // Répertoire du fichier actuel
echo __LINE__;       // Numéro de ligne actuel
echo __FUNCTION__;   // Nom de la fonction
echo __CLASS__;      // Nom de la classe
echo __METHOD__;     // Nom de la méthode de la classe
echo __NAMESPACE__;  // Espace de noms actuel
```

## Bonnes Pratiques

1. Utilisez `MAJUSCULES_AVEC_UNDERSCORES` pour les noms de constantes.
2. Regroupez les constantes liées dans des classes.
3. Préférez `const` pour les valeurs simples, `define()` pour les cas conditionnels.
4. Privilégiez les constantes de classe aux constantes globales.

## Exemples de code

**Organisation des constantes dans une application PHP**

```php
<?php
// Constantes de configuration
const DB_HOTE = "localhost";
const DB_NOM = "mon_app";

// Constantes d'application dans une classe
class App {
    public const VERSION = "1.0.0";
    public const ENV = "development";

    public const ENVIRONNEMENTS = [
        "development",
        "staging",
        "production"
    ];
}

echo "Version de l'App : " . App::VERSION;
echo "Connecté à : " . DB_HOTE;
?>
```

## Ressources

- [Constantes PHP](https://www.php.net/manual/fr/language.constants.php) — Documentation officielle des constantes PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
