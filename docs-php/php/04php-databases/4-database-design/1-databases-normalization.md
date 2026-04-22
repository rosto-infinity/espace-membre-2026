---
source_course: "php-databases"
source_lesson: "php-databases-normalization"
---

# Normalisation & Conception de Schéma

Une bonne conception de base de données **prévient les anomalies de données et améliore les performances**.

## Les Formes Normales

### Première Forme Normale (1FN)

- Valeurs atomiques (pas de tableaux/listes dans les colonnes)
- Chaque ligne est unique

```sql
-- MAUVAIS : Viole la 1FN
CREATE TABLE orders (
    id INT,
    products VARCHAR(255)  -- 'Widget, Gadget, Truc'
);

-- BON : Conforme à la 1FN
CREATE TABLE orders (id INT PRIMARY KEY);
CREATE TABLE order_items (
    order_id INT,
    product_id INT
);
```

### Deuxième Forme Normale (2FN)

- En 1FN
- Pas de dépendances partielles (toutes les colonnes non-clé dépendent de toute la clé primaire)

```sql
-- MAUVAIS : product_name ne dépend que de product_id
CREATE TABLE order_items (
    order_id INT,
    product_id INT,
    product_name VARCHAR(255),  -- Dépendance partielle !
    quantity INT,
    PRIMARY KEY (order_id, product_id)
);

-- BON : Table séparée pour les produits
CREATE TABLE products (
    id INT PRIMARY KEY,
    name VARCHAR(255)
);

CREATE TABLE order_items (
    order_id INT,
    product_id INT,
    quantity INT,
    PRIMARY KEY (order_id, product_id)
);
```

### Troisième Forme Normale (3FN)

- En 2FN
- Pas de dépendances transitives

```sql
-- MAUVAIS : city dépend de zip_code, pas directement de l'utilisateur
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    zip_code VARCHAR(10),
    city VARCHAR(255)  -- Dépendance transitive !
);

-- BON : Séparer les localisations
CREATE TABLE locations (
    zip_code VARCHAR(10) PRIMARY KEY,
    city VARCHAR(255)
);

CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    zip_code VARCHAR(10) REFERENCES locations(zip_code)
);
```

## Dénormalisation pour les Performances

```sql
-- Parfois la dénormalisation est acceptable
CREATE TABLE orders (
    id INT PRIMARY KEY,
    user_id INT,
    user_name VARCHAR(255),       -- Dénormalisé pour l'affichage
    total_amount DECIMAL(10,2),   -- Calcul mis en cache
    item_count INT                -- Comptage mis en cache
);
```

## Stratégies d'Indexation

```sql
-- Clé primaire (index automatique)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT
);

-- Index de clé étrangère
CREATE INDEX idx_orders_user_id ON orders(user_id);

-- Index composite pour les requêtes courantes
CREATE INDEX idx_orders_user_status ON orders(user_id, status);

-- Contrainte unique avec index
CREATE UNIQUE INDEX idx_users_email ON users(email);

-- Recherche plein texte
CREATE FULLTEXT INDEX idx_products_search ON products(name, description);
```

## Les Grimoires

- [MySQL CREATE INDEX (Documentation Officielle)](https://dev.mysql.com/doc/refman/8.0/en/create-index.html)

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
