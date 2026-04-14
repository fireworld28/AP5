<?php
// Ce fichier est inclus en haut de chaque page d'administration avec require()
// Il vérifie que la session admin est active, sinon il redirige vers la page de connexion

session_start();

if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    // L'utilisateur n'est pas connecté : on le redirige vers la connexion
    // On remonte d'un niveau car ce fichier est dans le dossier /session/
    header('Location: ../connexion.php');
    exit;
}
