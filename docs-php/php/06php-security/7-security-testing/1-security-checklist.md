---
source_course: "php-security"
source_lesson: "php-security-security-checklist"
---

# Check-list d'Audit de Sécurité

Utilisez cette check-list pour **auditer vos applications PHP** contre les vulnérabilités courantes.

## Gestion des Entrées

- [ ] Toutes les entrées utilisateur sont validées
- [ ] Validation par liste blanche là où c'est possible
- [ ] Déclarations de types (strict_types)
- [ ] Pas de $\_GET, $\_POST bruts utilisés dans le SQL
- [ ] Pas d'entrées utilisateur brutes affichées sur la page

## Sécurité SQL

- [ ] Toutes les requêtes utilisent des requêtes préparées
- [ ] PDO avec ATTR_EMULATE_PREPARES = false
- [ ] Noms de tables/colonnes validés contre une liste blanche
- [ ] Les messages d'erreur ne révèlent pas la structure de la base de données

## Sécurité de Sortie

- [ ] Toutes les sorties échappées avec htmlspecialchars()
- [ ] Header Content-Security-Policy défini
- [ ] X-Content-Type-Options: nosniff
- [ ] Header X-Frame-Options défini

## Authentification

- [ ] Mots de passe hashés avec password_hash()
- [ ] Session régénérée après connexion
- [ ] Paramètres de cookies de session sécurisés
- [ ] Verrouillage de compte après tentatives échouées
- [ ] Tokens CSRF sur tous les formulaires

## Gestion des Sessions

- [ ] Cookies HTTPOnly
- [ ] Flag Secure sur les cookies
- [ ] Attribut SameSite sur les cookies
- [ ] Délai d'expiration de session implémenté
- [ ] Données de session validées

## Gestion des Fichiers

- [ ] Uploads de fichiers validés par type
- [ ] Fichiers uploadés renommés
- [ ] Répertoire d'upload hors de la racine web
- [ ] Pas d'entrée utilisateur dans include/require

## Configuration

- [ ] display_errors = Off en production
- [ ] error_log activé
- [ ] Fonctions dangereuses désactivées
- [ ] open_basedir défini
- [ ] HTTPS obligatoire

## Dépendances

- [ ] Dépendances régulièrement mises à jour
- [ ] Vulnérabilités connues vérifiées (composer audit)
- [ ] Pas de paquets abandonnés

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
