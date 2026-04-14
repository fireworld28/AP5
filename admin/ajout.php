<?php
// Protection : seul un administrateur connecté peut accéder à cette page
require('../session/verification.php');
require('../session/credentials.php');

$connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);
$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// On détermine quelle table alimenter : 'machine' ou 'materiel'
$table = $_GET['table'] ?? 'machine';
if (!in_array($table, ['machine', 'materiel'])) {
    $table = 'machine';
}

// Si on vient de machine.php, la machine parente est pré-sélectionnée
$parentPreselectionne = isset($_GET['parent']) ? (int)$_GET['parent'] : null;

// On récupère la liste des machines pour le menu déroulant (cas matériel)
$requeteMachines = $connexion->query('SELECT id_mach, nom_mach FROM machine ORDER BY id_mach');
$tableauMachines = $requeteMachines->fetchAll(PDO::FETCH_ASSOC);

$messageSucces = '';
$messageErreur = '';

// Traitement du formulaire d'ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tablePost = $_POST['table'] ?? '';

    if ($tablePost === 'machine') {
        $identifiant = (int)$_POST['id_mach'];
        $nom         = trim($_POST['nom_mach']);
        $annee       = (int)$_POST['anne_mach'];
        $details     = trim($_POST['det_mach']);
        $type        = trim($_POST['typ_mach']);

        if ($identifiant <= 0 || empty($nom) || $annee <= 0 || empty($type)) {
            $messageErreur = 'Tous les champs obligatoires doivent etre remplis.';
        } else {
            try {
                $requeteAjout = $connexion->prepare(
                    'INSERT INTO machine (id_mach, nom_mach, anne_mach, det_mach, typ_mach)
                     VALUES (:id, :nom, :annee, :details, :type)'
                );
                $requeteAjout->execute([
                    ':id'      => $identifiant,
                    ':nom'     => $nom,
                    ':annee'   => $annee,
                    ':details' => $details ?: null,
                    ':type'    => $type,
                ]);
                $messageSucces = 'Machine ajoutee avec succes.';
            } catch (PDOException $erreurBase) {
                $messageErreur = 'Erreur lors de l\'ajout : ' . $erreurBase->getMessage();
            }
        }

    } elseif ($tablePost === 'materiel') {
        $identifiant  = (int)$_POST['id_mat'];
        $nom          = trim($_POST['nom_mat']);
        $annee        = (int)$_POST['anne_mat'];
        $details      = trim($_POST['det_mat']);
        $type         = trim($_POST['typ_mat']);
        $machineParente = (int)$_POST['id_mach_par'];

        if ($identifiant <= 0 || empty($nom) || $annee <= 0 || empty($type) || $machineParente <= 0) {
            $messageErreur = 'Tous les champs obligatoires doivent etre remplis.';
        } else {
            try {
                $requeteAjout = $connexion->prepare(
                    'INSERT INTO materiel (id_mat, nom_mat, anne_mat, det_mat, typ_mat, id_mach_par)
                     VALUES (:id, :nom, :annee, :details, :type, :parent)'
                );
                $requeteAjout->execute([
                    ':id'      => $identifiant,
                    ':nom'     => $nom,
                    ':annee'   => $annee,
                    ':details' => $details ?: null,
                    ':type'    => $type,
                    ':parent'  => $machineParente,
                ]);
                $messageSucces = 'Materiel ajoute avec succes.';
            } catch (PDOException $erreurBase) {
                $messageErreur = 'Erreur lors de l\'ajout : ' . $erreurBase->getMessage();
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
    <title>Ajouter — Inventaire SI</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1>Ajouter un equipement</h1>
                <p><a href="../index.php" class="lien-retour">Retour a l'inventaire</a></p>
            </div>
            <nav class="header-nav">
                <a href="../deconnexion.php" class="btn btn-danger">Deconnexion</a>
            </nav>
        </div>
    </header>

    <div class="form-card">

        <!-- Onglets pour choisir entre machine et matériel -->
        <div class="tab-switcher">
            <a href="ajout.php?table=machine"   class="tab <?= $table === 'machine'  ? 'active' : '' ?>">Machine</a>
            <a href="ajout.php?table=materiel"  class="tab <?= $table === 'materiel' ? 'active' : '' ?>">Materiel</a>
        </div>

        <?php if ($messageSucces): ?>
            <div class="alerte alerte-succes"><?= htmlspecialchars($messageSucces) ?></div>
        <?php endif; ?>
        <?php if ($messageErreur): ?>
            <div class="alerte alerte-erreur"><?= htmlspecialchars($messageErreur) ?></div>
        <?php endif; ?>

        <?php if ($table === 'machine'): ?>
            <!-- Formulaire d'ajout d'une machine -->
            <form method="POST" action="ajout.php">
                <input type="hidden" name="table" value="machine">
                <div class="champ">
                    <label for="id_mach">ID (entier unique) *</label>
                    <input type="number" id="id_mach" name="id_mach" required min="1">
                </div>
                <div class="champ">
                    <label for="nom_mach">Nom *</label>
                    <input type="text" id="nom_mach" name="nom_mach" required maxlength="30">
                </div>
                <div class="champ">
                    <label for="anne_mach">Annee *</label>
                    <input type="number" id="anne_mach" name="anne_mach" required min="2000" max="2099">
                </div>
                <div class="champ">
                    <label for="det_mach">Details</label>
                    <input type="text" id="det_mach" name="det_mach" maxlength="50">
                </div>
                <div class="champ">
                    <label for="typ_mach">Type *</label>
                    <select id="typ_mach" name="typ_mach" required>
                        <option value="">Choisir</option>
                        <option value="PC">PC</option>
                        <option value="Écran">Ecran</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-block">Ajouter la machine</button>
            </form>

        <?php else: ?>
            <!-- Formulaire d'ajout d'un matériel -->
            <form method="POST" action="ajout.php?table=materiel">
                <input type="hidden" name="table" value="materiel">
                <div class="champ">
                    <label for="id_mat">ID (entier unique) *</label>
                    <input type="number" id="id_mat" name="id_mat" required min="1">
                </div>
                <div class="champ">
                    <label for="nom_mat">Nom *</label>
                    <input type="text" id="nom_mat" name="nom_mat" required maxlength="30">
                </div>
                <div class="champ">
                    <label for="anne_mat">Annee *</label>
                    <input type="number" id="anne_mat" name="anne_mat" required min="2000" max="2099">
                </div>
                <div class="champ">
                    <label for="det_mat">Details</label>
                    <input type="text" id="det_mat" name="det_mat" maxlength="50">
                </div>
                <div class="champ">
                    <label for="typ_mat">Type *</label>
                    <select id="typ_mat" name="typ_mat" required>
                        <option value="">Choisir</option>
                        <option value="CPU">CPU</option>
                        <option value="RAM">RAM</option>
                        <option value="Disque">Disque</option>
                        <option value="GPU">GPU</option>
                        <option value="Carte réseau">Carte reseau</option>
                        <option value="OS">OS</option>
                        <option value="Batterie">Batterie</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                <div class="champ">
                    <label for="id_mach_par">Machine parente *</label>
                    <select id="id_mach_par" name="id_mach_par" required>
                        <option value="">Choisir une machine</option>
                        <?php foreach ($tableauMachines as $machine): ?>
                            <option value="<?= $machine['id_mach'] ?>"
                                <?= ($parentPreselectionne === $machine['id_mach']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($machine['nom_mach']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-success btn-block">Ajouter le materiel</button>
            </form>
        <?php endif; ?>

    </div>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
