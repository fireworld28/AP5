<?php
//Connexion à la base de données, on accède le fichier credentials pour pouvoir connecter à la base de données
require('session/credentials.php');
$connexion = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $password);

//  On récupère les données des deux tables
$reqMach = $connexion->query('SELECT * FROM MACHINE');
$machines = $reqMach->fetchAll(\PDO::FETCH_ASSOC);
/*$machines = [];
while ($row = $reqMach->fetch(\PDO::FETCH_ASSOC)) {
    $machines[$row['id_mach']] = $row;
}*/

$reqMat = $connexion->query('SELECT * FROM MATERIEL');
$materiels = $reqMat->fetchAll(\PDO::FETCH_ASSOC);

// 3. On fusionne : on ajoute tout à la liste d'affichage
$affichage_complet = [];

foreach ($machines as $id => $m) {
    $affichage_complet[] = [
        'id'     => $m['id_mach'],
        'nom'    => $m['nom_mach'],
        'annee'  => $m['anne_mach'],
        'details'=> $m['det_mach'],
        'type'   => $m['typ_mach'],
        'parent' => null
    ];
}
foreach ($materiels as $m) {
    $affichage_complet[] = [
        'id'     => $m['id_mat'],
        'nom'    => $m['nom_mat'],
        'annee'  => $m['anne_mat'],
        'details'=> $m['det_mat'],
        'type'   => $m['typ_mat'],
        'parent' => $m['id_mach_par']
    ];
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
        <h1> Inventaire du parc informatique</h1>
        <p>Liste des machines et de leur matériel associé</p>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($affichage_complet as $item): ?>
                    <?php $est_principal = empty($item['parent']); ?>
                    <tr class="<?= $est_principal ? 'principal' : '' ?>">

                        <td><?= $item['id'] ?></td>

                        <td><?= htmlspecialchars($item['nom'] ?? '') ?></td>

                        <td><?= htmlspecialchars($item['annee'] ?? '') ?></td>

                        <td>
                            <?php if (!empty($item['details'])): ?>
                                <?= htmlspecialchars($item['details']) ?>
                            <?php else: ?>
                                <span class="vide">—</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <span class="badge <?= classeBadge($item['type'] ?? '') ?>">
                                <?= htmlspecialchars($item['type'] ?? '') ?>
                            </span>
                        </td>

                        <td>
                            <?php if (!$est_principal && isset($machines[$item['parent']])): ?>
                                <?= htmlspecialchars($machines[$item['parent']]['nom_mach']) ?>
                            <?php else: ?>
                                <span class="vide">—</span>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <footer>
        <p>AP4 GROUPE SIO — Inventaire SI &copy; <?= date('Y') ?></p>
    </footer>

</body>
</html>