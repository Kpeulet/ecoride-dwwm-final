<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$id_utilisateur = $_SESSION['user_id'] ?? null;

if (!$id_utilisateur) {
    header('Location: ../login.php?error=Veuillez vous connecter pour réserver');
    exit;
}

$id_trajet = $_POST['id_trajet'] ?? null;
if (!$id_trajet) { die("Erreur : Trajet invalide."); }

try {
    $pdo->beginTransaction();

    // 1. Récupération des infos du trajet (avec verrouillage d'écriture FOR UPDATE)
    $stmt = $pdo->prepare("SELECT places_disponibles, id_chauffeur, prix, statut FROM trajets WHERE id = ? FOR UPDATE");
    $stmt->execute([$id_trajet]);
    $trajet = $stmt->fetch();

    if (!$trajet) { throw new Exception("Le trajet n'existe pas."); }

    // Sécurité : On vérifie si le trajet est bien ouvert
    if ($trajet['statut'] !== 'ouvert') {
        throw new Exception("Ce trajet n'est plus disponible (déjà terminé ou annulé).");
    }

    // 2. Récupération du solde du passager
    $stmtUser = $pdo->prepare("SELECT credits FROM utilisateur WHERE id = ? FOR UPDATE");
    $stmtUser->execute([$id_utilisateur]);
    $userCredits = $stmtUser->fetchColumn();

    // 3. Calcul du prix total (Remplacement de intval par floatval pour gérer les centimes de la BDD)
    $prix_base = floatval($trajet['prix']);
    $prix_total = $prix_base + 2.0; // Prix de base + 2 crédits fixes de commission éco-participation (Ex: 15 + 2 = 17)

    // 4. Vérifications de sécurité applicatives
    if ($trajet['id_chauffeur'] == $id_utilisateur) {
        throw new Exception("Vous ne pouvez pas réserver votre propre trajet.");
    }

    if ($userCredits < $prix_total) {
        throw new Exception("Solde insuffisant (Il vous faut $prix_total crédits).");
    }

    if ($trajet['places_disponibles'] <= 0) {
        throw new Exception("Désolé, il n'y a plus de places disponibles.");
    }

    // 5. Exécution des requêtes SQL de mise à jour
    
    // A. Enregistrement de la réservation
    $insert = $pdo->prepare("INSERT INTO reservations (id_trajet, id_utilisateur, date_reservation, statut) VALUES (?, ?, NOW(), 'validé')");
    $insert->execute([$id_trajet, $id_utilisateur]);

    // B. Décrémentation d'une place sur le trajet
    $updatePlaces = $pdo->prepare("UPDATE trajets SET places_disponibles = places_disponibles - 1 WHERE id = ?");
    $updatePlaces->execute([$id_trajet]);
    
    // C. Débit global du passager (On retire bien les 17 crédits ici !)
    $updateCreditsPassager = $pdo->prepare("UPDATE utilisateur SET credits = credits - ? WHERE id = ?");
    $updateCreditsPassager->execute([$prix_total, $id_utilisateur]);

    // D. Crédit du chauffeur (Le chauffeur ne reçoit QUE le prix de base, soit 15 crédits. Les 2 crédits restent à la plateforme)
    $updateCreditsChauffeur = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
    $updateCreditsChauffeur->execute([$prix_base, $trajet['id_chauffeur']]);
    
    $pdo->commit();
    header('Location: ../mon_espace.php?success=reservation_confirmee');
    exit;

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Redirection avec le message d'erreur d'origine
    header('Location: ../detail_trajet.php?id='.$id_trajet.'&error=' . urlencode($e->getMessage()));
    exit;
}