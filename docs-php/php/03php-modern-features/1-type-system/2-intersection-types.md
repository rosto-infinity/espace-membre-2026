---
source_course: "php-modern-features"
source_lesson: "php-modern-features-intersection-types"
---

# Les Types Intersection (PHP 8.1+)

Les types intersection exigent qu'une valeur satisfasse **plusieurs contraintes de type simultanément**. La valeur doit implémenter TOUTES les interfaces spécifiées.

## La Syntaxe de Base

```php
<?php
interface Countable {
    public function count(): int;
}

interface Traversable {}

// La valeur DOIT implémenter LES DEUX interfaces
function process(Countable&Traversable $collection): void {
    foreach ($collection as $item) {
        // On peut itérer
    }
    echo "Nombre : " . $collection->count();
}
```

## Cas d'Usage : Combiner des Interfaces

```php
<?php
interface Renderable {
    public function render(): string;
}

interface Cacheable {
    public function getCacheKey(): string;
}

// Doit implémenter les deux interfaces
function renderAndCache(Renderable&Cacheable $component): string {
    $key = $component->getCacheKey();

    // Vérifier le cache d'abord...

    return $component->render();
}
```

## Intersection VS Union

```php
<?php
// Union : A OU B (l'un ou l'autre)
function union(A|B $value): void {}

// Intersection : A ET B (les deux requis)
function intersection(A&B $value): void {}
```

## Les Types DNF (PHP 8.2+) — Forme Normale Disjonctive

Combine union et intersection :

```php
<?php
// (A&B)|C signifie : (A et B) OU juste C
function process((Countable&Iterator)|null $data): void {
    if ($data === null) {
        return;
    }

    // $data implémente à la fois Countable et Iterator
}

// Exemple plus complexe
function handle((A&B)|(C&D) $value): void {
    // La valeur est soit (A et B) soit (C et D)
}
```

## Exemple Pratique

```php
<?php
interface Loggable {
    public function getLogContext(): array;
}

interface Jsonable {
    public function toJson(): string;
}

class Event implements Loggable, Jsonable {
    public function __construct(
        public string $type,
        public array $data
    ) {}

    public function getLogContext(): array {
        return ['type' => $this->type, 'data' => $this->data];
    }

    public function toJson(): string {
        return json_encode($this->data);
    }
}

function logAndSerialize(Loggable&Jsonable $item): void {
    // Garanti d'avoir les deux méthodes
    $context = $item->getLogContext();
    $json = $item->toJson();
}
```

## Les Grimoires

- [Types Intersection (Documentation Officielle)](https://www.php.net/manual/en/language.types.declarations.php#language.types.declarations.composite.intersection)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
