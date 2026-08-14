<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['id'])) {
    header('Location: ../mon_espace.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$id_trajet = (int)$_GET['id'];

try {
    $pdo->beginTransaction();

    // 1. On vérifie que le trajet appartient au chauffeur et qu'il est bien 'en cours'
    // (Ajusté sans l'underscore pour correspondre à ton demarrer_trajet.php)
    $stmt = $pdo->prepare("SELECT id FROM trajets WHERE id = ? AND id_chauffeur = ? AND statut = 'en cours'");
    $stmt->execute([$id_trajet, $user_id]);
    $trajet = $stmt->fetch();

    if (!$trajet) {
        throw new Exception("Impossible de clôturer ce trajet (Statut incorrect ou vous n'êtes pas le chauffeur).");
    }

    // 2. Mise à jour du statut du trajet à 'termine'
    $updateStatus = $pdo->prepare("UPDATE trajets SET statut = 'termine' WHERE id = ?");
    $updateStatus->execute([$id_trajet]);

    // 3. Récupération des emails des passagers pour ce trajet afin de leur envoyer le mail de l'US 11
    $stmtPassagers = $pdo->prepare("
        SELECT u.email 
        FROM reservations r 
        JOIN utilisateur u ON r.id_utilisateur = u.id 
        WHERE r.id_trajet = ? AND r.statut = 'valide'
    ");
    $stmtPassagers->execute([$id_trajet]);
    $passagers = $stmtPassagers->fetchAll();

    // 4. Envoi du mail de notification aux passagers
    foreach ($passagers as $passager) {
        $to = $passager['email'];
        $subject = "Votre covoiturage EcoRide est arrivé !";
        $message = "Bonjour,\n\nVotre chauffeur a indiqué que vous êtes arrivés à destination. Veuillez vous connecter sur votre espace EcoRide pour valider que le trajet s'est bien passé.";
        $headers = "From: no-reply@ecoride.com";
        
        // On utilise l'arobase @ pour éviter de bloquer le script en local si le serveur SMTP n'est pas configuré
        @mail($to, $subject, $message, $headers);
    }

    $pdo->commit();

    $messageSuccess = "Trajet marqué comme terminé ! Les passagers ont reçu un mail pour valider le trajet et débloquer vos crédits.";
    header('Location: ../mon_espace.php?success=' . urlencode($messageSuccess));
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../mon_espace.php?error=' . urlencode($e->getMessage()));
    exit;
}