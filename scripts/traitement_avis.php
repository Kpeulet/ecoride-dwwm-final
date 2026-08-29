<?php
session_start();
require_once '../config/db.php';

// Chargement de la configuration et de la connexion MongoDB
if (file_exists('../config/mongodb.php')) {
    include_once '../config/mongodb.php';
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
        // Début de la transaction SQL
        $pdo->beginTransaction();

        // 1. Sauvegarde principale dans MySQL (Table avis)
        $stmt = $pdo->prepare("INSERT INTO avis (id_trajet, id_expediteur, note, commentaire, statut) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$id_trajet, $id_expediteur, $note, $commentaire, $statut_avis]);

        // 2. Logique des crédits & infos du trajet
        $stmtChauffeur = $pdo->prepare("SELECT id_chauffeur, prix, date_depart, date_arrivee, ville_depart, ville_arrivee FROM trajets WHERE id = ?");
        $stmtChauffeur->execute([$id_trajet]);
        $trajetInfos = $stmtChauffeur->fetch();

        if ($trajetInfos) {
            $id_chauffeur = $trajetInfos['id_chauffeur'];
            $prix_trajet = intval($trajetInfos['prix']);

            if ($voyage_statut === 'bien_passe') {
                $stmtCredit = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
                $stmtCredit->execute([$prix_trajet, $id_chauffeur]);
                $messageSuccess = "avis_envoye";
            } else {
                $updateRes = $pdo->prepare("UPDATE reservations SET statut = 'litige' WHERE id_trajet = ? AND id_utilisateur = ?");
                $updateRes->execute([$id_trajet, $id_expediteur]);
                $messageSuccess = "signalement_enregistre";
            }
        }

        // 3. ENREGISTREMENT NOSQL MONGODB (Avis & Incidents)
        
        // A. Sauvegarde dans la collection 'avis' MongoDB si la connexion est active
        if (isset($avisCollection) && $avisCollection !== null) {
            try {
                $avisCollection->insertOne([
                    "id_trajet" => $id_trajet,
                    "id_expediteur" => $id_expediteur,
                    "note" => $note,
                    "commentaire" => $commentaire,
                    "statut" => $statut_avis,
                    "voyage_statut" => $voyage_statut,
                    "created_at" => new MongoDB\BSON\UTCDateTime()
                ]);
            } catch (\Exception $eMongo) {
                error_log("Erreur insertion avis MongoDB : " . $eMongo->getMessage());
            }
        }

        // B. Sauvegarde dans la collection 'incidents' MongoDB si "mal passé"
        if ($voyage_statut === 'mal_passe' && isset($litigesCollection) && $litigesCollection !== null && $trajetInfos) {
            
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
                try {
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
                } catch (\Exception $eMongoLitige) {
                    error_log("Erreur insertion incident MongoDB : " . $eMongoLitige->getMessage());
                }
            }
        }

        // Validation finale de la transaction PDO
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