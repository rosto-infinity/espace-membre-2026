---
source_course: "php-modern-features"
source_lesson: "php-modern-features-type-narrowing"
---

# Le Rétrécissement de Types & Les Assertions

Le système de types de PHP 8 fonctionne avec l'analyse du flux de contrôle pour **rétrécir les types** en fonction des conditions.

## Le Rétrécissement Automatique de Types

```php
<?php
function process(string|int|null $value): string
{
    // À ce stade : string|int|null

    if ($value === null) {
        return 'vide';
    }
    // Maintenant : string|int (null exclu)

    if (is_string($value)) {
        return strtoupper($value);  // Méthodes string OK
    }
    // Maintenant : int uniquement

    return (string) $value;  // int → string
}
```

## Rétrécissement avec `instanceof`

```php
<?php
interface Renderable {
    public function render(): string;
}

interface Cacheable {
    public function getCacheKey(): string;
}

function display(object $item): string
{
    $output = '';

    if ($item instanceof Renderable) {
        // $item est maintenant Renderable
        $output = $item->render();
    }

    if ($item instanceof Cacheable) {
        // $item est maintenant Cacheable
        $key = $item->getCacheKey();
    }

    return $output;
}
```

## Les Assertions pour l'Analyse Statique

```php
<?php
/**
 * @param mixed $value
 * @return User
 * @throws InvalidArgumentException
 */
function assertUser(mixed $value): User
{
    if (!$value instanceof User) {
        throw new InvalidArgumentException('Un objet User était attendu');
    }

    return $value;  // Le type est réduit à User
}

// Utilisation
$data = $cache->get('user');
$user = assertUser($data);
// $user est définitivement un User ici
```

## Rétrécissement avec `match`

```php
<?php
function handle(string|int|array $input): string
{
    return match(true) {
        is_string($input) => "Chaîne : $input",
        is_int($input) => "Entier : $input",
        is_array($input) => "Tableau : " . count($input) . " éléments",
    };
}
```

## La Fonction `assert()`

```php
<?php
// Assertions en développement
assert($user instanceof User, 'Une instance User était attendue');

// En production, les assertions peuvent être désactivées :
// zend.assertions = -1 dans php.ini

// Gestionnaire d'assertion personnalisé
set_assert_callback(function(string $file, int $line, ?string $assertion) {
    throw new AssertionError("Assertion échouée : $assertion dans $file:$line");
});
```

## Les Assertions PHPStan/Psalm

```php
<?php
/**
 * @phpstan-assert User $value
 * @psalm-assert User $value
 */
function assertIsUser(mixed $value): void
{
    if (!$value instanceof User) {
        throw new TypeError('Un User était attendu');
    }
}

function processUser(mixed $data): void
{
    assertIsUser($data);
    // Les analyseurs statiques savent maintenant que $data est un User
    echo $data->getName();  // Pas d'erreur !
}
```

## Les Grimoires

- [Déclarations de Types (Documentation Officielle)](https://www.php.net/manual/en/language.types.declarations.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
