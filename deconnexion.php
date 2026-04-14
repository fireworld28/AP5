<?php
session_start();

// On détruit la session pour déconnecter l'administrateur
session_destroy();

header('Location: index.php');
exit;
