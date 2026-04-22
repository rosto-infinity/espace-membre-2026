---
source_course: "php-security"
source_lesson: "php-security-threat-landscape"
---

# Comprendre les Menaces de Sécurité Web

Les applications web font face à des **menaces de sécurité constantes**. Comprendre ces menaces est la première étape pour s'en défendre.

## OWASP Top 10 pour PHP

L'Open Web Application Security Project (OWASP) identifie les risques de sécurité web les plus critiques :

1. **Injection** (SQL, Commande, LDAP)
2. **Authentification Défaillante**
3. **Exposition de Données Sensibles**
4. **Entités XML Externes (XXE)**
5. **Contrôle d'Accès Défaillant**
6. **Mauvaise Configuration de Sécurité**
7. **Cross-Site Scripting (XSS)**
8. **Désérialisation Non Sécurisée**
9. **Utilisation de Composants avec des Vulnérabilités Connues**
10. **Journalisation & Surveillance Insuffisantes**

## Vecteurs d'Attaque

### Entrées Utilisateur

```php
<?php
// Chaque entrée utilisateur est potentiellement malveillante
$_GET['search'];      // Paramètres URL
$_POST['username'];   // Données de formulaire
$_COOKIE['session'];  // Cookies
$_FILES['upload'];    // Uploads de fichiers
$_SERVER['HTTP_*'];   // Headers HTTP
file_get_contents('php://input');  // Corps brut de la requête POST
```

### Frontières de Confiance

```
[Zone Non Fiable]      [Frontière de Confiance]    [Zone Fiable]

  Entrée Utilisateur ──► Validation ─────────────► Application
  APIs Externes ───────► Assainissement ─────────► Base de données
  Uploads Fichiers ────► Autorisation ─────────── ► Système de fichiers
```

## Défense en Profondeur

Ne jamais se reposer sur une seule mesure de sécurité :

```php
<?php
// Couche 1 : Validation des entrées
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);

// Couche 2 : Requête paramétrée (prévention injection SQL)
$stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
$stmt->execute(['email' => $email]);

// Couche 3 : Contrôle d'accès
if (!$user->canViewData($result)) {
    throw new ForbiddenException();
}

// Couche 4 : Encodage de sortie (prévention XSS)
echo htmlspecialchars($result['name'], ENT_QUOTES, 'UTF-8');
```

## Principes de Sécurité

| Principe                              | Description                                         |
| ------------------------------------- | --------------------------------------------------- |
| Moindre Privilège                     | Accorder les permissions minimales nécessaires      |
| Défense en Profondeur                 | Plusieurs couches de sécurité                       |
| Échec Sécurisé                        | Les erreurs doivent refuser l'accès, pas l'accorder |
| Ne Jamais Faire Confiance aux Entrées | Valider tout ce qui vient des utilisateurs          |
| Garder la Simplicité                  | Le code complexe a plus de vulnérabilités           |

## Les Grimoires

- [Manuel de Sécurité PHP (Documentation Officielle)](https://www.php.net/manual/en/security.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
