<?php
session_start();
require_once '../config/db.php';

// Sécurité : l'utilisateur doit être connecté et les données indispensables présentes
if (!isset($_SESSION['user_id']) || !isset($_POST['id_reservation']) || !isset($_POST['trajet_conforme'])) {
    header('Location: ../mon_espace.php');
    exit;
}

$id_passager = $_SESSION['user_id'];
$id_reservation = (int)$_POST['id_reservation'];
$trajet_conforme = $_POST['trajet_conforme']; // Attendu : 'oui' ou 'non'

try {
    $pdo->beginTransaction();

    // 1. On récupère la réservation pour s'assurer qu'elle appartient au passager et que le trajet est bien 'termine'
    $stmt = $pdo->prepare("
        SELECT r.id, r.id_trajet, t.id_chauffeur, t.prix 
        FROM reservations r
        JOIN trajets t ON r.id_trajet = t.id
        WHERE r.id = ? AND r.id_passager = ? AND t.statut = 'termine' AND r.statut = 'valide'
    ");
    $stmt->execute([$id_reservation, $id_passager]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        throw new Exception("Validation impossible : Réservation introuvable ou déjà validée.");
    }

    $id_chauffeur = $reservation['id_chauffeur'];
    $prix_place = (int)$reservation['prix'];
    $id_trajet = $reservation['id_trajet'];

    if ($trajet_conforme === 'oui') {
        // --- SCÉNARIO A : TOUT S'EST BIEN PASSÉ ---
        
        // Mise à jour de la réservation
        $updateRes = $pdo->prepare("UPDATE reservations SET statut = 'cloture_succes' WHERE id = ?");
        $updateRes->execute([$id_reservation]);

        // CRITIQUE US 11 : On crédite le compte du chauffeur puisque tout est OK
        $updateCredits = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
        $updateCredits->execute([$prix_place, $id_chauffeur]);

        // Enregistrement de l'avis (soumis à validation employé)
        if (!empty($_POST['note'])) {
            $note = (int)$_POST['note'];
            $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';
            
            $insertAvis = $pdo->prepare("
                INSERT INTO avis (id_trajet, id_conducteur, id_passager, note, commentaire, statut) 
                VALUES (?, ?, ?, ?, ?, 'en attente')
            ");
            $insertAvis->execute([$id_trajet, $id_chauffeur, $id_passager, $note, $commentaire]);
        }

        $message = "Merci pour votre retour ! Le chauffeur a été crédité de ses points.";

    } else {
        // --- SCÉNARIO B : ÇA S'EST MAL PASSÉ ---
        $commentaire_litige = isset($_POST['commentaire_litige']) ? trim($_POST['commentaire_litige']) : '';
        
        if (empty($commentaire_litige)) {
            throw new Exception("Un commentaire est obligatoire si le trajet s'est mal passé.");
        }

        // On marque la réservation en litige
        $updateRes = $pdo->prepare("UPDATE reservations SET statut = 'litige' WHERE id = ?");
        $updateRes->execute([$id_reservation]);

        // On crée un incident pour qu'un employé contacte le chauffeur (Table 'incidents_trajets' ou similaire)
        $insertIncident = $pdo->prepare("
            INSERT INTO incidents_trajets (id_trajet, id_reservation, id_passager, id_chauffeur, description, statut) 
            VALUES (?, ?, ?, ?, ?, 'en_attente')
        ");
        $insertIncident->execute([$id_trajet, $id_reservation, $id_passager, $id_chauffeur, $commentaire_litige]);

        // CRITIQUE US 11 : Les crédits du chauffeur NE SONT PAS mis à jour ici.

        $message = "Votre signalement a été enregistré. Un employé va étudier la situation avant le versement des crédits.";
    }

    $pdo->commit();
    header('Location: ../mon_espace.php?success=' . urlencode($message));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../mon_espace.php?error=' . urlencode($e->getMessage()));
    exit;
}