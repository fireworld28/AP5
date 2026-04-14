<?php
require('session/credentials.php');
session_start();

$connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);
$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Récupération de l'ID passé en GET, on vérifie que c'est bien un entier
$identifiant = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($identifiant <= 0) {
    header('Location: index.php');
    exit;
}

// On récupère la machine correspondante
$requeteMachine = $connexion->prepare('SELECT * FROM machine WHERE id_mach = :id');
$requeteMachine->execute([':id' => $identifiant]);
$machine = $requeteMachine->fetch(PDO::FETCH_ASSOC);

// Si la machine n'existe pas, on retourne à l'accueil
if (!$machine) {
    header('Location: index.php');
    exit;
}

// On récupère les matériels associés à cette machine
$requeteMateriels = $connexion->prepare('SELECT * FROM materiel WHERE id_mach_par = :id ORDER BY typ_mat');
$requeteMateriels->execute([':id' => $identifiant]);
$tableauMateriels = $requeteMateriels->fetchAll(PDO::FETCH_ASSOC);

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
    <title><?= htmlspecialchars($machine['nom_mach']) ?> — Inventaire SI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1><?= htmlspecialchars($machine['nom_mach']) ?></h1>
                <p><a href="index.php" class="lien-retour">Retour a l'inventaire</a></p>
            </div>
            <nav class="header-nav">
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                    <a href="admin/modification.php?table=machine&id=<?= $machine['id_mach'] ?>" class="btn btn-primary">Modifier</a>
                    <a href="admin/suppression.php?table=machine&id=<?= $machine['id_mach'] ?>" class="btn btn-danger"
                       onclick="return confirm('Supprimer cette machine et tout son materiel ?')">Supprimer</a>
                    <a href="deconnexion.php" class="btn btn-secondary">Deconnexion</a>
                <?php else: ?>
                    <a href="connexion.php" class="btn btn-primary">Administration</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <!-- Informations générales de la machine -->
    <div class="detail-card">
        <h2>Informations generales</h2>
        <table class="table-detail">
            <tr>
                <th>ID</th>
                <td><?= $machine['id_mach'] ?></td>
            </tr>
            <tr>
                <th>Nom</th>
                <td><?= htmlspecialchars($machine['nom_mach']) ?></td>
            </tr>
            <tr>
                <th>Année</th>
                <td><?= htmlspecialchars($machine['anne_mach']) ?></td>
            </tr>
            <tr>
                <th>Détails</th>
                <td>
                    <?php if (!empty($machine['det_mach'])): ?>
                        <?= htmlspecialchars($machine['det_mach']) ?>
                    <?php else: ?>
                        <span class="vide">—</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Type</th>
                <td>
                    <span class="badge <?= classeBadge($machine['typ_mach']) ?>">
                        <?= htmlspecialchars($machine['typ_mach']) ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Composants associés à cette machine -->
    <div class="detail-card">
        <div class="card-header-row">
            <h2>Composants associes (<?= count($tableauMateriels) ?>)</h2>
            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                <a href="admin/ajout.php?table=materiel&parent=<?= $machine['id_mach'] ?>" class="btn btn-success">Ajouter un composant</a>
            <?php endif; ?>
        </div>

        <?php if (empty($tableauMateriels)): ?>
            <p class="vide">Aucun composant enregistre pour cette machine.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Année</th>
                        <th>Détails</th>
                        <th>Type</th>
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                            <th>Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tableauMateriels as $materiel): ?>
                        <tr>
                            <td><?= $materiel['id_mat'] ?></td>
                            <td><?= htmlspecialchars($materiel['nom_mat']) ?></td>
                            <td><?= htmlspecialchars($materiel['anne_mat']) ?></td>
                            <td>
                                <?php if (!empty($materiel['det_mat'])): ?>
                                    <?= htmlspecialchars($materiel['det_mat']) ?>
                                <?php else: ?>
                                    <span class="vide">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= classeBadge($materiel['typ_mat']) ?>">
                                    <?= htmlspecialchars($materiel['typ_mat']) ?>
                                </span>
                            </td>
                            <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                <td class="actions">
                                    <a href="admin/modification.php?table=materiel&id=<?= $materiel['id_mat'] ?>" class="btn-sm btn-edit">Modifier</a>
                                    <a href="admin/suppression.php?table=materiel&id=<?= $materiel['id_mat'] ?>" class="btn-sm btn-delete"
                                       onclick="return confirm('Supprimer ce materiel ?')">Supprimer</a>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
