---
source_course: "php-essentials"
source_lesson: "php-essentials-variable-scope"
---

# La Portée des Variables en PHP

La portée (scope) détermine où une variable peut être utilisée. La comprendre évite des bugs et aide à organiser le code.

## Portée Locale

Les variables déclarées à l'intérieur d'une fonction sont locales :

```php
<?php
function test() {
    $varLocale = "Je suis locale !";
    echo $varLocale; // Fonctionne
}

test();
echo $varLocale; // Erreur ! Non définie en dehors de la fonction
```

## Portée Globale

Les variables déclarées en dehors des fonctions ont une portée globale :

```php
<?php
$varGlobale = "Je suis globale !";

function test() {
    echo $varGlobale; // Erreur ! Non accessible directement
}
```

## Accéder aux Variables Globales (À utiliser avec parcimonie)

### Avec le mot-clé `global` :

```php
<?php
$compteur = 0;

function incrementer() {
    global $compteur; // Référencer la variable globale
    $compteur++;
}

incrementer();
echo $compteur; // 1
```

### Avec le tableau `$GLOBALS` :

```php
<?php
$nom = "Jean";

function saluer() {
    echo "Bonjour, " . $GLOBALS['nom'];
}

saluer(); // Bonjour, Jean
```

> **Attention** : Les variables globales rendent le code plus difficile à tester et à maintenir. Préférez toujours passer des paramètres !

## Variables Statiques

Conservent leur valeur entre les appels de fonction :

```php
<?php
function compterAppels() {
    static $count = 0; // Initialisée une seule fois
    $count++;
    return $count;
}

echo compterAppels(); // 1
echo compterAppels(); // 2
echo compterAppels(); // 3
```

## Bonne Pratique : Passer des Paramètres

Plutôt que des globales, transmettez ce dont vous avez besoin :

```php
<?php
// Mauvaise pratique : utiliser une globale
$config = ['debug' => true];

function journaliser($message) {
    global $config;
    if ($config['debug']) {
        echo $message;
    }
}

// Bonne pratique : passer en paramètre
function journaliser(string $message, bool $debug = false): void {
    if ($debug) {
        echo $message;
    }
}
```

## Ressources

- [Portée des Variables](https://www.php.net/manual/fr/language.variables.scope.php) — Documentation officielle sur la portée des variables PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
