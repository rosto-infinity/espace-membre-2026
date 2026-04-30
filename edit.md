Cette fonction PHP a pour but de gérer l'upload (le téléversement) d'une image de profil (avatar) pour un membre, de la valider, de l'enregistrer sur le serveur et de mettre à jour le chemin de l'image dans la base de données.

Voici l'explication pas à pas :

### 1. La signature de la fonction
```php
function handleAvatar(PDO $pdo, int $id, array $file): ?string
```
*   **`PDO $pdo`** : On passe l'objet de connexion à la base de données pour pouvoir faire la requête SQL.
*   **`int $id`** : L'identifiant unique du membre (pour savoir quel utilisateur modifier).
*   **`array $file`** : C'est le tableau contenant les informations du fichier (généralement une partie de `$_FILES['mon_input']`).
*   **`: ?string`** : La fonction retourne soit une chaîne de caractères (un message d'erreur ou "success"), soit `null`.

---

### 2. Vérification si un fichier a été envoyé
```php
if (empty($file['name'])) return null;
```
Si le champ `name` du fichier est vide, cela signifie que l'utilisateur n'a pas sélectionné d'image. La fonction s'arrête immédiatement et renvoie `null` (ce qui indique qu'aucune action n'a été entreprise).

---

### 3. Préparation de l'extension et du chemin
```php
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$path = "membres/avatars/";
```
*   **`pathinfo`** : On extrait l'extension du fichier (ex: "jpg", "png").
*   **`strtolower`** : On met l'extension en minuscules pour éviter les problèmes (ex: `.JPG` devient `.jpg`).
*   **`$path`** : On définit le dossier où seront stockées les images.

---

### 4. Les validations de sécurité
```php
if (!in_array($ext, ['jpg', 'jpeg', 'gif', 'png'])) return "Format invalide.";
if ($file['size'] > 2097152) return "Fichier trop lourd.";
```
*   **Format** : On vérifie que l'extension fait partie de la liste autorisée. Si ce n'est pas le cas, on retourne un message d'erreur.
*   **Taille** : On vérifie que le fichier ne dépasse pas **2 Mo** ($2 \times 1024 \times 1024$ octets = $2\,097\,152$ octets).

---

### 5. Gestion du dossier et du nom du fichier
```php
if (!is_dir($path)) mkdir($path, 0777, true);
$filename = "$id.$ext";
```
*   **`is_dir` / `mkdir`** : Si le dossier `membres/avatars/` n'existe pas, PHP le crée automatiquement avec les permissions `0777` (lecture/écriture/exécution).
*   **Nommage** : Au lieu de garder le nom original du fichier (qui peut contenir des espaces ou des caractères spéciaux), on nomme le fichier avec l'**ID de l'utilisateur**. 
    *   *Avantage* : Si l'utilisateur change d'avatar, l'ancien est automatiquement écrasé car le nom sera le même.

---

### 6. Déplacement du fichier et mise à jour SQL
```php
if (move_uploaded_file($file['tmp_name'], $path . $filename)) {
    $pdo->prepare("UPDATE membres SET avatar = :v WHERE id = :id")
        ->execute(['v' => $filename, 'id' => $id]);
    return "success";
}
```
*   **`move_uploaded_file`** : PHP stocke d'abord le fichier dans un dossier temporaire. Cette fonction déplace le fichier du dossier temporaire vers le dossier final (`membres/avatars/12.jpg` par exemple).
*   **SQL** : Si le déplacement réussit, on met à jour la colonne `avatar` de la table `membres` pour enregistrer le nom du fichier.
*   **Retour** : On renvoie `"success"`.

---

### 7. Cas d'erreur final
```php
return "Erreur upload.";
```
Si `move_uploaded_file` a échoué (par exemple à cause d'un problème de droits d'écriture sur le serveur), la fonction arrive ici et retourne un message d'erreur.

### Résumé du flux :
1. **L'utilisateur a-t-il envoyé un fichier ?** $\rightarrow$ Non $\rightarrow$ `null`
2. **L'extension est-elle correcte ?** $\rightarrow$ Non $\rightarrow$ `"Format invalide."`
3. **Le fichier est-il trop gros ?** $\rightarrow$ Oui $\rightarrow$ `"Fichier trop lourd."`
4. **Le dossier existe-t-il ?** $\rightarrow$ Non $\rightarrow$ Création du dossier.
5. **Le transfert du fichier réussit-il ?** $\rightarrow$ Oui $\rightarrow$ Update BDD $\rightarrow$ `"success"`
6. **Sinon** $\rightarrow$ `"Erreur upload."`