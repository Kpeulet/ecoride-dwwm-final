<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 1. SÉCURITÉ : Vérification des droits d'accès
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit;
}

require_once '../config/db.php';

$response = [
    'mongo_disponible' => false,
    'avis' => [],
    'litiges' => []
];

// 2. RÉCUPÉRATION DES AVIS (MySQL)
try {
    $stmtAvis = $pdo->prepare("
        SELECT a.*, u.pseudo AS expediteur, t.ville_depart, t.ville_arrivee 
        FROM avis a
        JOIN utilisateur u ON a.id_expediteur = u.id
        JOIN trajets t ON a.id_trajet = t.id
        WHERE a.statut = 'en_attente' OR a.statut IS NULL
        ORDER BY t.date_depart DESC
    ");
    $stmtAvis->execute();
    $avis_en_attente = $stmtAvis->fetchAll(PDO::FETCH_ASSOC);

    foreach ($avis_en_attente as $key => $avis) {
        $avis_en_attente[$key]['commentaire'] = html_entity_decode(htmlspecialchars_decode($avis['commentaire'], ENT_QUOTES), ENT_QUOTES, 'UTF-8');
    }
    $response['avis'] = $avis_en_attente;
} catch (\Throwable $e) {
    // Si la table avis/trajets MySQL échoue, on continue sans bloquer
}

// 3. RÉCUPÉRATION DES LITIGES (MongoDB)
try {
    if (file_exists('../config/mongodb.php')) {
        include_once '../config/mongodb.php';
        
        if (isset($litigesCollection) && $litigesCollection !== null) {
            $cursor = $litigesCollection->find([], ['sort' => ['date_incident' => -1]]);
            $litigesMongo = iterator_to_array($cursor);

            if (!empty($litigesMongo)) {
                $response['mongo_disponible'] = true;

                foreach ($litigesMongo as $doc) {
                    // Extraction conforme au document qu'on a inséré dans Compass
                    $response['litiges'][] = [
                        'id_trajet'       => $doc['numero_covoiturage'] ?? 'N/A',
                        'statut'          => $doc['statut'] ?? 'En attente',
                        'depart'          => $doc['descriptif_trajet']['depart'] ?? 'N/A',
                        'arrivee'         => $doc['descriptif_trajet']['arrivee'] ?? 'N/A',
                        'date_depart'     => $doc['descriptif_trajet']['date_depart'] ?? null,
                        'date_arrivee'    => $doc['descriptif_trajet']['date_arrivee'] ?? null,
                        'chauffeur'       => $doc['chauffeur']['pseudo'] ?? 'Chauffeur',
                        'email_chauffeur' => $doc['chauffeur']['email'] ?? 'Non renseigné',
                        'passager'        => $doc['passager']['pseudo'] ?? 'Passager',
                        'email_passager'  => $doc['passager']['email'] ?? 'Non renseigné',
                        'commentaire'     => html_entity_decode(htmlspecialchars_decode($doc['commentaire'] ?? '', ENT_QUOTES), ENT_QUOTES, 'UTF-8')
                    ];
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log("MongoDB erreur : " . $e->getMessage());
    $response['mongo_disponible'] = false;
}

// 4. SECONDE OPTION : SECOURS MYSQL (Si Mongo est vide ou KO)
if (!$response['mongo_disponible'] || empty($response['litiges'])) {
    try {
        $stmtLitiges = $pdo->prepare("
            SELECT t.id AS id_trajet, 
                   t.ville_depart AS depart, 
                   t.ville_arrivee AS arrivee,
                   t.date_depart,
                   c.pseudo AS chauffeur, 
                   c.email AS email_chauffeur,
                   p.pseudo AS passager, 
                   p.email AS email_passager,
                   a.commentaire,
                   'En litige' AS statut
            FROM avis a
            JOIN trajets t ON a.id_trajet = t.id
            JOIN utilisateur c ON t.id_chauffeur = c.id
            JOIN utilisateur p ON a.id_expediteur = p.id
            WHERE a.note <= 2 
        ");
        $stmtLitiges->execute();
        $response['litiges'] = $stmtLitiges->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {
        // En cas d'erreur de la requête de secours SQL
    }
}

echo json_encode($response);