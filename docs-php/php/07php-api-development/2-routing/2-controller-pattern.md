---
source_course: "php-api-development"
source_lesson: "php-api-development-controller-pattern"
---

# Le Pattern Contrôleur (Controller Pattern)

Les contrôleurs **organisent les gestionnaires de routes liés dans des classes** pour une meilleure structure.

## Contrôleur de Base

```php
<?php
abstract class Controller {
    protected function json(mixed $data, int $status = 200): never {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit;
    }

    protected function input(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    protected function notFound(string $message = 'Introuvable'): never {
        $this->json(['error' => $message], 404);
    }

    protected function badRequest(string $message, ?array $errors = null): never {
        $response = ['error' => $message];
        if ($errors) {
            $response['details'] = $errors;
        }
        $this->json($response, 400);
    }
}
```

## Contrôleur de Ressource

```php
<?php
class UserController extends Controller {
    public function __construct(
        private UserRepository $users
    ) {}

    // GET /users
    public function index(): never {
        $users = $this->users->findAll();
        $this->json(['data' => $users]);
    }

    // GET /users/{id}
    public function show(string $id): never {
        $user = $this->users->find((int) $id);

        if (!$user) {
            $this->notFound('Utilisateur introuvable');
        }

        $this->json(['data' => $user->toArray()]);
    }

    // POST /users
    public function store(): never {
        $data = $this->input();

        $errors = $this->validate($data, [
            'name' => 'required',
            'email' => 'required|email',
        ]);

        if ($errors) {
            $this->badRequest('Validation échouée', $errors);
        }

        $user = $this->users->create($data);
        $this->json(['data' => $user->toArray()], 201);
    }

    // PUT /users/{id}
    public function update(string $id): never {
        $user = $this->users->find((int) $id);

        if (!$user) {
            $this->notFound('Utilisateur introuvable');
        }

        $data = $this->input();
        $user->fill($data);
        $this->users->save($user);

        $this->json(['data' => $user->toArray()]);
    }

    // DELETE /users/{id}
    public function destroy(string $id): never {
        $user = $this->users->find((int) $id);

        if ($user) {
            $this->users->delete($user);
        }

        http_response_code(204);
        exit;
    }

    private function validate(array $data, array $rules): array {
        // Logique de validation
        return [];
    }
}
```

## Routeur avec Contrôleurs

```php
<?php
class Router {
    // ... code précédent ...

    public function controller(string $prefix, string $controllerClass): void {
        $this->get("$prefix", [$controllerClass, 'index']);
        $this->get("$prefix/{id}", [$controllerClass, 'show']);
        $this->post("$prefix", [$controllerClass, 'store']);
        $this->put("$prefix/{id}", [$controllerClass, 'update']);
        $this->patch("$prefix/{id}", [$controllerClass, 'update']);
        $this->delete("$prefix/{id}", [$controllerClass, 'destroy']);
    }
}

// Utilisation
$router = new Router();
$router->controller('/users', UserController::class);
$router->controller('/products', ProductController::class);
```

## Exemple Complet

**Routeur complet avec groupes et middleware**

```php
<?php
declare(strict_types=1);

// Structure complète d'application API

// Routeur avec support des middlewares
class Router {
    private array $routes = [];
    private array $middleware = [];
    private ?string $prefix = null;

    public function group(string $prefix, callable $callback): void {
        $previousPrefix = $this->prefix;
        $this->prefix = ($this->prefix ?? '') . $prefix;
        $callback($this);
        $this->prefix = $previousPrefix;
    }

    public function middleware(string $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }

    public function get(string $path, array|callable $handler): void {
        $this->addRoute('GET', $path, $handler);
    }

    public function post(string $path, array|callable $handler): void {
        $this->addRoute('POST', $path, $handler);
    }

    public function put(string $path, array|callable $handler): void {
        $this->addRoute('PUT', $path, $handler);
    }

    public function delete(string $path, array|callable $handler): void {
        $this->addRoute('DELETE', $path, $handler);
    }

    private function addRoute(string $method, string $path, array|callable $handler): void {
        $fullPath = ($this->prefix ?? '') . $path;
        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $this->middleware,
        ];
        $this->middleware = [];  // Réinitialiser pour la prochaine route
    }

    public function dispatch(): void {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Supprimer le chemin de base si nécessaire
        $basePath = '/api';
        if (str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;

            $params = $this->match($route['path'], $uri);
            if ($params === false) continue;

            // Exécuter les middlewares
            foreach ($route['middleware'] as $middlewareClass) {
                $middleware = new $middlewareClass();
                $middleware->handle();
            }

            // Appeler le gestionnaire
            $handler = $route['handler'];
            if (is_array($handler)) {
                [$class, $method] = $handler;
                $controller = new $class();
                $controller->$method(...array_values($params));
            } else {
                $handler(...array_values($params));
            }
            return;
        }

        http_response_code(404);
        echo json_encode(['error' => 'Route introuvable']);
    }

    private function match(string $routePath, string $uri): array|false {
        $pattern = preg_replace('/\{([a-z]+)\}/', '(?P<$1>[^/]+)', $routePath);
        if (preg_match('#^' . $pattern . '$#', $uri, $matches)) {
            return array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
        }
        return false;
    }
}

// Définition des routes
$router = new Router();

$router->group('/v1', function(Router $r) {
    $r->group('/users', function(Router $r) {
        $r->get('', [UserController::class, 'index']);
        $r->post('', [UserController::class, 'store']);
        $r->get('/{id}', [UserController::class, 'show']);
        $r->put('/{id}', [UserController::class, 'update']);
        $r->delete('/{id}', [UserController::class, 'destroy']);

        // Ressource imbriquée
        $r->get('/{id}/orders', [UserController::class, 'orders']);
    });

    $r->middleware(AuthMiddleware::class)->group('/admin', function(Router $r) {
        $r->get('/stats', [AdminController::class, 'stats']);
    });
});

$router->dispatch();
?>
```

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
