<?php
session_start();
require_once '../config/db.php';

// Sécurité : On vérifie si l'extension MongoDB est active sur MAMP
$mongoDisponible = false;
try {
    if (file_exists('../config/mongodb.php')) {
        include_once '../config/mongodb.php';
        if (class_exists('MongoDB\Client')) {
            $mongoDisponible = true;
        }
    }
} catch (\Throwable $e) {
    error_log("Extension MongoDB absente en local : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $id_trajet = intval($_POST['id_trajet']);
    $id_expediteur = $_SESSION['user_id'];
    $note = intval($_POST['note']);
    $commentaire = htmlspecialchars($_POST['commentaire']);
    
    // Récupération du statut (bien_passe ou mal_passe)
    $voyage_statut = $_POST['voyage_statut'] ?? 'bien_passe';
    
    // Tous les avis doivent être validés par un employé avant d'être publiés
    $statut_avis = 'en_attente';

    try {
        // Début de la transaction
        $pdo->beginTransaction();

        // 1. Sauvegarde dans MySQL (Table avis)
        $stmt = $pdo->prepare("INSERT INTO avis (id_trajet, id_expediteur, note, commentaire, statut) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_trajet, $id_expediteur, $note, $commentaire, $statut_avis]);

        // 2. LOGIQUE DES CRÉDITS (US 11) : Récupération du prix réel et de l'ID du chauffeur
        $stmtChauffeur = $pdo->prepare("SELECT id_chauffeur, prix, date_depart, date_arrivee, ville_depart, ville_arrivee FROM trajets WHERE id = ?");
        $stmtChauffeur->execute([$id_trajet]);
        $trajetInfos = $stmtChauffeur->fetch();

        if ($trajetInfos) {
            $id_chauffeur = $trajetInfos['id_chauffeur'];
            $prix_trajet = intval($trajetInfos['prix']); // Prix dynamique de la place du trajet

            // Si tout s'est bien passé, on crédite la valeur réelle de la place réservée
            if ($voyage_statut === 'bien_passe') {
                $stmtCredit = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
                $stmtCredit->execute([$prix_trajet, $id_chauffeur]);
                $messageSuccess = "avis_envoye";
            } else {
                // Règle US 11 : En cas de problème, pas de crédit automatique. 
                // Correction ici : id_utilisateur au lieu de id_passager
                $updateRes = $pdo->prepare("UPDATE reservations SET statut = 'litige' WHERE id_trajet = ? AND id_utilisateur = ?");
                $updateRes->execute([$id_trajet, $id_expediteur]);
                $messageSuccess = "signalement_enregistre";
            }
        }

        // 3. EXIGENCE JURY : Sauvegarde du document NoSQL si "mal passé" et MongoDB disponible
        if ($voyage_statut === 'mal_passe' && $mongoDisponible && isset($litigesCollection) && $trajetInfos) {
            
            $stmtInfos = $pdo->prepare("
                SELECT p.pseudo AS passager_pseudo, p.email AS passager_email,
                       c.pseudo AS chauffeur_pseudo, c.email AS chauffeur_email
                FROM utilisateur p
                JOIN utilisateur c ON c.id = ?
                WHERE p.id = ?
            ");
            $stmtInfos->execute([$id_chauffeur, $id_expediteur]);
            $infos = $stmtInfos->fetch();

            if ($infos) {
                $documentLitige = [
                    "numero_covoiturage" => $id_trajet,
                    "date_incident" => new MongoDB\BSON\UTCDateTime(),
                    "passager" => [
                        "pseudo" => $infos['passager_pseudo'],
                        "email" => $infos['passager_email']
                    ],
                    "chauffeur" => [
                        "pseudo" => $infos['chauffeur_pseudo'],
                        "email" => $infos['chauffeur_email']
                    ],
                    "descriptif_trajet" => [
                        "date_depart" => $trajetInfos['date_depart'],
                        "date_arrivee" => $trajetInfos['date_arrivee'],
                        "lieu_depart" => $trajetInfos['ville_depart'],
                        "lieu_arrivee" => $trajetInfos['ville_arrivee']
                    ],
                    "commentaire_plainte" => $commentaire,
                    "statut_resolution" => "En attente (À contacter par un employé)"
                ];

                $litigesCollection->insertOne($documentLitige);
            }
        }

        $pdo->commit();

        header('Location: ../mon_espace.php?success=' . $messageSuccess);
        exit;
        
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        die("Erreur lors de l'envoi de l'avis : " . $e->getMessage());
    }
}