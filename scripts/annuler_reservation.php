<?php
session_start();
require_once '../config/db.php';

// 1. Sécurité : Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../mon_espace.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération souple de l'ID passé par le bouton
$id_trajet = 0;
if (isset($_GET['id_trajet']) && (int)$_GET['id_trajet'] > 0) {
    $id_trajet = (int)$_GET['id_trajet'];
} elseif (isset($_GET['id']) && (int)$_GET['id'] > 0) {
    $id_trajet = (int)$_GET['id'];
}

if ($id_trajet <= 0) {
    header('Location: ../mon_espace.php?error=Impossible+de+récupérer+l+ID+du+trajet.');
    exit;
}

try {
    // 2. Début de la transaction SQL
    $pdo->beginTransaction();

    // 3. Récupérer le prix et vérifier que la réservation existe pour cet utilisateur
    $stmt = $pdo->prepare("
        SELECT r.id AS res_id, t.id AS trajet_id, t.prix, t.statut 
        FROM reservations r
        JOIN trajets t ON r.id_trajet = t.id
        WHERE r.id_trajet = ? AND r.id_utilisateur = ?
    ");
    $stmt->execute([$id_trajet, $user_id]);
    $reservation = $stmt->fetch();

    // Recherche de secours si l'ID envoyé était l'ID de la table réservation
    if (!$reservation) {
        $stmtSecours = $pdo->prepare("
            SELECT r.id AS res_id, t.id AS trajet_id, t.prix, t.statut 
            FROM reservations r
            JOIN trajets t ON r.id_trajet = t.id
            WHERE r.id = ? AND r.id_utilisateur = ?
        ");
        $stmtSecours->execute([$id_trajet, $user_id]);
        $reservation = $stmtSecours->fetch();
    }

    if (!$reservation) {
        throw new Exception("Réservation introuvable.");
    }

    // Sécurité : Pas d'annulation si le trajet a commencé ou est terminé
    if ($reservation['statut'] === 'en_cours' || $reservation['statut'] === 'termine') {
        throw new Exception("Impossible d'annuler : le trajet a déjà commencé ou est terminé.");
    }

    // OPTIMISATION : Remplacer intval par floatval pour correspondre au script de réservation
    $montant_remboursement = floatval($reservation['prix']) + 2.0; 
    $trajet_id_reel = $reservation['trajet_id'];
    $res_id_reel = $reservation['res_id'];

    // 4. Supprimer la réservation de manière ultra-ciblée via son ID unique
    $stmtDelete = $pdo->prepare("DELETE FROM reservations WHERE id = ?");
    $stmtDelete->execute([$res_id_reel]);

    // 5. Rembourser les crédits (Prix + Commission)
    $stmtRemboursement = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
    $stmtRemboursement->execute([$montant_remboursement, $user_id]);

    // 6. Libérer une place sur le trajet
    $stmtPlaces = $pdo->prepare("UPDATE trajets SET places_disponibles = places_disponibles + 1 WHERE id = ?");
    $stmtPlaces->execute([$trajet_id_reel]);

    // 7. Validation définitive de toutes les opérations
    $pdo->commit();

    // Redirection
    header('Location: ../mon_espace.php?success=Réservation+annulée.+Vous+avez+été+remboursé+de+' . $montant_remboursement . '+crédits.');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../mon_espace.php?error=' . urlencode($e->getMessage()));
    exit;
}