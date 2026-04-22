---
source_course: "php-performance"
source_lesson: "php-performance-eager-loading"
---

# Chargement Eager et Optimisation des Requêtes

Le chargement eager récupère les données associées en bloc, évitant le problème N+1 qui plague de nombreuses applications.

## Le Problème N+1 Illustré

```php
<?php
// Problème N+1 : 1 requête pour les articles + N requêtes pour les auteurs
$posts = $pdo->query('SELECT * FROM posts LIMIT 10')->fetchAll();

foreach ($posts as $post) {
    // S'exécute 10 fois !
    $author = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $author->execute([$post['user_id']]);
    echo $post['title'] . ' par ' . $author->fetch()['name'];
}
// Total : 11 requêtes
```

## Solution : Requête JOIN

```php
<?php
// Requête unique avec JOIN
$sql = '
    SELECT posts.*, users.name as author_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    LIMIT 10
';

$posts = $pdo->query($sql)->fetchAll();

foreach ($posts as $post) {
    echo $post['title'] . ' par ' . $post['author_name'];
}
// Total : 1 requête
```

## Solution : Chargement par Lots

```php
<?php
class PostRepository
{
    public function getPostsWithAuthors(int $limit = 10): array
    {
        // Requête 1 : Récupérer les articles
        $posts = $this->pdo->query("SELECT * FROM posts LIMIT $limit")->fetchAll();

        if (empty($posts)) {
            return [];
        }

        // Extraire les IDs d'utilisateurs uniques
        $userIds = array_unique(array_column($posts, 'user_id'));
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));

        // Requête 2 : Récupérer tous les auteurs en une requête
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id IN ($placeholders)");
        $stmt->execute($userIds);
        $users = $stmt->fetchAll(PDO::FETCH_UNIQUE);  // Indexer par ID

        // Combiner les données
        foreach ($posts as &$post) {
            $post['author'] = $users[$post['user_id']] ?? null;
        }

        return $posts;
    }
}
// Total : 2 requêtes (quelle que soit la quantité d'articles)
```

## Chargement Paresseux avec Data Mapper

```php
<?php
class LazyPost
{
    private ?User $author = null;
    private bool $authorLoaded = false;

    public function __construct(
        public int $id,
        public string $title,
        public int $userId,
        private UserRepository $userRepo
    ) {}

    public function getAuthor(): User
    {
        if (!$this->authorLoaded) {
            $this->author = $this->userRepo->find($this->userId);
            $this->authorLoaded = true;
        }
        return $this->author;
    }

    // Pré-charger pour éviter le chargement paresseux
    public function setAuthor(User $user): void
    {
        $this->author = $user;
        $this->authorLoaded = true;
    }
}
```

## Analyse de Requête avec EXPLAIN

```php
<?php
function analyzeQuery(PDO $pdo, string $sql): array
{
    $stmt = $pdo->query('EXPLAIN ' . $sql);
    $plan = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $issues = [];

    foreach ($plan as $row) {
        // Scan complet
        if ($row['type'] === 'ALL' && $row['rows'] > 1000) {
            $issues[] = "Scan complet sur {$row['table']} ({$row['rows']} lignes)";
        }

        // Pas d'index utilisé
        if (empty($row['key'])) {
            $issues[] = "Pas d'index utilisé pour {$row['table']}";
        }

        // Tri de fichier
        if (str_contains($row['Extra'] ?? '', 'filesort')) {
            $issues[] = "Utilisation de filesort pour {$row['table']}";
        }

        // Table temporaire
        if (str_contains($row['Extra'] ?? '', 'temporary')) {
            $issues[] = "Utilisation d'une table temporaire pour {$row['table']}";
        }
    }

    return [
        'plan' => $plan,
        'issues' => $issues,
    ];
}
```

## Les Grimoires

- [MySQL EXPLAIN](https://dev.mysql.com/doc/refman/8.0/en/explain.html)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
