<?php
session_start();
require_once '../config/db.php';

// Sécurité : Vérifier que l'utilisateur est connecté et qu'un ID est fourni
if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: ../mon_espace.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_trajet = (int)$_GET['id'];

try {
    // On vérifie que le trajet appartient bien au chauffeur connecté et qu'il est 'ouvert'
    $stmt = $pdo->prepare("SELECT id FROM trajets WHERE id = ? AND id_chauffeur = ? AND statut = 'ouvert'");
    $stmt->execute([$id_trajet, $user_id]);
    $trajet = $stmt->fetch();

    if ($trajet) {
        // Mise à jour du statut à 'en cours' (aligné sur le style sans underscore de ta BDD)
        $update = $pdo->prepare("UPDATE trajets SET statut = 'en cours' WHERE id = ?");
        $update->execute([$id_trajet]);
        
        header('Location: ../mon_espace.php?success=Le+trajet+a+commencé+!+Bonne+route.');
    } else {
        header('Location: ../mon_espace.php?error=Impossible+de+démarrer+ce+trajet.');
    }
    exit;

} catch (PDOException $e) {
    header('Location: ../mon_espace.php?error=Erreur+technique+lors+du+démarrage.');
    exit;
}