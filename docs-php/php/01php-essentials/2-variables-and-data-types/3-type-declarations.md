---
source_course: "php-essentials"
source_lesson: "php-essentials-type-declarations"
---

# Déclarations de Type en PHP Moderne

PHP 8+ supporte les déclarations de type strictes, rendant votre code plus fiable et auto-documenté.

## Activer les Types Stricts

Ajoutez cette directive en haut de votre fichier :

```php
<?php
declare(strict_types=1);
```

Avec les types stricts activés, PHP lèvera une `TypeError` si un mauvais type est passé en argument.

## Types des Paramètres de Fonction

```php
<?php
declare(strict_types=1);

function saluer(string $nom): void {
    echo "Bonjour, $nom !";
}

saluer("Alice"); // Fonctionne
saluer(123);     // TypeError !
```

## Déclarations de Type de Retour

```php
<?php
function additionner(int $a, int $b): int {
    return $a + $b;
}

function trouverUtilisateur(int $id): ?User { // ? permet null
    return $id > 0 ? new User() : null;
}

function journaliser(string $msg): void { // Pas de retour
    echo $msg;
}
```

## Types Union (PHP 8.0+)

Pour accepter plusieurs types :

```php
<?php
function traiterID(int|string $id): void {
    echo "Traitement : $id";
}

traiterID(42);        // Fonctionne
traiterID("ABC123");  // Fonctionne aussi
```

## Types Nullable

Deux façons d'autoriser null :

```php
<?php
function chercher(?int $id): ?string {
    // $id peut être int ou null
    // Retourne string ou null
}

// Syntaxe union PHP 8.0+ (équivalent)
function chercher(int|null $id): string|null {
    // Identique ci-dessus
}
```

## Type Mixed (PHP 8.0+)

Accepte n'importe quel type :

```php
<?php
function deboguer(mixed $valeur): void {
    var_dump($valeur);
}
```

## Types de Propriétés de Classe (PHP 7.4+)

```php
<?php
class Produit {
    public string $nom;
    public float $prix;
    public ?string $description = null;
    private int $stock = 0;
}
```

## Avantages des Déclarations de Type

1. **Détecter les bugs tôt** : Les erreurs se produisent à l'appel, pas au fond du code.
2. **Code auto-documenté** : Le code explique clairement ce qu'il attend.
3. **Support des IDE** : Meilleure auto-complétion et détection d'erreurs.
4. **Performance** : PHP peut optimiser le code typé.

## Exemples de code

**Classe PHP moderne avec déclarations de type**

```php
<?php
declare(strict_types=1);

class Calculatrice {
    public function additionner(int|float $a, int|float $b): float {
        return (float) ($a + $b);
    }

    public function diviser(float $a, float $b): ?float {
        if ($b === 0.0) {
            return null; // Évite la division par zéro
        }
        return $a / $b;
    }
}

$calc = new Calculatrice();
echo $calc->additionner(5, 3.5);   // 8.5
echo $calc->diviser(10, 0);        // null
?>
```

## Ressources

- [Déclarations de Type](https://www.php.net/manual/fr/language.types.declarations.php) — Guide officiel sur les déclarations de type PHP

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
