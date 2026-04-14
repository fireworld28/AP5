<?php
session_start();

if (!isset($_SESSION['user_authenticated'])) {
    header('Location: ../connexion.php');
    exit;
}
?>