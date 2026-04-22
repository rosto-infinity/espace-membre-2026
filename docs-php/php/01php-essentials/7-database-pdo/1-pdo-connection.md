---
source_course: "php-essentials"
source_lesson: "php-essentials-pdo-connection"
---

# Se Connecter aux Bases de Données avec PDO

PDO (PHP Data Objects) est la couche d'abstraction de base de données moderne de PHP. Elle fournit une interface cohérente pour accéder à différents systèmes de bases de données.

## Pourquoi PDO ?

- **Sécurité** : Les requêtes préparées intégrées préviennent les injections SQL.
- **Portabilité** : Le même code fonctionne avec MySQL, PostgreSQL, SQLite, etc.
- **Orienté Objet** : API moderne et propre.
- **Gestion des Erreurs** : Exceptions PHP correctes au lieu de simples avertissements.

## Connexion de Base

```php
<?php
try {
    $pdo = new PDO(
        'mysql:host=localhost;dbname=mon_app;charset=utf8mb4',
        'nom_utilisateur',
        'mot_de_passe'
    );
} catch (PDOException $e) {
    die("Connexion échouée : " . $e->getMessage());
}
```

## Connexion avec Options (Recommandé)

```php
<?php
$dsn      = 'mysql:host=localhost;dbname=mon_app;charset=utf8mb4';
$utilisateur = 'root';
$motdepasse  = 'secret';

$options = [
    // Lancer des exceptions en cas d'erreur
    PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
    // Retourner des tableaux associatifs par défaut
    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
    // Ne pas émuler les requêtes préparées
    PDO::ATTR_EMULATE_PREPARES    => false,
];

try {
    $pdo = new PDO($dsn, $utilisateur, $motdepasse, $options);
} catch (PDOException $e) {
    throw new PDOException($e->getMessage(), (int) $e->getCode());
}
```

## Format DSN pour Différentes Bases de Données

```php
<?php
// MySQL / MariaDB
$dsn = 'mysql:host=localhost;dbname=mon_app;charset=utf8mb4';

// PostgreSQL
$dsn = 'pgsql:host=localhost;dbname=mon_app';

// SQLite (fichier)
$dsn = 'sqlite:/chemin/vers/base.db';

// SQLite (en mémoire)
$dsn = 'sqlite::memory:';
```

## Modèle : Classe de Connexion (Singleton)

```php
<?php
class BaseDeDonnees {
    private static ?PDO $instance = null;

    public static function getConnexion(): PDO {
        if (self::$instance === null) {
            $dsn = 'mysql:host=localhost;dbname=app;charset=utf8mb4';
            self::$instance = new PDO($dsn, 'user', 'pass', [
                PDO::ATTR_ERRMODE             => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
            ]);
        }
        return self::$instance;
    }
}

// Utilisation
$pdo = BaseDeDonnees::getConnexion();
```

## Exemples de code

**Classe de connexion prête pour la production**

```php
<?php
declare(strict_types=1);

class BaseDeDonnees {
    private PDO $pdo;

    public function __construct(
        string $hote     = 'localhost',
        string $base     = '',
        string $user     = '',
        string $mdp      = '',
        int    $port     = 3306
    ) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $hote, $port, $base
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ];

        $this->pdo = new PDO($dsn, $user, $mdp, $options);
    }

    public function getConnexion(): PDO {
        return $this->pdo;
    }
}

// Utilisation avec variables d'environnement
$db = new BaseDeDonnees(
    hote: $_ENV['DB_HOST'] ?? 'localhost',
    base: $_ENV['DB_NAME'] ?? 'mon_app',
    user: $_ENV['DB_USER'] ?? 'root',
    mdp:  $_ENV['DB_PASS'] ?? ''
);
?>
```

## Ressources

- [Introduction à PDO](https://www.php.net/manual/fr/intro.pdo.php) — Introduction et vue d'ensemble officielle de PDO
- [Connexions PDO](https://www.php.net/manual/fr/pdo.connections.php) — Guide complet sur la gestion des connexions PDO

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
