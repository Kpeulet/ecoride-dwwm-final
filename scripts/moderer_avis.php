<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once '../config/db.php';

// Verification silencieuse de Composer
if (file_exists('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}

// 1. SÉCURITÉ
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'employe' && $_SESSION['role'] !== 'admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
    exit;
}

// 2. MODÉRATION
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id_avis = (int)$_GET['id'];

    try {
        $pdo->beginTransaction();

        if ($action === 'valider') {
            
            // Logique de crédits / litiges MySQL
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

            // Récupération des données avant validation
            $stmtAvis = $pdo->prepare("
                SELECT a.*, u.pseudo AS auteur_nom 
                FROM avis a 
                LEFT JOIN utilisateur u ON a.id_expediteur = u.id 
                WHERE a.id = ?
            ");
            $stmtAvis->execute([$id_avis]);
            $avisData = $stmtAvis->fetch(PDO::FETCH_ASSOC);

            // Validation dans MySQL
            $stmt = $pdo->prepare("UPDATE avis SET statut = 'valide' WHERE id = :id");
            $stmt->execute(['id' => $id_avis]);

            // Tentative MongoDB UNIQUEMENT si la classe existe (évite toute erreur si l'extension C manque)
            if ($avisData && class_exists('MongoDB\Client')) {
                try {
                    $mongoClient = new MongoDB\Client("mongodb://localhost:27017");
                    $collection = $mongoClient->selectCollection('ecoride', 'avis');

                    $collection->insertOne([
                        'id_avis_mysql' => (int)$avisData['id'],
                        'id_trajet'     => (int)($avisData['id_trajet'] ?? 0),
                        'auteur'        => $avisData['auteur_nom'] ?? 'Inconnu',
                        'note'          => (int)($avisData['note'] ?? 5),
                        'commentaire'   => $avisData['commentaire'] ?? '',
                        'statut'        => 'valide',
                        'date_valide'   => new MongoDB\BSON\UTCDateTime()
                    ]);
                } catch (\Throwable $mongoErr) {
                    error_log("MongoDB non joignable : " . $mongoErr->getMessage());
                }
            }

        } elseif ($action === 'refuser') {
            $stmt = $pdo->prepare("DELETE FROM avis WHERE id = :id");
            $stmt->execute(['id' => $id_avis]);
        }
        
        $pdo->commit();

        echo json_encode([
            'success' => true, 
            'message' => 'L\'avis a bien été modéré dans MySQL.'
        ]);
        exit;

    } catch (\Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}