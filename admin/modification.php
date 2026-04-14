<?php
require('../session/verification.php');
require('../session/credentials.php');

$connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);
$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération des paramètres passés en GET
$table       = $_GET['table'] ?? '';
$identifiant = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!in_array($table, ['machine', 'materiel']) || $identifiant <= 0) {
    header('Location: ../index.php');
    exit;
}

// On récupère la liste des machines pour le menu déroulant (cas matériel)
$requeteMachines = $connexion->query('SELECT id_mach, nom_mach FROM machine ORDER BY id_mach');
$tableauMachines = $requeteMachines->fetchAll(PDO::FETCH_ASSOC);

$messageSucces = '';
$messageErreur = '';
$enregistrement = null;

// Récupération de l'enregistrement à modifier
if ($table === 'machine') {
    $requeteElement = $connexion->prepare('SELECT * FROM machine WHERE id_mach = :id');
    $requeteElement->execute([':id' => $identifiant]);
    $enregistrement = $requeteElement->fetch(PDO::FETCH_ASSOC);
} else {
    $requeteElement = $connexion->prepare('SELECT * FROM materiel WHERE id_mat = :id');
    $requeteElement->execute([':id' => $identifiant]);
    $enregistrement = $requeteElement->fetch(PDO::FETCH_ASSOC);
}

// Si l'enregistrement n'existe pas, on retourne à l'accueil
if (!$enregistrement) {
    header('Location: ../index.php');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tablePost = $_POST['table'] ?? '';

    if ($tablePost === 'machine') {
        $nom     = trim($_POST['nom_mach']);
        $annee   = (int)$_POST['anne_mach'];
        $details = trim($_POST['det_mach']);
        $type    = trim($_POST['typ_mach']);

        if (empty($nom) || $annee <= 0 || empty($type)) {
            $messageErreur = 'Tous les champs obligatoires doivent etre remplis.';
        } else {
            try {
                $requeteModification = $connexion->prepare(
                    'UPDATE machine SET nom_mach = :nom, anne_mach = :annee, det_mach = :details, typ_mach = :type
                     WHERE id_mach = :id'
                );
                $requeteModification->execute([
                    ':nom'     => $nom,
                    ':annee'   => $annee,
                    ':details' => $details ?: null,
                    ':type'    => $type,
                    ':id'      => $identifiant,
                ]);
                $messageSucces = 'Machine modifiee avec succes.';
                // On met à jour l'enregistrement local pour afficher les nouvelles valeurs
                $enregistrement['nom_mach']  = $nom;
                $enregistrement['anne_mach'] = $annee;
                $enregistrement['det_mach']  = $details;
                $enregistrement['typ_mach']  = $type;
            } catch (PDOException $erreurBase) {
                $messageErreur = 'Erreur : ' . $erreurBase->getMessage();
            }
        }

    } elseif ($tablePost === 'materiel') {
        $nom            = trim($_POST['nom_mat']);
        $annee          = (int)$_POST['anne_mat'];
        $details        = trim($_POST['det_mat']);
        $type           = trim($_POST['typ_mat']);
        $machineParente = (int)$_POST['id_mach_par'];

        if (empty($nom) || $annee <= 0 || empty($type) || $machineParente <= 0) {
            $messageErreur = 'Tous les champs obligatoires doivent etre remplis.';
        } else {
            try {
                $requeteModification = $connexion->prepare(
                    'UPDATE materiel SET nom_mat = :nom, anne_mat = :annee, det_mat = :details, typ_mat = :type, id_mach_par = :parent
                     WHERE id_mat = :id'
                );
                $requeteModification->execute([
                    ':nom'     => $nom,
                    ':annee'   => $annee,
                    ':details' => $details ?: null,
                    ':type'    => $type,
                    ':parent'  => $machineParente,
                    ':id'      => $identifiant,
                ]);
                $messageSucces = 'Materiel modifie avec succes.';
                $enregistrement['nom_mat']     = $nom;
                $enregistrement['anne_mat']    = $annee;
                $enregistrement['det_mat']     = $details;
                $enregistrement['typ_mat']     = $type;
                $enregistrement['id_mach_par'] = $machineParente;
            } catch (PDOException $erreurBase) {
                $messageErreur = 'Erreur : ' . $erreurBase->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier — Inventaire SI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1>Modifier un equipement</h1>
                <p><a href="../index.php" class="lien-retour">Retour a l'inventaire</a></p>
            </div>
            <nav class="header-nav">
                <a href="../deconnexion.php" class="btn btn-danger">Deconnexion</a>
            </nav>
        </div>
    </header>

    <div class="form-card">

        <?php if ($messageSucces): ?>
            <div class="alerte alerte-succes"><?= htmlspecialchars($messageSucces) ?></div>
        <?php endif; ?>
        <?php if ($messageErreur): ?>
            <div class="alerte alerte-erreur"><?= htmlspecialchars($messageErreur) ?></div>
        <?php endif; ?>

        <?php if ($table === 'machine'): ?>
            <h2>Modifier la machine n°<?= $enregistrement['id_mach'] ?></h2>
            <form method="POST" action="modification.php?table=machine&id=<?= $enregistrement['id_mach'] ?>">
                <input type="hidden" name="table" value="machine">
                <div class="champ">
                    <label for="nom_mach">Nom *</label>
                    <input type="text" id="nom_mach" name="nom_mach"
                           value="<?= htmlspecialchars($enregistrement['nom_mach']) ?>" required maxlength="30">
                </div>
                <div class="champ">
                    <label for="anne_mach">Annee *</label>
                    <input type="number" id="anne_mach" name="anne_mach"
                           value="<?= $enregistrement['anne_mach'] ?>" required min="2000" max="2099">
                </div>
                <div class="champ">
                    <label for="det_mach">Details</label>
                    <input type="text" id="det_mach" name="det_mach"
                           value="<?= htmlspecialchars($enregistrement['det_mach'] ?? '') ?>" maxlength="50">
                </div>
                <div class="champ">
                    <label for="typ_mach">Type *</label>
                    <select id="typ_mach" name="typ_mach" required>
                        <?php foreach (['PC', 'Écran', 'Autre'] as $option): ?>
                            <option value="<?= $option ?>" <?= $enregistrement['typ_mach'] === $option ? 'selected' : '' ?>>
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enregistrer les modifications</button>
            </form>

        <?php else: ?>
            <h2>Modifier le materiel n°<?= $enregistrement['id_mat'] ?></h2>
            <form method="POST" action="modification.php?table=materiel&id=<?= $enregistrement['id_mat'] ?>">
                <input type="hidden" name="table" value="materiel">
                <div class="champ">
                    <label for="nom_mat">Nom *</label>
                    <input type="text" id="nom_mat" name="nom_mat"
                           value="<?= htmlspecialchars($enregistrement['nom_mat']) ?>" required maxlength="30">
                </div>
                <div class="champ">
                    <label for="anne_mat">Annee *</label>
                    <input type="number" id="anne_mat" name="anne_mat"
                           value="<?= $enregistrement['anne_mat'] ?>" required min="2000" max="2099">
                </div>
                <div class="champ">
                    <label for="det_mat">Details</label>
                    <input type="text" id="det_mat" name="det_mat"
                           value="<?= htmlspecialchars($enregistrement['det_mat'] ?? '') ?>" maxlength="50">
                </div>
                <div class="champ">
                    <label for="typ_mat">Type *</label>
                    <select id="typ_mat" name="typ_mat" required>
                        <?php foreach (['CPU', 'RAM', 'Disque', 'GPU', 'Carte réseau', 'OS', 'Batterie', 'Autre'] as $option): ?>
                            <option value="<?= $option ?>" <?= $enregistrement['typ_mat'] === $option ? 'selected' : '' ?>>
                                <?= $option ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="champ">
                    <label for="id_mach_par">Machine parente *</label>
                    <select id="id_mach_par" name="id_mach_par" required>
                        <?php foreach ($tableauMachines as $machine): ?>
                            <option value="<?= $machine['id_mach'] ?>"
                                <?= $enregistrement['id_mach_par'] == $machine['id_mach'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($machine['nom_mach']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">Enregistrer les modifications</button>
            </form>
        <?php endif; ?>

    </div>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
