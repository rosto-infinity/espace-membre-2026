---
source_course: "php-essentials"
source_lesson: "php-essentials-first-php-script"
---

# Écrire votre premier script PHP

C'est parti pour votre premier programme PHP ! Le code PHP est inséré dans des balises spéciales qui indiquent au serveur de traiter le code.

## Les Balises PHP

Le code PHP doit être entouré de balises d'ouverture et de fermeture :

```php
<?php
// Votre code PHP ici
?>
```

**Important** : La balise de fermeture `?>` est optionnelle (et souvent omise) dans les fichiers ne contenant que du PHP. Cela évite d'ajouter accidentellement des espaces blancs en fin de fichier qui pourraient causer des erreurs.

## L'instruction Echo

L'instruction `echo` permet d'afficher du texte dans le navigateur :

```php
<?php
echo "Bonjour le monde !";
?>
```

## Intégrer du PHP dans du HTML

PHP brille par sa capacité à se mélanger avec le HTML :

```php
<!DOCTYPE html>
<html>
<head>
    <title>Ma première page PHP</title>
</head>
<body>
    <h1><?php echo "Bienvenue sur ma page PHP !"; ?></h1>
    <p>Nous sommes en l'an : <?php echo date("Y"); ?></p>
</body>
</html>
```

## Syntaxe courte pour Echo

Pour un affichage simple au sein du HTML (sans logique complexe), vous pouvez utiliser la syntaxe courte `<?=` :

```php
<p>Heure actuelle : <?= date("H:i:s") ?></p>
<!-- Équivalent à : <?php echo date("H:i:s"); ?> -->
```

## Commentaires en PHP

PHP supporte trois styles de commentaires :

```php
<?php
// Commentaire sur une seule ligne

# Autre commentaire sur une seule ligne (style shell)

/*
  Commentaire sur plusieurs lignes
  pour des explications plus longues
*/

/**
 * Commentaire DocBlock
 * Utilisé pour la documentation technique
 * @param string $nom Le nom de l'utilisateur
 */
```

## Sensibilité à la Casse (Case Sensitivity)

- **Mots-clés** (`if`, `else`, `echo`, `class`) : **Insensibles** à la casse (`ECHO` = `echo`).
- **Noms de variables** : **Sensibles** à la casse (`$nom` ≠ `$Nom`).

```php
<?php
ECHO "Cela fonctionne !"; // Les mots-clés sont insensibles
$nom = "Alice";
echo $Nom; // Erreur ! Les variables SONT sensibles à la casse
```

## Exemples de code

**Un script PHP simple qui affiche du texte et la date du jour**

```php
<?php
// hello.php - Votre premier script PHP
echo "Bonjour le monde !";
echo "<br>";
echo "La date actuelle est : " . date("d/m/Y");
?>
```

## Ressources

- [Syntaxe de base PHP](https://www.php.net/manual/fr/language.basic-syntax.php) — Guide officiel sur les règles de base de la syntaxe PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
