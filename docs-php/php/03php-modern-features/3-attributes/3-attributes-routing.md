---
source_course: "php-modern-features"
source_lesson: "php-modern-features-attributes-routing"
---

# Construire un Routeur Basé sur les Attributs

Les attributs permettent un routage déclaratif similaire aux frameworks comme Symfony ou Laravel. Construisons un système de routage complet.

## Définition de l'Attribut Route

```php
<?php
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route
{
    public function __construct(
        public string $path,
        public string $method = 'GET',
        public ?string $name = null,
        public array $middleware = []
    ) {}
}

#[Attribute(Attribute::TARGET_CLASS)]
class Controller
{
    public function __construct(
        public string $prefix = ''
    ) {}
}

#[Attribute(Attribute::TARGET_METHOD)]
class Middleware
{
    public function __construct(
        public string $name
    ) {}
}
```

## Définition du Contrôleur

```php
<?php
#[Controller('/api/users')]
class UserController
{
    #[Route('/', 'GET', name: 'users.index')]
    #[Middleware('auth')]
    public function index(): array
    {
        return ['users' => []];
    }

    #[Route('/{id}', 'GET', name: 'users.show')]
    #[Middleware('auth')]
    public function show(int $id): array
    {
        return ['user' => ['id' => $id]];
    }

    #[Route('/', 'POST', name: 'users.create')]
    #[Middleware('auth')]
    #[Middleware('admin')]
    public function create(): array
    {
        return ['created' => true];
    }
}
```

## La Collection de Routes

```php
<?php
class RouteDefinition
{
    public function __construct(
        public string $path,
        public string $method,
        public string $controller,
        public string $action,
        public ?string $name,
        public array $middleware
    ) {}

    public function matches(string $method, string $path): ?array
    {
        if ($this->method !== $method) {
            return null;
        }

        // Convertir {param} en regex
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }

        return null;
    }
}
```

## L'Implémentation du Routeur

```php
<?php
class AttributeRouter
{
    /** @var RouteDefinition[] */
    private array $routes = [];

    public function registerController(string $className): void
    {
        $reflection = new ReflectionClass($className);

        // Obtenir le préfixe du contrôleur
        $prefix = '';
        $controllerAttrs = $reflection->getAttributes(Controller::class);
        if (!empty($controllerAttrs)) {
            $prefix = $controllerAttrs[0]->newInstance()->prefix;
        }

        // Enregistrer chaque méthode de route
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $routeAttrs = $method->getAttributes(Route::class);

            foreach ($routeAttrs as $attr) {
                $route = $attr->newInstance();

                // Collecter les middlewares
                $middleware = $route->middleware;
                foreach ($method->getAttributes(Middleware::class) as $mwAttr) {
                    $middleware[] = $mwAttr->newInstance()->name;
                }

                $this->routes[] = new RouteDefinition(
                    path: $prefix . $route->path,
                    method: $route->method,
                    controller: $className,
                    action: $method->getName(),
                    name: $route->name,
                    middleware: $middleware
                );
            }
        }
    }

    public function dispatch(string $method, string $uri): mixed
    {
        foreach ($this->routes as $route) {
            $params = $route->matches($method, $uri);

            if ($params !== null) {
                // Exécuter les middlewares
                foreach ($route->middleware as $mw) {
                    $this->runMiddleware($mw);
                }

                // Appeler l'action du contrôleur
                $controller = new ($route->controller)();
                return $controller->{$route->action}(...$params);
            }
        }

        throw new NotFoundException('Route introuvable');
    }
}

// Utilisation
$router = new AttributeRouter();
$router->registerController(UserController::class);

$result = $router->dispatch('GET', '/api/users/123');
// Appelle UserController::show(123)
```

## Les Grimoires

- [Attributs PHP (Documentation Officielle)](https://www.php.net/manual/en/language.attributes.php)

---

> 📘 _Cette leçon fait partie du cours [PHP 8.x Moderne : Les Dernières Fonctionnalités du Langage](/php/php-modern-features/) sur la plateforme d'apprentissage RostoDev._
