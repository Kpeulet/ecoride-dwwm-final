<?php
/**
 * Script de relance automatique pour les avis d'EcoRide
 * Ce script est destiné à être exécuté par une tâche Cron toutes les 24h.
 */

require_once __DIR__ . '/../config/db.php';

try {
    echo "--- Début du script de relance des avis ---\n";

    // 1. Requête SQL de sélection des passagers éligibles à la relance
    $sql = "
        SELECT 
            r.id_utilisateur AS id_passager,
            u.pseudo AS nom_passager,
            u.email AS email_passager,
            t.id AS id_trajet,
            t.ville_depart,
            t.ville_arrivee,
            t.date_arrivee
        FROM reservations r
        JOIN trajets t ON r.id_trajet = t.id
        JOIN utilisateur u ON r.id_utilisateur = u.id
        LEFT JOIN avis a ON a.id_trajet = t.id AND a.id_expediteur = r.id_utilisateur
        WHERE t.date_arrivee <= NOW() - INTERVAL 1 DAY
          AND r.statut = 'valide'
          AND a.id IS NULL
          AND r.rappel_envoye = 0
    ";

    $stmt = $pdo->query($sql);
    $relances = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Traitement des relances ou affichage du message d'absence de données
    if (empty($relances)) {
        echo "Aucun passager à relancer aujourd'hui.\n";
    } else {
        echo count($relances) . " relance(s) à effectuer.\n";

        // 3. Boucle d'envoi des e-mails et mise à jour de la sécurité anti-spam
        foreach ($relances as $relance) {
            $to = $relance['email_passager'];
            $subject = "Votre voyage EcoRide : Donnez votre avis !";
            
            // Construction du message de manière explicite
            $message = "Bonjour " . $relance['nom_passager'] . ",\n\n";
            $message .= "Il y a plus de 24 heures, vous avez effectué le trajet " . $relance['ville_depart'] . " -> " . $relance['ville_arrivee'] . ".\n";
            $message .= "Le covoiturage responsable repose sur la confiance. Prenez 1 minute pour partager votre expérience et laisser une note à votre chauffeur !\n\n";
            $message .= "Cliquez ici pour déposer votre avis : http://localhost:8888/projet_ecoride/laisser_avis.php?id_trajet=" . $relance['id_trajet'] . "\n\n";
            $message .= "À bientôt sur EcoRide,\nL'équipe de modération.";

            // En-têtes de l'e-mail
            $headers = "From: ne-pas-repondre@ecoride.com\r\n";
            $headers .= "Reply-To: contact@ecoride.com\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();

            // Envoi effectif de l'e-mail et mise à jour de la base de données
            if (mail($to, $subject, $message, $headers)) {
                $stmtUpdate = $pdo->prepare("
                    UPDATE reservations 
                    SET rappel_envoye = 1 
                    WHERE id_trajet = ? AND id_utilisateur = ?
                ");
                $stmtUpdate->execute([$relance['id_trajet'], $relance['id_passager']]);

                echo "E-mail de relance envoyé avec succès à : " . $to . " pour le trajet n°" . $relance['id_trajet'] . "\n";
            } else {
                echo "Échec de l'envoi de l'e-mail pour : " . $to . "\n";
            }
        }
    }

    echo "--- Fin du script de relance ---\n";

} catch (PDOException $e) {
    echo "Erreur SQL lors de l'exécution du script : " . $e->getMessage() . "\n";
    exit;
}