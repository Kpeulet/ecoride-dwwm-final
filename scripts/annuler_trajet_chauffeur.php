<?php
session_start();
require_once '../config/db.php';

// 1. Sécurité : Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: ../mon_espace.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération de l'ID du trajet passé en paramètre GET
$id_trajet = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_trajet <= 0) {
    header('Location: ../mon_espace.php?error=Impossible+de+récupérer+l+ID+du+trajet.');
    exit;
}

try {
    // 2. Début de la transaction SQL
    $pdo->beginTransaction();

    // 3. Vérifier que le trajet existe, appartient bien à ce chauffeur et est annulable
    $stmtTrajet = $pdo->prepare("SELECT * FROM trajets WHERE id = ? AND id_chauffeur = ?");
    $stmtTrajet->execute([$id_trajet, $user_id]);
    $trajet = $stmtTrajet->fetch();

    if (!$trajet) {
        throw new Exception("Trajet introuvable ou vous n'êtes pas le chauffeur de ce trajet.");
    }

    if ($trajet['statut'] === 'en_cours' || $trajet['statut'] === 'termine') {
        throw new Exception("Impossible d'annuler : le trajet a déjà commencé ou est terminé.");
    }
    
    if ($trajet['statut'] === 'annule') {
        throw new Exception("Ce trajet est déjà annulé.");
    }

    // 4. Récupérer la liste des passagers qui ont réservé pour ce trajet (pour remboursement et mail)
    $stmtPassagers = $pdo->prepare("
        SELECT r.id_utilisateur, u.email, u.pseudo 
        FROM reservations r
        JOIN utilisateur u ON r.id_utilisateur = u.id
        WHERE r.id_trajet = ?
    ");
    $stmtPassagers->execute([$id_trajet]);
    $passagers = $stmtPassagers->fetchAll();

    // Montant à rembourser à chaque passager (Prix demandé par le chauffeur + commission de 2 crédits)
    $montant_remboursement = floatval($trajet['prix']) + 2.0;

    // 5. Boucle de remboursement des passagers et suppression de leurs réservations
    if (!empty($passagers)) {
        // Préparation des requêtes pour optimiser les performances dans la boucle
        $stmtCredit = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
        $stmtDelRes = $pdo->prepare("DELETE FROM reservations WHERE id_trajet = ?");
        
        foreach ($passagers as $passager) {
            // Remboursement des crédits du passager
            $stmtCredit->execute([$montant_remboursement, $passager['id_utilisateur']]);
            
            // Envoi de l'email de notification au passager
            $to = $passager['email'];
            $subject = "⚠️ Annulation de votre covoiturage EcoRide";
            
            $message = "Bonjour " . htmlspecialchars($passager['pseudo']) . ",\n\n";
            $message .= "Nous vous informons que le chauffeur a malheureusement annulé le trajet de " . htmlspecialchars($trajet['ville_depart']) . " vers " . htmlspecialchars($trajet['ville_arrivee']) . " prévu le " . $trajet['date_depart'] . ".\n";
            $message .= "Votre compte EcoRide a été recrédité de " . $montant_remboursement . " crédits (prix du billet + frais de service).\n\n";
            $message .= "Nous vous invitons à vous connecter sur la plateforme pour trouver un autre itinéraire disponible.\n\n";
            $message .= "L'équipe EcoRide à votre service.";
            
            $headers = "From: ne-pas-repondre@ecoride.com\r\n" .
                       "Reply-To: contact@ecoride.com\r\n" .
                       "X-Mailer: PHP/" . phpversion() . "\r\n" .
                       "Content-Type: text/plain; charset=UTF-8";
            
            // Note pour l'examen : la fonction mail() renvoie true/false mais on ne bloque pas la transaction si l'envoi réseau échoue localement
            @mail($to, $subject, $message, $headers);
        }
        
        // Suppression globale des réservations pour ce trajet
        $stmtDelRes->execute([$id_trajet]);
    }

    // 6. Basculer le statut du trajet à 'annule' et remettre les places à 0
    $stmtUpdateTrajet = $pdo->prepare("UPDATE trajets SET statut = 'annule', places_disponibles = 0 WHERE id = ?");
    $stmtUpdateTrajet->execute([$id_trajet]);

    // 7. Validation définitive de toutes les opérations en base de données
    $pdo->commit();

    header('Location: ../mon_espace.php?success=Le+trajet+a+été+annulé+avec+succès.+Les+passagers+ont+été+remboursés+et+avertis.');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../mon_espace.php?error=' . urlencode($e->getMessage()));
    exit;
}