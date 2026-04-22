---
source_course: "php-modern-features"
source_lesson: "php-modern-features-constructor-promotion"
---

# La Promotion de Propriétés du Constructeur (PHP 8.0+)

La promotion de constructeur est une **syntaxe raccourcie** qui déclare et initialise les propriétés directement dans la signature du constructeur.

## L'Ancienne Manière

```php
<?php
// Avant PHP 8.0 — beaucoup de code répétitif !
class User {
    private string $name;
    private string $email;
    private int $age;

    public function __construct(
        string $name,
        string $email,
        int $age
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->age = $age;
    }
}
```

## La Nouvelle Manière

```php
<?php
// PHP 8.0+ avec la promotion — C'est TOUT !
class User {
    public function __construct(
        private string $name,
        private string $email,
        private int $age
    ) {}
}

// Les propriétés sont déclarées et assignées automatiquement
```

## Promotion Mixte et Paramètres Classiques

```php
<?php
class Post {
    public function __construct(
        private string $title,      // Promu (devient une propriété)
        private string $content,    // Promu
        private Author $author,     // Promu
        string $tempData = ''       // PAS promu (pas de visibilité)
    ) {
        // $tempData est disponible ici mais pas en tant que propriété
        $this->processTemp($tempData);
    }
}
```

## Avec Des Valeurs Par Défaut

```php
<?php
class Config {
    public function __construct(
        public string $environment = 'production',
        public bool $debug = false,
        public int $cacheTime = 3600,
        public array $features = []
    ) {}
}

$config = new Config(debug: true);
echo $config->environment;  // 'production'
echo $config->debug;        // true
```

## Combinaison avec Readonly (PHP 8.1+)

```php
<?php
class ImmutableUser {
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $email
    ) {}
}

$user = new ImmutableUser('1', 'Jean', 'jean@example.com');
echo $user->name;           // Jean
$user->name = 'Marie';      // Erreur ! Propriété readonly immuable
```

## Les Avantages

1. **Moins de boilerplate** : Pas de déclarations de propriétés, pas d'assignations
2. **Code plus propre** : Tout au même endroit
3. **Maintenance facilitée** : Changer une fois, pas trois fois
4. **Fonctionne avec les arguments nommés** : `new User(email: 'test@test.com', name: 'Test')`

## Exemple Concret

**Un DTO (Data Transfer Object) avec la promotion de constructeur**

```php
<?php
declare(strict_types=1);

// DTO moderne avec promotion de constructeur
class CreateUserDTO {
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly ?string $phone = null,
        public readonly array $roles = ['user'],
        public readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {}

    public static function fromRequest(array $data): self {
        return new self(
            name: $data['name'] ?? throw new InvalidArgumentException('Nom requis'),
            email: $data['email'] ?? throw new InvalidArgumentException('Email requis'),
            password: $data['password'] ?? throw new InvalidArgumentException('Mot de passe requis'),
            phone: $data['phone'] ?? null,
            roles: $data['roles'] ?? ['user']
        );
    }
}

// Utilisation
$dto = CreateUserDTO::fromRequest($_POST);
echo $dto->name;
?>
```

## Les Grimoires

- [Promotion de Constructeur (Documentation Officielle)](https://www.php.net/manual/en/language.oop5.decon.php#language.oop5.decon.constructor.promotion)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
