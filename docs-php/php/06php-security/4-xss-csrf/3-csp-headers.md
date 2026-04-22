---
source_course: "php-security"
source_lesson: "php-security-csp-headers"
---

# La Politique de Sécurité du Contenu (CSP)

La CSP est **une puissante défense contre le XSS** qui indique aux navigateurs quelles ressources sont autorisées à charger et s'exécuter.

## Header CSP de Base

```php
<?php
// Politique par défaut restrictive
header("Content-Security-Policy: default-src 'self'");

// Cela prévient :
// - Les scripts inline (<script>alert(1)</script>)
// - Les styles inline
// - Le chargement de ressources depuis d'autres domaines
// - eval() et fonctions similaires
```

## Directives CSP Granulaires

```php
<?php
function setCSP(): void
{
    $directives = [
        // Fallback par défaut
        "default-src 'self'",

        // Scripts : domaine propre + CDN spécifique
        "script-src 'self' https://cdn.example.com",

        // Styles : domaine propre + inline (pour les frameworks)
        "style-src 'self' 'unsafe-inline'",

        // Images depuis partout
        "img-src 'self' data: https:",

        // Polices depuis le domaine propre et Google
        "font-src 'self' https://fonts.gstatic.com",

        // AJAX/Fetch vers le domaine propre seulement
        "connect-src 'self'",

        // Frames : aucune
        "frame-src 'none'",

        // Soumissions de formulaires vers le domaine propre
        "form-action 'self'",

        // Ne pas autoriser l'intégration dans des frames
        "frame-ancestors 'none'",

        // Bloquer le contenu mixte
        "upgrade-insecure-requests",
    ];

    header('Content-Security-Policy: ' . implode('; ', $directives));
}
```

## Utiliser les Nonces pour les Scripts Inline

```php
<?php
// Générer un nonce pour cette requête
$nonce = base64_encode(random_bytes(16));

// Définir le header CSP avec le nonce
header("Content-Security-Policy: script-src 'nonce-$nonce'");

// Stocker pour utilisation dans les templates
$_REQUEST['csp_nonce'] = $nonce;
```

```html
<!-- Seuls les scripts avec le nonce correspondant s'exécuteront -->
<script nonce="<?= htmlspecialchars($nonce) ?>">
  // Autorisé
  console.log("Ceci s'exécute");
</script>

<script>
  // Bloqué !
  console.log("Ceci est bloqué");
</script>
```

## CSP pour les APIs

```php
<?php
// Les APIs doivent avoir une CSP très restrictive
function setApiCSP(): void
{
    header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
    header('X-Content-Type-Options: nosniff');
}
```

## Mode Rapport Seulement (Test)

```php
<?php
// Tester la CSP sans bloquer
header(
    "Content-Security-Policy-Report-Only: " .
    "default-src 'self'; " .
    "report-uri /csp-report"
);
```

```php
<?php
// Endpoint /csp-report pour collecter les violations
$report = json_decode(file_get_contents('php://input'), true);

if (isset($report['csp-report'])) {
    $violation = $report['csp-report'];
    error_log(sprintf(
        "Violation CSP : %s a bloqué %s depuis %s",
        $violation['violated-directive'],
        $violation['blocked-uri'],
        $violation['document-uri']
    ));
}
```

## Erreurs CSP Courantes

```php
<?php
// MAUVAIS : 'unsafe-inline' annule la protection XSS
header("Content-Security-Policy: script-src 'self' 'unsafe-inline'");

// MAUVAIS : 'unsafe-eval' autorise les attaques eval()
header("Content-Security-Policy: script-src 'self' 'unsafe-eval'");

// MAUVAIS : Le wildcard autorise tout sous-domaine
header("Content-Security-Policy: script-src *.example.com");

// BON : Sources spécifiques avec nonces
header("Content-Security-Policy: script-src 'self' 'nonce-abc123'");
```

## Les Grimoires

- [Content Security Policy (MDN)](https://developer.mozilla.org/fr/docs/Web/HTTP/CSP)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
