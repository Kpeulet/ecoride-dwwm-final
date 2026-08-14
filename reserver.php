<?php
// 1. Démarrage de la session
session_start();

// Sécurité : Si l'utilisateur n'est pas connecté, retour à l'accueil
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// 2. Inclusion de ta connexion centralisée
// Grâce à cette ligne, la variable $pdo devient disponible dans ce script.
require_once 'config/db.php'; 

// Vérification que le formulaire a bien été soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider_reservation'])) {
    
    // Récupération et sécurisation des variables issues du formulaire
    $id_trajet = (int)$_POST['id_trajet'];
    $id_passager = (int)$_SESSION['user_id']; 

    // -----------------------------------------------------------------
    // ÉTAPE 1 : Récupérer et vérifier les données (Règles métiers)
    // -----------------------------------------------------------------
    
    // Récupération des infos du trajet
    $reqTrajet = $pdo->prepare('SELECT prix, places_disponibles, id_chauffeur FROM trajets WHERE id = ?');
    $reqTrajet->execute([$id_trajet]);
    $trajet = $reqTrajet->fetch();

    // Récupération des infos du passager (pour contrôler son solde)
    $reqPassager = $pdo->prepare('SELECT credits FROM utilisateur WHERE id = ?');
    $reqPassager->execute([$id_passager]);
    $passager = $reqPassager->fetch();

    // Si le trajet ou le passager n'existe pas dans la base
    if (!$trajet || !$passager) {
        header('Location: recherche.php?erreur=introuvable');
        exit();
    }

    // Sécurité : Un chauffeur ne peut pas réserver son propre trajet
    if ($trajet['id_chauffeur'] == $id_passager) {
        header('Location: detail_trajet.php?id=' . $id_trajet . '&erreur=propre_trajet');
        exit();
    }

    $cout_total = (float)$trajet['prix'] + 2; // Prix du trajet + 2 crédits de commission
    $places_restantes = (int)$trajet['places_disponibles'];
    $credits_dispo = (float)$passager['credits'];

    // Vérification des conditions imposées par l'US 6
    if ($places_restantes <= 0) {
        header('Location: detail_trajet.php?id=' . $id_trajet . '&erreur=complet');
        exit();
    }

    if ($credits_dispo < $cout_total) {
        header('Location: detail_trajet.php?id=' . $id_trajet . '&erreur=credits_insuffisants');
        exit();
    }

    // -----------------------------------------------------------------
    // ÉTAPE 2 : La Transaction SQL (Sécurisation des écritures)
    // -----------------------------------------------------------------
    try {
        // Activation du mode transactionnel sur l'objet $pdo
        $pdo->beginTransaction();

        // Action A : Débit des crédits du passager
        $majCredits = $pdo->prepare('UPDATE utilisateur SET credits = credits - ? WHERE id = ?');
        $majCredits->execute([$cout_total, $id_passager]);

        // Action B : Retrait d'une place disponible sur le trajet
        $majPlaces = $pdo->prepare('UPDATE trajets SET places_disponibles = places_disponibles - 1 WHERE id = ?');
        $majPlaces->execute([$id_trajet]);

        // Action C : Création de la ligne de réservation
        $date_reservation = date('Y-m-d H:i:s');
        $statut_reservation = 'validé';
        
        $insReservation = $pdo->prepare('INSERT INTO reservations (id_trajet, id_utilisateur, date_reservation, statut, rappel_envoye) VALUES (?, ?, ?, ?, 0)');
        $insReservation->execute([$id_trajet, $id_passager, $date_reservation, $statut_reservation]);

        // Si tout s'est exécuté sans erreur, on valide définitivement en base de données
        $pdo->commit();

        // Redirection vers l'espace utilisateur avec succès
        header('Location: espace_passager.php?succes=reserve');
        exit();

    } catch (Exception $e) {
        // En cas d'anomalie sur l'une des requêtes, on annule TOUT (Rollback)
        $pdo->rollBack();
        
        // Redirection avec un code erreur technique pour le débug
        header('Location: detail_trajet.php?id=' . $id_trajet . '&erreur=sql_echec');
        exit();
    }

} else {
    header('Location: index.php');
    exit();
}
?>