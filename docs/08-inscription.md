# 08 — L'Inscription (inscription.php)

## L'objectif de cette page

Permettre à un visiteur de créer un compte. On doit :
1. Afficher un formulaire
2. Valider les données envoyées
3. Vérifier qu'aucun doublon n'existe (pseudo/email déjà pris)
4. Hasher le mot de passe
5. Insérer l'utilisateur en BDD
6. Rediriger avec un message flash

---

## Philosophie : une fonction par responsabilité

Plutôt qu'un gros bloc de code `if/else/if/else`, on encapsule la logique dans une **fonction `register()`** qui :
- Reçoit les données en paramètres
- Retourne une chaîne de caractères : `"success"` ou un **message d'erreur**

---

## Le code complet — `inscription.php`

```php
<?php
declare(strict_types=1);
session_start();
require_once 'db.php';
require_once 'flash.php';

// Rediriger si déjà connecté
if (isset($_SESSION['id'])) {
    header("Location: profil.php?id={$_SESSION['id']}");
    exit();
}

/**
 * Inscrit un nouvel utilisateur en base de données.
 * Retourne "success" ou un message d'erreur.
 */
function register(PDO $pdo, string $pseudo, string $mail, string $mdp, string $mdp2): string
{
    if (empty($pseudo) || empty($mail) || empty($mdp) || empty($mdp2)) {
        return "Tous les champs doivent être remplis.";
    }
    if (strlen($pseudo) > 255) return "Votre pseudo ne doit pas dépasser 255 caractères.";

    $stmt = $pdo->prepare("SELECT id FROM membres WHERE pseudo = :pseudo");
    $stmt->execute([':pseudo' => $pseudo]);
    if ($stmt->rowCount() > 0) return "Ce pseudo est déjà utilisé.";

    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) return "Adresse email invalide.";

    $stmt = $pdo->prepare("SELECT id FROM membres WHERE mail = :mail");
    $stmt->execute([':mail' => $mail]);
    if ($stmt->rowCount() > 0) return "Adresse mail déjà utilisée !";

    if (strlen($mdp) < 8 || !preg_match("#[0-9]+#", $mdp) || !preg_match("#[a-zA-Z]+#", $mdp)) {
        return "Mot de passe : 8 caractères min. avec une lettre et un chiffre.";
    }
    if ($mdp !== $mdp2) return "Les mots de passe ne correspondent pas !";

    $stmt = $pdo->prepare("INSERT INTO membres(pseudo, mail, motdepasse) VALUES(:pseudo, :mail, :mdp)");
    $stmt->execute([':pseudo' => $pseudo, ':mail' => $mail, ':mdp' => password_hash($mdp, PASSWORD_DEFAULT)]);

    return "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = strip_tags($_POST['pseudo'] ?? '');
    $mail   = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL) ?? '';
    $mdp    = $_POST['mdp'] ?? '';
    $mdp2   = $_POST['mdp2'] ?? '';

    $result = register($pdo, $pseudo, $mail, $mdp, $mdp2);

    if ($result === "success") {
        flash_set('success', "Compte créé avec succès ! Vous pouvez maintenant vous connecter.");
        header("Location: connexion.php");
        exit();
    }

    flash_set('error', $result);
    header("Location: inscription.php");
    exit();
}

include 'header.php';
?>

<div class="card">
    <h2 class="text-center" style="margin-bottom: 2rem;">Créer un compte</h2>

    <form method="POST" action="">
        <div class="form-group">
            <label for="pseudo">Pseudo</label>
            <input type="text" placeholder="Votre pseudo" id="pseudo" name="pseudo" required />
        </div>

        <div class="form-group">
            <label for="mail">Adresse E-mail</label>
            <input type="email" placeholder="votre@email.com" id="mail" name="mail" required />
        </div>

        <div class="form-group">
            <label for="mdp">Mot de passe</label>
            <input type="password" placeholder="8 caractères min. (lettre + chiffre)" id="mdp" name="mdp" required />
        </div>

        <div class="form-group">
            <label for="mdp2">Confirmer le mot de passe</label>
            <input type="password" placeholder="Confirmez votre mot de passe" id="mdp2" name="mdp2" required />
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 1rem;">
            S'inscrire gratuitement
        </button>

        <p class="text-center mt-4" style="font-size: 0.9rem; color: #94a3b8;">
            Déjà inscrit ? <a href="connexion.php" style="color: #42b883; text-decoration: none;">Se connecter</a>
        </p>
    </form>
</div>

<?php include 'footer.php'; ?>
```

---

## Analyse détaillée

### Nettoyage des données utilisateur

```php
$pseudo = strip_tags($_POST['pseudo'] ?? '');
$mail   = filter_input(INPUT_POST, 'mail', FILTER_SANITIZE_EMAIL) ?? '';
```

- **`strip_tags()`** : Supprime toutes les balises HTML. Si quelqu'un tape `<script>alert('hack')</script>`, ça devient juste une chaîne vide.
- **`filter_input(..., FILTER_SANITIZE_EMAIL)`** : Supprime les caractères illégaux dans un email.
- **`$_POST['pseudo'] ?? ''`** : Si la clé n'existe pas dans `$_POST`, retourne `''` au lieu de provoquer une erreur.

### Validation par Early Return

```php
function register(PDO $pdo, ...): string
{
    if (empty($pseudo)) return "Champs vides.";     // ← Retour immédiat
    if (strlen($pseudo) > 255) return "Trop long."; // ← Retour immédiat
    // ...
    // Si on arrive ici, tout est valide
    return "success";
}
```

La technique de "Early Return" signifie : dès qu'une condition échoue, on retourne immédiatement un message d'erreur. Le code principal n'est exécuté que si toutes les vérifications passent.

### Hashage du mot de passe

```php
password_hash($mdp, PASSWORD_DEFAULT)
```

- **Jamais** stocker un mot de passe en clair
- `password_hash()` génère un hash sécurisé. Chaque appel donne un résultat différent même avec le même mot de passe
- `PASSWORD_DEFAULT` utilise bcrypt, le meilleur algorithme disponible en PHP

### Le Pattern PRG (Post-Redirect-Get)

```php
// Succès
flash_set('success', "Compte créé !");
header("Location: connexion.php");
exit(); // ← TOUJOURS mettre exit() après header()

// Erreur
flash_set('error', $result);
header("Location: inscription.php");
exit();
```

---

> 🔐 **Résumé sécurité** : Nettoyage (`strip_tags`) → Validation (longueur, format) → Unicité (BDD) → Hashage (mot de passe) → Insertion.
