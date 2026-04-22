# 18 — Tests et Débogage : Résoudre les erreurs courantes

Tout développeur passe 80% de son temps à déboguer. Voici comment identifier et résoudre les problèmes fréquents rencontrés lors de ce projet.

---

## 1. Activer les erreurs PHP

Par défaut, certains serveurs cachent les erreurs (page blanche). Pour les voir, ajoutez ceci en haut de votre fichier principal :

```php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
```

---

## 2. Erreurs SQL (PDO Exception)

Si une requête ne fonctionne pas, PDO lancera une exception grâce à notre configuration dans `db.php`.

**Erreur : `SQLSTATE[HY093]: Invalid parameter number`**
→ Signifie que vous avez envoyé plus (ou moins) de paramètres dans `execute()` que de marqueurs (`:nom`) dans `prepare()`.

**Erreur : `SQLSTATE[42S22]: Column not found`**
→ Une faute de frappe dans le nom d'une colonne de votre table `membres`.

---

## 3. Déboguer les variables avec `var_dump()`

Pour voir ce qu'il y a dans une variable (tableau, objet, session) :

```php
var_dump($_SESSION);
var_dump($_POST);
die(); // Arrête l'exécution pour lire tranquillement
```

Pour un affichage plus lisible :
```php
echo '<pre>';
print_r($userinfo);
echo '</pre>';
die();
```

---

## 4. Problèmes d'upload d'avatar

Si l'avatar ne s'affiche pas ou ne s'enregistre pas :
1. Vérifiez que le dossier `membres/avatars/` existe.
2. Vérifiez que PHP a les droits d'écriture sur ce dossier.
3. Vérifiez l'attribut `enctype="multipart/form-data"` dans votre balise `<form>`.

---

## 5. Erreurs de syntaxe communes

- **Oubli de `;`** à la fin d'une ligne.
- **Accolade `{` ou `}` non fermée**.
- **Oubli de `session_start()`** : les variables de session seront vides ou perdues.
- **Espaces avant `<?php`** : peut causer une erreur "Headers already sent" lors d'une redirection.

---

## 6. Utiliser la console du navigateur (F12)

Même si PHP est côté serveur, la console réseau (onglet "Network") de votre navigateur est utile pour voir :
- Si les fichiers CSS/Images sont bien chargés (Statut 200).
- Si une page PHP renvoie une erreur 500 (Erreur serveur).
- Ce qui est envoyé réellement dans vos formulaires.

---

> 🔎 **Conseil** : Quand vous avez un bug, ne changez pas tout votre code au hasard. Utilisez `var_dump()` pour suivre le chemin de votre donnée étape par étape jusqu'à trouver où elle "disparaît" ou change de valeur.
