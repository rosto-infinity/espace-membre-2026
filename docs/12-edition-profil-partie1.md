# 12 — Édition du Profil : Fonctions Spécialisées (editionprofil.php) — Partie 1

## La philosophie : une fonction = une responsabilité

Cette page est la plus complexe du projet. Au lieu d'un seul bloc de code avec des dizaines de `if/else`, on crée **4 fonctions dédiées** :

| Fonction | Rôle |
|---|---|
| `isTaken()` | Vérifier si un pseudo/email est déjà pris |
| `handlePseudo()` | Gérer la mise à jour du pseudo |
| `handleEmail()` | Gérer la mise à jour de l'email |
| `handlePassword()` | Gérer la mise à jour du mot de passe |
| `handleAvatar()` | Gérer l'upload de la photo de profil |

---

## La fonction utilitaire `isTaken()`

```php
/**
 * Utility: Check if a value is already taken
 */
function isTaken(PDO $pdo, string $col, string $val, int $id): bool {
    $stmt = $pdo->prepare("SELECT id FROM membres WHERE $col = :val AND id != :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    return (bool)$stmt->fetch();
}
```

### Pourquoi `id != :id` ?
Quand un utilisateur modifie son pseudo, on vérifie que ce pseudo n'est pas pris par **quelqu'un d'autre**. Sans `id != :id`, si Alice garde son pseudo "Alice", le système lui dirait que "Alice est déjà pris" (par elle-même !).

### Exemple d'utilisation

```php
// Est-ce que "Bob" est déjà le pseudo de quelqu'un d'autre que l'utilisateur #5 ?
if (isTaken($pdo, 'pseudo', 'Bob', 5)) {
    echo "Pseudo déjà utilisé.";
}
```

---

## La fonction `handlePseudo()`

```php
/**
 * 1. Handle Pseudo Update
 */
function handlePseudo(PDO $pdo, int $id, string $val, string $current): ?string {
    // Si vide ou identique à l'actuel → ne rien faire
    if (empty($val) || $val === $current) return null;

    if (strlen($val) > 255) return "Pseudo trop long.";
    if (isTaken($pdo, 'pseudo', $val, $id)) return "Pseudo déjà utilisé.";
    
    $stmt = $pdo->prepare("UPDATE membres SET pseudo = :val WHERE id = :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    
    $_SESSION['pseudo'] = $val; // Mettre à jour la session aussi !
    return "success";
}
```

### La valeur de retour `?string`

Le `?` signifie que la fonction peut retourner `null` (PHP 8+ nullable types) :
- `null` → Rien à faire (champ vide ou inchangé)
- `"success"` → Mis à jour avec succès
- `"message d'erreur"` → Problème détecté

---

## La fonction `handleEmail()`

```php
/**
 * 2. Handle Email Update
 */
function handleEmail(PDO $pdo, int $id, ?string $val, string $current): ?string {
    if (empty($val) || $val === $current) return null;
    
    if (!filter_var($val, FILTER_VALIDATE_EMAIL)) return "Email invalide.";
    if (isTaken($pdo, 'mail', $val, $id)) return "Email déjà utilisé.";

    $stmt = $pdo->prepare("UPDATE membres SET mail = :val WHERE id = :id");
    $stmt->execute([':val' => $val, ':id' => $id]);
    
    $_SESSION['mail'] = $val; // Mettre à jour la session
    return "success";
}
```

### `filter_var($val, FILTER_VALIDATE_EMAIL)`
PHP dispose de filtres intégrés pour valider les formats. `FILTER_VALIDATE_EMAIL` vérifie que l'email a bien le format `quelquechose@domaine.extension`.

```php
filter_var("alice@email.com", FILTER_VALIDATE_EMAIL); // Retourne "alice@email.com" (valide)
filter_var("pas-un-email", FILTER_VALIDATE_EMAIL);    // Retourne false (invalide)
```

---

## La fonction `handlePassword()`

```php
/**
 * 3. Handle Password Update
 */
function handlePassword(PDO $pdo, int $id, string $p1, string $p2): ?string {
    // Si vide, l'utilisateur ne veut pas changer son mdp
    if (empty($p1)) return null;
    
    if ($p1 !== $p2) return "Les mots de passe ne correspondent pas.";
    if (strlen($p1) < 8) return "8 caractères minimum.";

    $stmt = $pdo->prepare("UPDATE membres SET motdepasse = :val WHERE id = :id");
    $stmt->execute([':val' => password_hash($p1, PASSWORD_DEFAULT), ':id' => $id]);
    
    return "success";
}
```

### Points importants
- Si `$p1` est vide, l'utilisateur n'a pas rempli le champ → on ne change pas le mot de passe
- On hash le nouveau mot de passe avec `password_hash()` avant de le sauvegarder
- On ne stocke **jamais** le mot de passe en clair

---

> Suite dans le fichier `13-edition-profil-partie2.md`
