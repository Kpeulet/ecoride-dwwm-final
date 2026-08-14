<?php
session_start();
require_once '../config/db.php';

if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

// Sécurité
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'employe' && $_SESSION['role'] !== 'admin')) {
    header('Location: ../index.php');
    exit;
}

$id_avis = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action  = $_GET['action'] ?? null;

if ($id_avis && $action) {
    try {
        $pdo->beginTransaction();

        if ($action === 'valider' || $action === 'accepter') {
            // 1. Déblocage crédits / litiges MySQL
            $stmtInfos = $pdo->prepare("
                SELECT t.id_chauffeur, t.prix, r.statut AS reservation_statut, a.id_trajet, a.id_expediteur
                FROM avis a
                JOIN trajets t ON a.id_trajet = t.id
                JOIN reservations r ON r.id_trajet = t.id AND r.id_utilisateur = a.id_expediteur
                WHERE a.id = ?
            ");
            $stmtInfos->execute([$id_avis]);
            $infos = $stmtInfos->fetch();

            if ($infos && $infos['reservation_statut'] === 'litige') {
                $stmtCredit = $pdo->prepare("UPDATE utilisateur SET credits = credits + ? WHERE id = ?");
                $stmtCredit->execute([intval($infos['prix']), $infos['id_chauffeur']]);

                $stmtUpdateRes = $pdo->prepare("UPDATE reservations SET statut = 'valide' WHERE id_trajet = ? AND id_utilisateur = ?");
                $stmtUpdateRes->execute([$infos['id_trajet'], $infos['id_expediteur']]);
            }

            // 2. Statut MySQL
            $stmt = $pdo->prepare("UPDATE avis SET statut = 'valide' WHERE id = ?");
            $stmt->execute([$id_avis]);

            // 3. Archivage MongoDB
            try {
                if (class_exists('MongoDB\Client')) {
                    $stmtAvis = $pdo->prepare("
                        SELECT a.*, u.pseudo AS auteur_nom 
                        FROM avis a 
                        LEFT JOIN utilisateur u ON a.id_expediteur = u.id 
                        WHERE a.id = ?
                    ");
                    $stmtAvis->execute([$id_avis]);
                    $avisData = $stmtAvis->fetch(PDO::FETCH_ASSOC);

                    if ($avisData) {
                        $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
                        $collection = $mongoClient->selectCollection('ecoride', 'avis');

                        $collection->insertOne([
                            'id_avis_mysql' => (int)$avisData['id'],
                            'id_trajet'     => (int)$avisData['id_trajet'],
                            'auteur'        => $avisData['auteur_nom'] ?? 'Inconnu',
                            'note'          => (int)$avisData['note'],
                            'commentaire'   => $avisData['commentaire'],
                            'statut'        => 'valide',
                            'date_valide'   => new MongoDB\BSON\UTCDateTime()
                        ]);
                    }
                }
            } catch (\Throwable $mongoErr) {
                error_log("Avertissement MongoDB dans valider_avis.php : " . $mongoErr->getMessage());
            }

        } elseif ($action === 'refuser') {
            $stmt = $pdo->prepare("DELETE FROM avis WHERE id = ?");
            $stmt->execute([$id_avis]);
        }

        $pdo->commit();
    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Erreur valider_avis.php : " . $e->getMessage());
    }
}

// Redirection propre vers le tableau de bord
header('Location: ../espace_employe.php');
exit;