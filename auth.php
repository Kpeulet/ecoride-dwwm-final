<?php
// On démarre la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// On vérifie si l'utilisateur est connecté, sinon on le redirige
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
