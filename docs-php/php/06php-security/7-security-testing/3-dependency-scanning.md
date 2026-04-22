---
source_course: "php-security"
source_lesson: "php-security-dependency-scanning"
---

# Analyse de Sécurité des Dépendances

Votre application est aussi sécurisée que ses dépendances. **Analysez régulièrement les vulnérabilités connues** dans les paquets tiers.

## Composer Audit

```bash
# Audit de sécurité intégré (Composer 2.4+)
composer audit

# La sortie montre :
# - Nom du paquet
# - Version installée
# - Identifiants CVE
# - Niveau de sévérité
# - Détails du conseil

# Code de sortie : non-zéro si des vulnérabilités sont trouvées (utile pour CI)
composer audit && echo 'Aucune vulnérabilité'
```

## Intégration CI Automatisée

```yaml
# GitHub Actions
name: Sécurité
on: [push, pull_request]

jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Configurer PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"

      - name: Installer les dépendances
        run: composer install --no-dev

      - name: Audit de sécurité
        run: composer audit --format=json > audit.json

      - name: Uploader le rapport
        uses: actions/upload-artifact@v3
        with:
          name: security-audit
          path: audit.json
```

## Base de Données des Conseils de Sécurité PHP

```bash
# Installer le vérificateur de sécurité local
composer require --dev roave/security-advisories:dev-latest

# Ce paquet n'a pas de code - il entre en conflit avec les paquets vulnérables
# Composer refusera d'installer les versions vulnérables connues
```

## Analyse Statique pour la Sécurité

```bash
# PHPStan avec règles de sécurité
composer require --dev phpstan/phpstan
composer require --dev phpstan/phpstan-strict-rules

# psalm avec analyse de contamination
composer require --dev vimeo/psalm

# Exécuter l'analyse de contamination (suit le flux des entrées utilisateur)
./vendor/bin/psalm --taint-analysis
```

## Analyse de Contamination Psalm

```php
<?php
/**
 * @psalm-taint-source input
 */
function getUserInput(): string
{
    return $_GET['data'];
}

/**
 * @psalm-taint-sink sql
 */
function executeQuery(string $sql): void
{
    // Si des données contaminées arrivent ici, Psalm le signale
    $pdo->query($sql);
}

// Psalm signalera ceci comme des données contaminées atteignant un sink SQL
$input = getUserInput();
executeQuery("SELECT * FROM users WHERE name = '$input'");
```

## Suite de Tests de Sécurité

```php
<?php
class SecurityTestSuite
{
    public function run(): array
    {
        return [
            'sql_injection' => $this->testSqlInjection(),
            'xss' => $this->testXss(),
            'csrf' => $this->testCsrf(),
            'auth_bypass' => $this->testAuthBypass(),
            'path_traversal' => $this->testPathTraversal(),
            'open_redirect' => $this->testOpenRedirect(),
        ];
    }

    private function testSqlInjection(): bool
    {
        $payloads = ["' OR '1'='1", "1; DROP TABLE users", "1 UNION SELECT"];

        foreach ($payloads as $payload) {
            $response = $this->request('/api/users/' . urlencode($payload));

            // Vérifier les messages d'erreur SQL dans la réponse
            if (preg_match('/SQL|syntax|mysql|ORA-/i', $response)) {
                return false;  // Vulnérabilité trouvée
            }
        }

        return true;
    }

    // Méthodes de test supplémentaires...
}
```

## Les Grimoires

- [Composer Audit (Documentation Officielle)](https://getcomposer.org/doc/03-cli.md#audit)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
