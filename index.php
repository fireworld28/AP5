<?php
require('session/credentials.php');
session_start();

$connexion = new PDO("mysql:host=127.0.0.1;port=3307;dbname=$dbname;charset=$charset", $user, $password);
$connexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// On récupère toutes les machines, indexées par leur id
$requeteMachines = $connexion->query('SELECT * FROM machine ORDER BY id_mach');
$tableauMachines = [];
foreach ($requeteMachines->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
    $tableauMachines[$ligne['id_mach']] = $ligne;
}

// On récupère tous les matériels et on les regroupe par machine parente
$requeteMateriels = $connexion->query('SELECT * FROM materiel ORDER BY id_mach_par, id_mat');
$tableauMateriels = [];
foreach ($requeteMateriels->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
    $tableauMateriels[$ligne['id_mach_par']][] = $ligne;
}

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
    <title>Inventaire SI</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <div class="header-inner">
            <div>
                <h1>Inventaire du parc informatique</h1>
                <p>Liste des machines et de leur matériel associé</p>
            </div>
            <nav class="header-nav">
                <a href="recherche.php" class="btn btn-secondary">Recherche</a>
                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                    <a href="admin/ajout.php" class="btn btn-success">Ajouter</a>
                    <a href="deconnexion.php" class="btn btn-danger">Deconnexion</a>
                <?php else: ?>
                    <a href="connexion.php" class="btn btn-primary">Administration</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <div class="conteneur-tableau">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom</th>
                    <th>Année</th>
                    <th>Détails</th>
                    <th>Type</th>
                    <th>Appartient à</th>
                    <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                        <th>Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tableauMachines as $machine): ?>

                    <!-- Ligne de la machine principale -->
                    <tr class="principal">
                        <td>
                            <a href="machine.php?id=<?= $machine['id_mach'] ?>">
                                <?= $machine['id_mach'] ?>
                            </a>
                        </td>
                        <td>
                            <a href="machine.php?id=<?= $machine['id_mach'] ?>">
                                <?= htmlspecialchars($machine['nom_mach']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($machine['anne_mach']) ?></td>
                        <td>
                            <?php if (!empty($machine['det_mach'])): ?>
                                <?= htmlspecialchars($machine['det_mach']) ?>
                            <?php else: ?>
                                <span class="vide">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= classeBadge($machine['typ_mach']) ?>">
                                <?= htmlspecialchars($machine['typ_mach']) ?>
                            </span>
                        </td>
                        <td><span class="vide">—</span></td>
                        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                            <td class="actions">
                                <a href="admin/modification.php?table=machine&id=<?= $machine['id_mach'] ?>" class="btn-sm btn-edit">Modifier</a>
                                <a href="admin/suppression.php?table=machine&id=<?= $machine['id_mach'] ?>" class="btn-sm btn-delete"
                                   onclick="return confirm('Supprimer cette machine et tout son materiel ?')">Supprimer</a>
                            </td>
                        <?php endif; ?>
                    </tr>

                    <!-- Lignes des matériels associés à cette machine -->
                    <?php if (!empty($tableauMateriels[$machine['id_mach']])): ?>
                        <?php foreach ($tableauMateriels[$machine['id_mach']] as $materiel): ?>
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
                                <td><?= htmlspecialchars($machine['nom_mach']) ?></td>
                                <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] === true): ?>
                                    <td class="actions">
                                        <a href="admin/modification.php?table=materiel&id=<?= $materiel['id_mat'] ?>" class="btn-sm btn-edit">Modifier</a>
                                        <a href="admin/suppression.php?table=materiel&id=<?= $materiel['id_mat'] ?>" class="btn-sm btn-delete"
                                           onclick="return confirm('Supprimer ce materiel ?')">Supprimer</a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>

                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <footer>
        <p>AP5 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>
