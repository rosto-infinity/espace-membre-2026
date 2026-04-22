---
source_course: "php-modern-features"
source_lesson: "php-modern-features-union-types"
---

# Les Types Union (PHP 8.0+)

Les types union permettent à une valeur d'être de **plusieurs types possibles**. Cela apporte plus de flexibilité tout en maintenant la sécurité des types.

## La Syntaxe de Base

```php
<?php
function processId(int|string $id): void {
    if (is_int($id)) {
        echo "ID entier : $id";
    } else {
        echo "ID chaîne : $id";
    }
}

processId(42);       // ID entier : 42
processId("ABC123"); // ID chaîne : ABC123
```

## Types Union sur les Propriétés

```php
<?php
class ApiResponse {
    public int|string $id;
    public array|object $data;
    public string|null $error;
}
```

## Types de Retour Union

```php
<?php
function findUser(int $id): User|null {
    // Retourne User ou null
    return $this->users[$id] ?? null;
}

function getValue(string $key): string|int|float|bool {
    // Peut retourner plusieurs types scalaires
    return $this->config[$key];
}
```

## Le Pseudo-Type `false`

PHP 8.0 autorise `false` comme membre d'un type union (pour la compatibilité avec les anciennes APIs) :

```php
<?php
function search(array $haystack, mixed $needle): int|false {
    $index = array_search($needle, $haystack);
    return $index;  // Retourne l'index ou false
}
```

## Le `null` dans les Types Union

```php
<?php
// Ces deux déclarations sont équivalentes :
function example1(?string $name): void {}
function example2(string|null $name): void {}

// Mais la syntaxe union permet plus de types :
function example3(string|int|null $value): void {}
```

## Le Rétrécissement de Types (Type Narrowing)

```php
<?php
function process(int|string|array $data): string {
    // Rétrécissement de type avec des conditions
    if (is_array($data)) {
        return implode(', ', $data);
    }

    if (is_int($data)) {
        return (string) $data;
    }

    return $data;  // Forcément une string ici
}
```

## Types Union avec des Interfaces

```php
<?php
interface Stringable {
    public function __toString(): string;
}

function render(string|Stringable $content): string {
    return (string) $content;
}
```

## Exemple Concret

**Classe de Configuration utilisant les types union**

```php
<?php
declare(strict_types=1);

// Cas réel : Gestionnaire de configuration avec types union
class Config {
    private array $settings = [];

    public function set(string $key, string|int|float|bool|array $value): void {
        $this->settings[$key] = $value;
    }

    public function get(string $key): string|int|float|bool|array|null {
        return $this->settings[$key] ?? null;
    }

    public function getString(string $key): string|null {
        $value = $this->get($key);
        return is_string($value) ? $value : null;
    }
}

$config = new Config();
$config->set('app.name', 'MonApp');
$config->set('app.debug', true);
$config->set('app.max_users', 100);

echo $config->getString('app.name');  // MonApp
?>
```

## Les Grimoires

- [Types Union (RFC Officiel)](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.union)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
