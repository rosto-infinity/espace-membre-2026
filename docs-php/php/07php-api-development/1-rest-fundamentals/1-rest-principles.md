---
source_course: "php-api-development"
source_lesson: "php-api-development-rest-principles"
---

# Les Principes de l'Architecture REST

REST (Representational State Transfer) est un style architectural pour construire des APIs web **scalables, maintenables et intuitives**.

## Principes Fondamentaux

### 1. Séparation Client-Serveur

Les clients et les serveurs sont indépendants. Le client gère l'UI/UX, le serveur gère les données et la logique.

### 2. Sans État (Statelessness)

Chaque requête contient toutes les informations nécessaires. Le serveur ne stocke pas l'état du client entre les requêtes.

```php
<?php
// MAUVAIS : Le serveur stocke l'état
$_SESSION['last_action'] = 'viewed_products';
// La prochaine requête dépend de ceci

// BON : La requête est autonome
// GET /products?page=2&sort=price&filter=active
// Tout le contexte est dans la requête elle-même
```

### 3. URLs Basées sur les Ressources

```
# Ressources (noms, pas des verbes)
GET    /users          # Lister les utilisateurs
GET    /users/123      # Obtenir l'utilisateur 123
POST   /users          # Créer un utilisateur
PUT    /users/123      # Remplacer l'utilisateur 123
PATCH  /users/123      # Mettre à jour partiellement l'utilisateur 123
DELETE /users/123      # Supprimer l'utilisateur 123

# Ressources imbriquées
GET    /users/123/orders        # Commandes de l'utilisateur
GET    /users/123/orders/456    # Commande spécifique
```

### 4. Méthodes HTTP (Verbes)

| Méthode | Rôle                  | Idempotent | Sûr |
| ------- | --------------------- | ---------- | --- |
| GET     | Lire                  | Oui        | Oui |
| POST    | Créer                 | Non        | Non |
| PUT     | Remplacer             | Oui        | Non |
| PATCH   | Mise à jour partielle | Oui        | Non |
| DELETE  | Supprimer             | Oui        | Non |

### 5. Codes de Statut HTTP

```php
<?php
// Succès
http_response_code(200);  // OK
http_response_code(201);  // Créé
http_response_code(204);  // Pas de contenu (DELETE)

// Erreurs Client
http_response_code(400);  // Mauvaise Requête
http_response_code(401);  // Non Autorisé
http_response_code(403);  // Interdit
http_response_code(404);  // Introuvable
http_response_code(422);  // Entité Non Traitable (validation)

// Erreurs Serveur
http_response_code(500);  // Erreur Interne du Serveur
http_response_code(503);  // Service Indisponible
```

## Bonnes Pratiques de Conception d'URLs

```
# Bonnes URLs
GET  /products
GET  /products/123
GET  /products?category=electronique
GET  /products?sort=price&order=desc
GET  /users/123/orders

# Mauvaises URLs
GET  /getProducts         # Verbe dans l'URL
GET  /product/123         # Pluriel incohérent
POST /users/create        # Action dans l'URL
GET  /users/123/getOrders # Verbe dans l'URL
```

## Les Grimoires

- [Méthodes HTTP (MDN)](https://developer.mozilla.org/fr/docs/Web/HTTP/Methods)

---

> 📘 _Cette leçon fait partie du cours [Développement d'API RESTful avec PHP](/php/php-api-development/) sur la plateforme d'apprentissage RostoDev._
