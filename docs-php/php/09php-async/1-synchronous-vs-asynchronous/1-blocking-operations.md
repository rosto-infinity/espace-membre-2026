---
source_course: "php-async"
source_lesson: "php-async-blocking-operations"
---

# Comprendre les Opérations Bloquantes

Avant de plonger dans la programmation asynchrone, vous devez comprendre **ce qui rend PHP traditionnellement synchrone** et pourquoi les opérations bloquantes peuvent être problématiques pour certaines applications.

## Qu'est-ce que l'Exécution Synchrone ?

**L'exécution synchrone** signifie que votre code s'exécute ligne par ligne, dans l'ordre, attendant que chaque opération se termine avant de passer à la suivante. C'est ainsi que PHP a traditionnellement fonctionné.

```php
<?php
// Exemple d'exécution synchrone
echo "Démarrage...\n";

// Ceci bloque - rien d'autre ne se passe jusqu'à la fin
$data = file_get_contents('https://api.exemple.com/users');

echo "Données reçues!\n";  // S'exécute seulement après que file_get_contents se termine

// Traiter les données
$users = json_decode($data, true);
echo "Traité " . count($users) . " utilisateurs\n";
```

Dans cet exemple, le script entier attend pendant que `file_get_contents()` récupère les données depuis l'API. Si l'API prend 3 secondes à répondre, votre script ne fait **absolument rien** pendant ces 3 secondes.

## Qu'est-ce qu'une Opération Bloquante ?

Une **opération bloquante** est toute opération qui fait pauserun votre programme. Les opérations bloquantes courantes en PHP incluent :

| Type d'Opération         | Exemples                                               |
| ------------------------ | ------------------------------------------------------ |
| **I/O Réseau**           | Requêtes HTTP, requêtes de base de données, appels API |
| **I/O Fichier**          | Lecture/écriture de fichiers, surtout volumineux       |
| **Fonctions de sommeil** | `sleep()`, `usleep()`                                  |
| **Processus externes**   | `shell_exec()`, `exec()`                               |
| **Entrée utilisateur**   | `fgets(STDIN)` dans des scripts CLI                    |

## Le Problème avec le Blocage

Imaginez que vous devez récupérer des données de trois APIs différentes :

```php
<?php
// Blocage séquentiel - LENT !
$startTime = microtime(true);

// Chaque appel bloque jusqu'à la fin
$users = file_get_contents('https://api.exemple.com/users');     // 2 secondes
$orders = file_get_contents('https://api.exemple.com/orders');   // 1 seconde
$products = file_get_contents('https://api.exemple.com/products'); // 1.5 secondes

$totalTime = microtime(true) - $startTime;
echo "Temps total: {$totalTime} secondes\n"; // ~4.5 secondes
```

Même si ces trois requêtes sont **indépendantes**, elles s'exécutent séquentiellement. Le temps total est la **somme** de tous les temps individuels.

## Visualiser la Chronologie

```
Exécution Synchrone :

|-- API Users (2s) --|-- API Orders (1s) --|-- API Products (1.5s) --|
                                                                      Total : 4.5s

Exécution Asynchrone (idéal) :

|-- API Users (2s) --|
|-- API Orders (1s) --|
|-- API Products (1.5s) --|
                          Total : 2s (la requête la plus lente)
```

Avec l'exécution asynchrone, les trois requêtes peuvent s'exécuter **en simultané**, et le temps total ne serait que celui de la requête la plus lente.

## Quand le Blocage est Acceptable

Le blocage n'est pas toujours mauvais. Pour de nombreuses applications PHP, l'exécution synchrone est parfaitement correcte :

1. **Requêtes web simples** : La plupart des pages web se terminent en moins de 200ms
2. **Opérations CRUD** : Les opérations de base de données basiques sont rapides
3. **Dépendances séquentielles** : Quand l'étape B nécessite le résultat de l'étape A
4. **Applications à faible trafic** : Le blocage importe moins avec peu d'utilisateurs

## Quand l'Async Devient Nécessaire

Envisagez la programmation asynchrone quand :

- **Haute concurrence** : Nombreux utilisateurs ou requêtes simultanés
- **Dépendances API externes** : Plusieurs services tiers
- **Tâches de longue durée** : Traitement de fichiers, génération de rapports
- **Fonctionnalités temps réel** : Chat, notifications, mises à jour en direct
- **Serveurs WebSocket** : Connexions persistantes

## CPU-Bound vs I/O-Bound

**Opérations I/O-Bound** (bons candidats pour l'async) :

- Requêtes réseau
- Requêtes de base de données
- Lecture/écriture de fichiers
- Appels à des services externes

Ces opérations passent la plupart du temps **à attendre** des systèmes externes.

**Opérations CPU-Bound** (l'async n'aide pas beaucoup) :

- Calculs complexes
- Traitement d'images
- Chiffrement de données
- Tri de grands ensembles de données

Ces opérations gardent le CPU occupé. L'async ne les accélérera pas car le CPU travaille déjà.

## Les Grimoires

- [Manuel PHP - Introduction](https://www.php.net/manual/en/intro-whatis.php)

---

> 📘 _Cette leçon fait partie du cours [PHP Asynchrone](/php/php-async/) sur la plateforme d'apprentissage RostoDev._
