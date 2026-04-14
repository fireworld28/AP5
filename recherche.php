<?php
require('session/credentials.php');
session_start();

$connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);
$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération des critères de recherche depuis l'URL (méthode GET pour pouvoir partager le lien)
$motCle    = trim($_GET['q']         ?? '');
$filtrType = trim($_GET['type']      ?? '');
$anneeMin  = $_GET['annee_min'] !== '' && isset($_GET['annee_min']) ? (int)$_GET['annee_min'] : null;
$anneeMax  = $_GET['annee_max'] !== '' && isset($_GET['annee_max']) ? (int)$_GET['annee_max'] : null;

$tableauResultats = []; // On prépare un tableau vide

if (isset($_GET['annee_min']) && isset($_GET['annee_max'])) {
    $annee_min = $_GET['annee_min'];
    $annee_max = $_GET['annee_max'];
    
    // C'est ici que tu mets ta requête SQL de recherche
    // ex: SELECT * FROM machine WHERE anne_mach BETWEEN :min AND :max
}

// On ne lance la recherche que si au moins un critère est renseigné
$rechercheActive = $motCle !== '' || $filtrType !== '' || $anneeMin !== null || $anneeMax !== null;

if ($rechercheActive) {

    // Construction des conditions WHERE pour la table machine
    $conditionsMachine  = [];
    $parametresMachine  = [];

    // Construction des conditions WHERE pour la table materiel
    $conditionsMateriels = [];
    $parametresMateriels = [];

    if ($motCle !== '') {
        $conditionsMachine[]           = '(nom_mach LIKE :motcle1 OR det_mach LIKE :motcle2)';
        $parametresMachine[':motcle1'] = '%' . $motCle . '%';
        $parametresMachine[':motcle2'] = '%' . $motCle . '%';

        $conditionsMateriels[]            = '(nom_mat LIKE :motcle1 OR det_mat LIKE :motcle2)';
        $parametresMateriels[':motcle1']  = '%' . $motCle . '%';
        $parametresMateriels[':motcle2']  = '%' . $motCle . '%';
    }

    if ($filtrType !== '') {
        $conditionsMachine[]           = 'typ_mach = :type';
        $parametresMachine[':type']    = $filtrType;

        $conditionsMateriels[]         = 'typ_mat = :type';
        $parametresMateriels[':type']  = $filtrType;
    }

    if ($anneeMin !== null) {
        $conditionsMachine[]                = 'anne_mach >= :annee_min';
        $parametresMachine[':annee_min']    = $anneeMin;

        $conditionsMateriels[]              = 'anne_mat >= :annee_min';
        $parametresMateriels[':annee_min']  = $anneeMin;
    }

    if ($anneeMax !== null) {
        $conditionsMachine[]                = 'anne_mach <= :annee_max';
        $parametresMachine[':annee_max']    = $anneeMax;

        $conditionsMateriels[]              = 'anne_mat <= :annee_max';
        $parametresMateriels[':annee_max']  = $anneeMax;
    }

    $clauseMachine  = !empty($conditionsMachine)  ? 'WHERE ' . implode(' AND ', $conditionsMachine)  : '';
    $clauseMateriels = !empty($conditionsMateriels) ? 'WHERE ' . implode(' AND ', $conditionsMateriels) : '';

    // Requête sur la table machine
    $requeteMachine = $connexion->prepare(
        "SELECT id_mach AS id, nom_mach AS nom, anne_mach AS annee, det_mach AS details, typ_mach AS type,
                NULL AS id_parent, NULL AS nom_parent
         FROM machine $clauseMachine"
    );
    $requeteMachine->execute($parametresMachine);
    $resultsMachine = $requeteMachine->fetchAll(PDO::FETCH_ASSOC);

    // Requête sur la table materiel avec jointure pour récupérer le nom de la machine parente
    $requeteMateriels = $connexion->prepare(
        "SELECT m.id_mat AS id, m.nom_mat AS nom, m.anne_mat AS annee, m.det_mat AS details, m.typ_mat AS type,
                m.id_mach_par AS id_parent, ma.nom_mach AS nom_parent
         FROM materiel m
         LEFT JOIN machine ma ON m.id_mach_par = ma.id_mach
         $clauseMateriels"
    );
    $requeteMateriels->execute($parametresMateriels);
    $resultsMateriels = $requeteMateriels->fetchAll(PDO::FETCH_ASSOC);

    // On fusionne les deux tableaux de résultats
    $resultats = array_merge($resultsMachine, $resultsMateriels);
}

// Liste des types disponibles pour le menu déroulant
$typesDisponibles = ['PC', 'Écran', 'CPU', 'RAM', 'Disque', 'GPU', 'Carte réseau', 'OS', 'Batterie'];

// Fonction pour choisir la classe du badge selon le type
function classeBadge(string $type): string {
    $type = strtolower($type);
    $correspondances = [
        'pc'           => 'pc',
        'écran'        => 'ecran',
        'cpu'          => 'cpu',
        'ram'          => 'ram',
        'disque'       => 'disque',
        'os'           => 'os',
        'gpu'          => 'gpu',
        'carte réseau' => 'reseau',
        'batterie'     => 'batterie',
    ];
    return $correspondances[$type] ?? 'autre';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recherche — Inventaire SI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1>Recherche multicritere</h1>
                <p><a href="index.php" class="lien-retour">Retour a l'inventaire</a></p>
            </div>
            <nav class="header-nav">
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                    <a href="admin/ajout.php" class="btn btn-success">Ajouter</a>
                    <a href="deconnexion.php" class="btn btn-danger">Deconnexion</a>
                <?php else: ?>
                    <a href="connexion.php" class="btn btn-primary">Administration</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Formulaire de recherche en GET pour que l'URL soit partageable -->
    <div class="form-card">
        <form method="GET" action="recherche.php">
            <div class="champ">
                <label for="q">Mot-cle (nom ou details)</label>
                <input type="text" id="q" name="q" value="<?= htmlspecialchars($motCle) ?>" placeholder="ex : PC 1, Intel, SSD...">
            </div>
            <div class="form-ligne">
                <div class="champ">
                    <label for="type">Type</label>
                    <select id="type" name="type">
                        <option value="">Tous</option>
                        <?php foreach ($typesDisponibles as $type): ?>
                            <option value="<?= $type ?>" <?= $filtrType === $type ? 'selected' : '' ?>>
                                <?= $type ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="champ">
                    <label for="annee_min">Annee min</label>
                    <input type="number" id="annee_min" name="annee_min" value="<?= $anneeMin ?? '' ?>" min="2000" max="2099" placeholder="ex : 2015">
                </div>
                <div class="champ">
                    <label for="annee_max">Annee max</label>
                    <input type="number" id="annee_max" name="annee_max" value="<?= $anneeMax ?? '' ?>" min="2000" max="2099" placeholder="ex : 2020">
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Rechercher</button>
        </form>
    </div>

    <!-- Affichage des résultats uniquement si une recherche a été lancée -->
    <?php if ($rechercheActive): ?>
        <div class="conteneur-tableau">
            <div class="resultats-header">
                <?= count($resultats) ?> resultat(s) trouve(s)
            </div>
            <?php if (empty($resultats)): ?>
                <p class="vide" style="padding: 20px;">Aucun equipement ne correspond a cette recherche.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Année</th>
                            <th>Détails</th>
                            <th>Type</th>
                            <th>Appartient à</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($resultats as $ligne): ?>
                            <tr class="<?= $ligne['id_parent'] === null ? 'principal' : '' ?>">
                                <td><?= htmlspecialchars($ligne['id']) ?></td>
                                <td>
                                    <?php if ($ligne['id_parent'] === null): ?>
                                        <a href="machine.php?id=<?= $ligne['id'] ?>"><?= htmlspecialchars($ligne['nom']) ?></a>
                                    <?php else: ?>
                                        <?= htmlspecialchars($ligne['nom']) ?>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($ligne['annee']) ?></td>
                                <td>
                                    <?php if (!empty($ligne['details'])): ?>
                                        <?= htmlspecialchars($ligne['details']) ?>
                                    <?php else: ?>
                                        <span class="vide">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?= classeBadge($ligne['type']) ?>">
                                        <?= htmlspecialchars($ligne['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($ligne['nom_parent'])): ?>
                                        <?= htmlspecialchars($ligne['nom_parent']) ?>
                                    <?php else: ?>
                                        <span class="vide">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
