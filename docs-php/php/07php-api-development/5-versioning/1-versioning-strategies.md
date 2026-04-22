---
source_course: "php-api-development"
source_lesson: "php-api-development-versioning-strategies"
---

# Stratégies de Versionnage d'API

Le versionnage d'API vous permet de **faire évoluer votre API tout en maintenant la compatibilité ascendante**.

## Versionnage par Chemin URL

```php
<?php
// L'approche la plus courante
// /api/v1/users
// /api/v2/users

$router->group('/api/v1', function(Router $r) {
    $r->get('/users', [UserControllerV1::class, 'index']);
    $r->get('/users/{id}', [UserControllerV1::class, 'show']);
});

$router->group('/api/v2', function(Router $r) {
    $r->get('/users', [UserControllerV2::class, 'index']);
    $r->get('/users/{id}', [UserControllerV2::class, 'show']);
});
```

## Versionnage par Header

```php
<?php
// Accept: application/vnd.myapi.v1+json
// X-API-Version: 1

function getApiVersion(): int {
    // Vérifier l'header Accept
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (preg_match('/vnd\.myapi\.v(\d+)/', $accept, $matches)) {
        return (int) $matches[1];
    }

    // Vérifier l'header personnalisé
    $version = $_SERVER['HTTP_X_API_VERSION'] ?? '1';
    return (int) $version;
}

// Router vers la bonne version
$version = getApiVersion();
$controllerClass = match($version) {
    1 => UserControllerV1::class,
    2 => UserControllerV2::class,
    default => throw new BadRequestException('Version d\'API non supportée'),
};
```

## Versionnage par Paramètre de Requête

```php
<?php
// /api/users?version=2

$version = (int) ($_GET['version'] ?? 1);
```

## Couche d'Abstraction de Version

```php
<?php
interface UserRepositoryInterface {
    public function findAll(): array;
    public function find(int $id): ?User;
}

// V1 : Retourne les données utilisateur de base
class UserControllerV1 extends Controller {
    public function index(): never {
        $users = $this->users->findAll();

        $this->json([
            'data' => array_map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ], $users)
        ]);
    }
}

// V2 : Retourne les données étendues avec le profil
class UserControllerV2 extends Controller {
    public function index(): never {
        $users = $this->users->findAllWithProfile();

        $this->json([
            'data' => array_map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'profile' => [
                    'avatar' => $u->profile?->avatar,
                    'bio' => $u->profile?->bio,
                ],
                'created_at' => $u->createdAt->format('c'),
            ], $users),
            'meta' => [
                'total' => count($users),
            ]
        ]);
    }
}
```

## Gestion de la Dépréciation

```php
<?php
class DeprecationMiddleware {
    private array $deprecated = [
        '/api/v1/users' => '2025-01-01',
        '/api/v1/products' => '2025-06-01',
    ];

    public function handle(): void {
        $path = $_SERVER['REQUEST_URI'];

        foreach ($this->deprecated as $pattern => $sunsetDate) {
            if (str_starts_with($path, $pattern)) {
                header('Deprecation: true');
                header('Sunset: ' . date('D, d M Y H:i:s T', strtotime($sunsetDate)));
                header('Link: </api/v2/users>; rel="successor-version"');
                break;
            }
        }
    }
}
```

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
