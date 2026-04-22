---
source_course: "php-security"
source_lesson: "php-security-file-upload-security"
---

# Gestion Sécurisée des Uploads de Fichiers

Les uploads de fichiers sont un vecteur d'attaque courant. Une gestion incorrecte peut mener à l'**exécution de code à distance**, au path traversal, et au déni de service.

## Les Dangers des Uploads de Fichiers

1. **Exécution de Code à Distance** : Uploader des fichiers PHP qui sont exécutés
2. **Path Traversal** : Utiliser `../` pour écrire en dehors du répertoire d'upload
3. **Usurpation MIME** : Déguiser des fichiers malveillants avec de fausses extensions
4. **Déni de Service** : Uploader des fichiers énormes pour épuiser le stockage

## Gestionnaire d'Upload Sécurisé

```php
<?php
class SecureFileUpload
{
    private const ALLOWED_TYPES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    ];

    private const MAX_SIZE = 5 * 1024 * 1024;  // 5Mo

    public function __construct(
        private string $uploadDir
    ) {
        // S'assurer que le répertoire d'upload est hors de la racine web
        if (str_starts_with(realpath($this->uploadDir), $_SERVER['DOCUMENT_ROOT'])) {
            throw new RuntimeException('Le répertoire d\'upload doit être en dehors de la racine web');
        }
    }

    public function upload(array $file): string
    {
        // 1. Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new UploadException($this->getUploadErrorMessage($file['error']));
        }

        // 2. Valider la taille du fichier
        if ($file['size'] > self::MAX_SIZE) {
            throw new UploadException('Fichier trop volumineux');
        }

        // 3. Valider le type MIME (vérifier le contenu réel, pas l'extension)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!isset(self::ALLOWED_TYPES[$mimeType])) {
            throw new UploadException('Type de fichier invalide');
        }

        // 4. Générer un nom de fichier sûr (ne jamais utiliser le nom original !)
        $extension = self::ALLOWED_TYPES[$mimeType];
        $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;

        // 5. Déplacer vers un emplacement sécurisé
        $destination = $this->uploadDir . '/' . $newFilename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new UploadException('Échec de l\'enregistrement du fichier');
        }

        // 6. Définir des permissions restrictives
        chmod($destination, 0644);

        return $newFilename;
    }

    private function getUploadErrorMessage(int $error): string
    {
        return match($error) {
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse MAX_FILE_SIZE du formulaire',
            UPLOAD_ERR_PARTIAL => 'Téléchargement partiel seulement',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture du fichier',
            default => 'Erreur d\'upload inconnue',
        };
    }
}
```

## Servir les Fichiers Uploadés de Façon Sécurisée

```php
<?php
class SecureFileServer
{
    public function serve(string $filename): void
    {
        // Valider le format du nom de fichier
        if (!preg_match('/^[a-f0-9]{32}\.(jpg|png|gif|pdf)$/', $filename)) {
            http_response_code(400);
            exit('Nom de fichier invalide');
        }

        $path = '/var/uploads/' . $filename;  // En dehors de la racine web

        if (!file_exists($path)) {
            http_response_code(404);
            exit('Fichier introuvable');
        }

        // Déterminer le type de contenu
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        // Headers de sécurité
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('X-Content-Type-Options: nosniff');
        header('Content-Security-Policy: default-src \'none\'');

        // Servir le fichier
        readfile($path);
    }
}
```

## Validation Spécifique aux Images

```php
<?php
function validateImage(string $filepath): bool
{
    // Essayer de charger comme image
    $imageInfo = @getimagesize($filepath);

    if ($imageInfo === false) {
        return false;  // Pas une image valide
    }

    // Vérifier le type d'image
    $allowedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF];
    if (!in_array($imageInfo[2], $allowedTypes, true)) {
        return false;
    }

    // Ré-encoder pour supprimer tout code embarqué
    $image = match($imageInfo[2]) {
        IMAGETYPE_JPEG => imagecreatefromjpeg($filepath),
        IMAGETYPE_PNG => imagecreatefrompng($filepath),
        IMAGETYPE_GIF => imagecreatefromgif($filepath),
    };

    // Sauvegarder l'image ré-encodée (supprime tout PHP embarqué)
    imagejpeg($image, $filepath, 90);
    imagedestroy($image);

    return true;
}
```

## Les Grimoires

- [Uploads de Fichiers (Documentation Officielle)](https://www.php.net/manual/en/features.file-upload.php)

---

> 📘 _Cette leçon fait partie du cours [Ingénierie de Sécurité PHP](/php/php-security/) sur la plateforme d'apprentissage RostoDev._
