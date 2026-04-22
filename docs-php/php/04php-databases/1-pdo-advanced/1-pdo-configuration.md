---
source_course: "php-databases"
source_lesson: "php-databases-pdo-configuration"
---

# Configuration PDO & Bonnes Pratiques

PDO (PHP Data Objects) fournit une **interface cohérente pour l'accès aux bases de données**. Une configuration correcte est essentielle pour la sécurité et la fiabilité.

## Configuration de la Connexion

```php
<?php
$dsn = 'mysql:host=localhost;dbname=myapp;charset=utf8mb4';
$options = [
    // Lever des exceptions en cas d'erreur
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,

    // Retourner des tableaux associatifs par défaut
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

    // Utiliser de vraies requêtes préparées (pas émulées)
    PDO::ATTR_EMULATE_PREPARES => false,

    // Retourner les chaînes comme des strings PHP, pas des LOB
    PDO::ATTR_STRINGIFY_FETCHES => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Ne pas exposer les détails de connexion
    error_log($e->getMessage());
    throw new RuntimeException('Connexion à la base de données échouée');
}
```

## Pourquoi ATTR_EMULATE_PREPARES Doit Être à False

```php
<?php
// Avec les requêtes préparées émulées (par défaut)
// PHP construit la requête, puis l'envoie comme une seule chaîne
// Moins sécurisé, problèmes potentiels de types

// Avec de vraies requêtes préparées
// Le modèle de requête est envoyé d'abord, puis les paramètres séparément
// La BDD applique les types, meilleure sécurité

// Exemple : Gestion des entiers
$id = '1 OR 1=1';

// Émulé : Peut permettre des attaques par manipulation de type
// Réel : La BDD rejette une non-entier pour une colonne INT
```

## Fabrique de Base de Données

```php
<?php
class DatabaseFactory {
    private static ?PDO $instance = null;

    public static function create(): PDO {
        if (self::$instance === null) {
            $config = require 'config/database.php';

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                $config['driver'],
                $config['host'],
                $config['port'],
                $config['database']
            );

            self::$instance = new PDO(
                $dsn,
                $config['username'],
                $config['password'],
                self::getOptions()
            );
        }

        return self::$instance;
    }

    private static function getOptions(): array {
        return [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_FOUND_ROWS => true,
        ];
    }
}
```

## Connexion PostgreSQL

```php
<?php
$dsn = 'pgsql:host=localhost;dbname=myapp';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $username, $password, $options);

// Spécifique à PostgreSQL : Définir le schema
$pdo->exec('SET search_path TO myschema');
```

## Les Grimoires

- [Manuel PDO (Documentation Officielle)](https://www.php.net/manual/en/book.pdo.php)

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
