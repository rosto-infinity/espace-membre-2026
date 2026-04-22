# 19 — Mise en Ligne : Considérations de Déploiement

Une fois que votre projet fonctionne en local, comment le mettre en ligne en toute sécurité ?

---

## 1. Sécuriser les identifiants de BDD

Sur votre serveur de production, n'écrivez jamais vos mots de passe directement dans `db.php`. Utilisez des **variables d'environnement**.

Si vous ne pouvez pas utiliser d'environnement, créez un fichier de configuration séparé (`config.php`) qui est exclu de votre versioning (Git).

---

## 2. Configuration du serveur (Permissions)

Pour que l'upload d'avatar fonctionne, le serveur web (Apache ou Nginx) doit pouvoir écrire dans le dossier :
`membres/avatars/`

Sur Linux, cela se gère souvent avec la commande `chmod` ou en changeant le propriétaire du dossier :
```bash
chown -R www-data:www-data membres/avatars/
```

---

## 3. HTTPS est obligatoire

Un espace membre gère des identifiants et des mots de passe. Sans certificat **SSL/TLS (HTTPS)**, ces informations circulent en clair sur le réseau et peuvent être interceptées.
Utilisez "Let's Encrypt" pour obtenir un certificat gratuit.

---

## 4. Désactiver l'affichage des erreurs

En production, l'utilisateur ne doit jamais voir les erreurs techniques PHP ou SQL (cela donne des indices précieux aux pirates).

Dans votre `php.ini` de production :
```ini
display_errors = Off
log_errors = On
```
Les erreurs seront enregistrées dans un fichier log sur le serveur, mais pas affichées à l'écran.

---

## 5. Taille maximale des uploads

Vérifiez la configuration de votre serveur PHP pour autoriser les images :
```ini
upload_max_filesize = 5M
post_max_size = 8M
```
Si ces valeurs sont trop basses, PHP bloquera l'upload des avatars avant même que votre code ne soit exécuté.

---

## 6. Cookies sécurisés

Dans `session_start()`, vous pouvez ajouter des options pour renforcer la sécurité des cookies :
```php
session_start([
    'cookie_httponly' => true, // Empêche l'accès via JS
    'cookie_secure' => true,   // Uniquement via HTTPS
    'cookie_samesite' => 'Lax' // Protection contre CSRF
]);
```

---

> 🚀 **Prêt pour le monde réel** : Le déploiement est une étape délicate. Prenez le temps de vérifier ces points pour ne pas exposer les données de vos futurs utilisateurs.
