---
source_course: "php-api-development"
source_lesson: "php-api-development-openapi-docs"
---

# Documentation OpenAPI

OpenAPI (anciennement Swagger) est **le standard pour documenter les APIs REST**.

## Bases de la Spécification OpenAPI

```yaml
openapi: 3.0.3
info:
  title: Mon API
  version: 1.0.0
  description: Une API exemple

servers:
  - url: https://api.example.com/v1

paths:
  /users:
    get:
      summary: Lister les utilisateurs
      operationId: listUsers
      parameters:
        - name: page
          in: query
          schema:
            type: integer
            default: 1
      responses:
        "200":
          description: Réponse réussie
          content:
            application/json:
              schema:
                $ref: "#/components/schemas/UserList"

components:
  schemas:
    User:
      type: object
      properties:
        id:
          type: integer
        name:
          type: string
        email:
          type: string
          format: email
```

## Générer la Docs depuis PHP

```php
<?php
use OpenApi\Attributes as OA;

#[OA\Info(title: 'Mon API', version: '1.0.0')]
#[OA\Server(url: 'https://api.example.com/v1')]
class OpenApiSpec {}

#[OA\Schema(schema: 'User')]
class User {
    #[OA\Property(type: 'integer')]
    public int $id;

    #[OA\Property(type: 'string', maxLength: 255)]
    public string $name;

    #[OA\Property(type: 'string', format: 'email')]
    public string $email;
}

class UserController {
    #[OA\Get(
        path: '/users',
        summary: 'Lister tous les utilisateurs',
        tags: ['Users'],
        parameters: [
            new OA\Parameter(
                name: 'page',
                in: 'query',
                schema: new OA\Schema(type: 'integer', default: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Liste des utilisateurs',
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/User')
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Non autorisé')
        ]
    )]
    public function index(): never { /* ... */ }

    #[OA\Post(
        path: '/users',
        summary: 'Créer un nouvel utilisateur',
        tags: ['Users'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', type: 'string'),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', minLength: 8),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Utilisateur créé'),
            new OA\Response(response: 422, description: 'Erreur de validation')
        ]
    )]
    public function store(): never { /* ... */ }
}
```

## Servir la Documentation

```php
<?php
// Générer le JSON OpenAPI
$router->get('/openapi.json', function() {
    $openapi = \OpenApi\Generator::scan([__DIR__ . '/src']);

    header('Content-Type: application/json');
    echo $openapi->toJson();
    exit;
});

// Servir Swagger UI
$router->get('/docs', function() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Documentation API</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>
        <script>
            SwaggerUIBundle({
                url: '/openapi.json',
                dom_id: '#swagger-ui'
            });
        </script>
    </body>
    </html>
    <?php
    exit;
});
```

## Les Grimoires

- [Spécification OpenAPI (swagger.io)](https://swagger.io/specification/)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
