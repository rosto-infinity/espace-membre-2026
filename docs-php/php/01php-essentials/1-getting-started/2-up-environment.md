---
source_course: "php-essentials"
source_lesson: "php-essentials-setting-up-environment"
---

# Configurer votre environnement de développement

Avant d'écrire du code PHP, vous avez besoin d'un environnement de développement local. Celui-ci comprend généralement un serveur web, l'interpréteur PHP et optionnellement une base de données.

## Option 1 : Paquets Tout-en-Un (Recommandé pour les débutants)

Ces paquets regroupent tout ce dont vous avez besoin dans une seule installation :

### XAMPP (Multiplateforme)

```bash
# Télécharger sur https://www.apachefriends.org/
# Inclus : Apache + MariaDB + PHP + Perl
```

### MAMP (macOS/Windows)

```bash
# Télécharger sur https://www.mamp.info/
# Inclus : Apache/Nginx + MySQL + PHP
```

### Laragon (Windows - Un choix moderne et léger)

```bash
# Télécharger sur https://laragon.org/
# Inclus : Apache/Nginx + MySQL + PHP + Node.js
```

## Option 2 : Docker (Installation professionnelle)

Pour un environnement plus professionnel, isolé et reproductible :

```yaml
# docker-compose.yml
version: "3.8"
services:
  php:
    image: php:8.4-apache
    ports:
      - "8080:80"
    volumes:
      - ./src:/var/www/html
```

Lancer avec : `docker-compose up -d`

## Option 3 : Serveur intégré de PHP (Tests rapides)

PHP inclut un serveur de développement pour des tests rapides sans configuration complexe :

```bash
# Naviguez vers le dossier de votre projet
cd /chemin/vers/votre/projet

# Démarrez le serveur intégré
php -S localhost:8000
```

## Vérifier votre installation

Créez un fichier nommé `info.php` à la racine de votre serveur :

```php
<?php
phpinfo();
?>
```

Visitez `http://localhost/info.php` (ou `http://localhost:8000/info.php`) dans votre navigateur. Vous devriez voir la page de configuration détaillée de PHP.

## Éditeurs de code recommandés

- **VS Code** avec l'extension "PHP Intelephense"
- **PhpStorm** (le plus complet, professionnel, payant)
- **Sublime Text** avec des paquets PHP dédiés

## Exemples de code

**Créez ce fichier pour vérifier que PHP fonctionne correctement**

```php
<?php
// info.php - Testez votre installation PHP
phpinfo();
?>
```

## Ressources

- [Guide d'installation PHP](https://www.php.net/manual/fr/install.php) — Documentation officielle d'installation pour toutes les plateformes

---

> 📘 _Cette leçon fait partie du cours [PHP Essentials](/php/php-essentials/) sur la plateforme d'apprentissage RostoDev._
