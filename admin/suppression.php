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

// Suppression selon la table ciblée
// Pour la table machine, la contrainte ON DELETE CASCADE dans la base de données
// supprime automatiquement tous les matériels associés à cette machine
if ($table === 'machine') {
    $requeteSuppression = $connexion->prepare('DELETE FROM machine WHERE id_mach = :id');
    $requeteSuppression->execute([':id' => $identifiant]);
} elseif ($table === 'materiel') {
    $requeteSuppression = $connexion->prepare('DELETE FROM materiel WHERE id_mat = :id');
    $requeteSuppression->execute([':id' => $identifiant]);
}

// Redirection vers l'accueil après la suppression
header('Location: ../index.php');
exit;
