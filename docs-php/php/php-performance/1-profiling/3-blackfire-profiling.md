---
source_course: "php-performance"
source_lesson: "php-performance-blackfire-profiling"
---

# Profilage Avancé avec Blackfire

Blackfire est un profileur de qualité production qui fournit des informations détaillées sur les performances des applications PHP sans overhead significatif.

## Pourquoi Blackfire plutôt que Xdebug ?

| Fonctionnalité            | Xdebug               | Blackfire                       |
| ------------------------- | -------------------- | ------------------------------- |
| Utilisation en Production | Non (overhead élevé) | Oui (overhead minimal)          |
| Graphe d'Appels           | Basique              | Détaillé avec recommandations   |
| Comparaison               | Manuelle             | Comparaison de profils intégrée |
| Intégration CI            | Limitée              | Intégration CI/CD complète      |
| Profilage Mémoire         | Basique              | Analyse du tas détaillée        |

## Installer Blackfire

```bash
# Installer la sonde (extension PHP)
curl -sSL https://packages.blackfire.io/gpg.key | sudo apt-key add -
echo "deb http://packages.blackfire.io/debian any main" | sudo tee /etc/apt/sources.list.d/blackfire.list
sudo apt-get update
sudo apt-get install blackfire-php

# Configurer les identifiants
blackfire config --client-id=xxx --client-token=xxx
```

## Profiler un Script

```bash
# Profiler un script CLI
blackfire run php my-script.php

# Profiler avec un scénario spécifique
blackfire --samples=10 run php heavy-computation.php
```

## Profiler des Requêtes HTTP

```php
<?php
// Installer le SDK Blackfire
// composer require blackfire/php-sdk

use Blackfire\Client;
use Blackfire\Profile\Configuration;

$blackfire = new Client();

$config = new Configuration();
$config->setTitle('Profil Page d\'Accueil');
$config->setSamples(10);

$probe = $blackfire->createProbe($config);

// Votre code à profiler
$result = processHeavyOperation();

$profile = $blackfire->endProbe($probe);
echo "URL du Profil : " . $profile->getUrl();
```

## Analyser les Résultats

Blackfire fournit :

```php
<?php
// Métriques clés à analyser
$metrics = [
    'wall_time' => 'Temps d\'exécution total',
    'cpu_time' => 'Temps de traitement CPU',
    'io_time' => 'Temps d\'attente I/O',
    'memory' => 'Utilisation mémoire de pointe',
    'network_in' => 'Données reçues',
    'network_out' => 'Données envoyées',
    'sql_queries' => 'Nombre de requêtes BDD',
    'http_requests' => 'Appels HTTP externes',
];
```

## Assertions pour CI/CD

```yaml
# .blackfire.yaml
scenarios:
  Accueil:
    - path: /
      assertions:
        - main.wall_time < 200ms
        - main.peak_memory < 50mb
        - metrics.sql.queries.count < 10

  Point API:
    - path: /api/users
      assertions:
        - main.wall_time < 100ms
        - metrics.http.requests.count == 0
```

## Profilage Comparatif

```bash
# Créer un profil de référence
blackfire --reference run php benchmark.php

# Comparer avec la référence
blackfire --reference=1 run php benchmark.php

# La sortie montre :
# - Wall Time: +15% (régression)
# - Memory: -5% (amélioration)
# - SQL Queries: identique
```

## Les Grimoires

- [Documentation Blackfire](https://blackfire.io/docs/introduction)

---

> 📘 _Cette leçon fait partie du cours [Optimisation des Performances PHP](/php/php-performance/) sur la plateforme d'apprentissage RostoDev._
