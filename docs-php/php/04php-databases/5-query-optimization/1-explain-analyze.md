---
source_course: "php-databases"
source_lesson: "php-databases-explain-analyze"
---

# Utiliser EXPLAIN pour Analyser les Requêtes

EXPLAIN **révèle comment la base de données exécute vos requêtes**, vous aidant à identifier les problèmes de performance.

## EXPLAIN de Base

```php
<?php
$stmt = $pdo->query('EXPLAIN SELECT * FROM users WHERE email = "jean@example.com"');
print_r($stmt->fetch());

// Sortie :
// id: 1
// select_type: SIMPLE
// table: users
// type: const  (bien ! utilise un index unique)
// possible_keys: idx_email
// key: idx_email
// rows: 1
// Extra: NULL
```

## Types d'EXPLAIN (Du Meilleur au Pire)

| Type         | Signification              | Action                       |
| ------------ | -------------------------- | ---------------------------- |
| system/const | Une seule ligne            | Parfait                      |
| eq_ref       | Une ligne par jointure     | Bon                          |
| ref          | Plusieurs lignes via index | Bon                          |
| range        | Balayage de plage d'index  | Acceptable                   |
| index        | Balayage complet d'index   | Envisager l'optimisation     |
| ALL          | Balayage complet de table  | Nécessite une optimisation ! |

## Identifier les Problèmes

```php
<?php
// MAUVAIS : Balayage complet de la table (type: ALL)
$stmt = $pdo->query('EXPLAIN SELECT * FROM orders WHERE YEAR(created_at) = 2024');
// Les fonctions sur les colonnes empêchent l'utilisation de l'index !

// BON : Utilise l'index (type: range)
$stmt = $pdo->query('EXPLAIN SELECT * FROM orders WHERE created_at >= "2024-01-01" AND created_at < "2025-01-01"');

// MAUVAIS : Pas d'index sur status
$stmt = $pdo->query('EXPLAIN SELECT * FROM orders WHERE status = "pending"');
// key: NULL signifie qu'aucun index n'est utilisé

// Après avoir ajouté un index :
// CREATE INDEX idx_status ON orders(status);
// key: idx_status
```

## EXPLAIN ANALYZE (MySQL 8.0+)

```php
<?php
// Affiche le temps d'exécution réel
$stmt = $pdo->query('
    EXPLAIN ANALYZE
    SELECT u.name, COUNT(o.id) as order_count
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
');

// La sortie inclut le temps réel et les lignes traitées
```

## Problèmes Courants de Requêtes

```sql
-- Problème : SELECT *
SELECT * FROM users;  -- Récupère toutes les colonnes
SELECT id, name, email FROM users;  -- Mieux : seulement les colonnes nécessaires

-- Problème : Requêtes N+1
FOREACH user { SELECT * FROM orders WHERE user_id = ? }  -- N requêtes !
SELECT * FROM orders WHERE user_id IN (1,2,3,4,5);  -- 1 requête

-- Problème : Fonction sur une colonne indexée
WHERE LOWER(email) = 'jean@example.com'  -- Ne peut pas utiliser l'index
WHERE email = 'jean@example.com'  -- Utilise l'index (stocker en minuscules)

-- Problème : LIKE avec caractère joker en tête
WHERE name LIKE '%jean%'  -- Balayage complet
WHERE name LIKE 'jean%'   -- Peut utiliser l'index
```

## Les Grimoires

- [MySQL EXPLAIN (Documentation Officielle)](https://dev.mysql.com/doc/refman/8.0/en/explain.html)

---

> 📘 _Cette leçon fait partie du cours [PHP & Bases de Données Relationnelles](/php/php-databases/) sur la plateforme d'apprentissage RostoDev._
