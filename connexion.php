<?php
require('session/credentials.php');
session_start();

// Si l'admin est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
    header('Location: index.php');
    exit;
}

$erreur = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    // On compare le mot de passe saisi avec celui défini dans credentials.php
    if ($motDePasse === $adminpassword) {
        $_SESSION['admin'] = true;
        header('Location: index.php');
        exit;
    } else {
        $erreur = 'Mot de passe incorrect.';
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Inventaire SI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1>Connexion Administration</h1>
                <p><a href="index.php" class="lien-retour">Retour a l'inventaire</a></p>
            </div>
        </div>
    </header>

    <div class="form-card">
        <h2>Acces reserve</h2>
        <p>Entrez le mot de passe administrateur pour acceder aux fonctions de gestion.</p>

        <?php if ($erreur): ?>
            <div class="alerte alerte-erreur"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" action="connexion.php">
            <div class="champ">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" autofocus required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Se connecter</button>
        </form>
    </div>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
