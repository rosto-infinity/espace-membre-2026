---
source_course: "php-modern-features"
source_lesson: "php-modern-features-special-types"
---

# Les Types Spéciaux en PHP 8

PHP 8 a introduit et affiné des types spéciaux pour des déclarations de types plus précises.

## Le Type `mixed` (PHP 8.0+)

Accepte n'importe quelle valeur — équivalent à `array|bool|callable|int|float|null|object|resource|string` :

```php
<?php
function debug(mixed $value): void {
    var_dump($value);
}

debug(42);           // OK
debug('bonjour');    // OK
debug([1, 2, 3]);    // OK
debug(null);         // OK

// Contrairement à l'absence de type, `mixed` est explicite
function process(mixed $data): mixed {
    // On dit explicitement "tout est accepté"
    return $data;
}
```

## Le Type de Retour `void`

Indique qu'une fonction ne retourne rien :

```php
<?php
function logMessage(string $message): void {
    file_put_contents('log.txt', $message, FILE_APPEND);
    // Pas de return, ou juste 'return;'
}

// Ne peut PAS retourner une valeur !
function invalid(): void {
    return 'oops';  // Erreur !
}
```

## Le Type de Retour `never` (PHP 8.1+)

Indique qu'une fonction ne termine **jamais** normalement (lance toujours une exception ou appelle `exit`) :

```php
<?php
function abort(string $message): never {
    throw new RuntimeException($message);
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function notImplemented(): never {
    throw new LogicException('Pas encore implémenté');
}

// Très utile pour l'analyse statique
function processOrFail(mixed $data): string {
    if (!is_string($data)) {
        abort('Type de données invalide');  // never retourne
    }

    return $data;  // L'analyseur sait que $data est forcément string ici
}
```

## void vs never

| Type    | Retourne | Termine Normalement                        |
| ------- | -------- | ------------------------------------------ |
| `void`  | Rien     | ✅ Oui                                     |
| `never` | Rien     | ❌ Non (lance toujours une exception/sort) |

## Le Type `null` (PHP 8.2+)

Peut être utilisé comme type autonome :

```php
<?php
class NullLogger {
    public function log(string $message): null {
        // Ne fait rien, retourne null explicitement
        return null;
    }
}
```

## Les Types `true` et `false` (PHP 8.2+)

```php
<?php
function alwaysTrue(): true {
    return true;
}

function alwaysFalse(): false {
    return false;
}

// Utile pour les méthodes qui ne peuvent pas échouer
interface ConnectionPool {
    // Retourne true en cas de succès, jamais false
    public function release(Connection $conn): true;
}
```

## Exemple Concret

**Un Routeur utilisant `void` et `never`**

```php
<?php
declare(strict_types=1);

// Utilisation pratique des types spéciaux
class Router {
    private array $routes = [];

    public function add(string $path, callable $handler): void {
        $this->routes[$path] = $handler;
    }

    public function dispatch(string $path): mixed {
        $handler = $this->routes[$path] ?? null;

        if ($handler === null) {
            $this->notFound($path);
        }

        return $handler();
    }

    private function notFound(string $path): never {
        http_response_code(404);
        echo "Page introuvable : $path";
        exit;
    }
}

$router = new Router();
$router->add('/', fn() => 'Accueil');
$router->add('/about', fn() => 'À Propos');
?>
```

## Les Grimoires

- [Déclarations de Types de Retour (Documentation Officielle)](https://www.php.net/manual/en/functions.returning-values.php#functions.returning-values.type-declaration)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
