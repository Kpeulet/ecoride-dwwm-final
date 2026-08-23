<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Mise à jour Profil & Préférences (US 8)
    if (isset($_POST['update_profil'])) {
        $est_chauffeur = isset($_POST['est_chauffeur']) ? (int)$_POST['est_chauffeur'] : 0;
        
        $fumeur_txt = (isset($_POST['fumeur']) && $_POST['fumeur'] == '1') ? "Fumeur accepté" : "Non-fumeur";
        $animal_txt = (isset($_POST['animal']) && $_POST['animal'] == '1') ? "Animaux acceptés" : "Pas d'animaux";
        
        // On récupère uniquement la remarque saisie par l'utilisateur
        $remarques = isset($_POST['preferences_libres']) ? trim(htmlspecialchars_decode($_POST['preferences_libres'], ENT_QUOTES)) : '';

        // Si la chaîne contenait déjà des puces issues d'un ancien enregistrement, on nettoie pour ne garder que le texte libre
        if (preg_match('/• Autres\s*:\s*(.*)/s', $remarques, $matches)) {
            $remarques = trim($matches[1]);
        }

        // Reconstruction propre
        $preferences_completes = "• Cigarette : " . $fumeur_txt . "\n• Animaux : " . $animal_txt;
        if (!empty($remarques)) {
            $preferences_completes .= "\n• Autres : " . $remarques;
        }

        try {
            $stmt = $pdo->prepare("UPDATE utilisateur SET est_chauffeur = ?, preferences_libres = ? WHERE id = ?");
            $stmt->execute([$est_chauffeur, $preferences_completes, $user_id]);

            header('Location: ../profil.php?success=1');
            exit();
        } catch (PDOException $e) {
            die("Erreur de mise à jour du profil : " . $e->getMessage());
        }
    }

    // 2. Ajout Véhicule avec nb_places (US 8)
    if (isset($_POST['add_vehicle'])) {
        $marque          = trim($_POST['marque']);
        $modele          = trim($_POST['modele']);
        $nb_places       = isset($_POST['nb_places']) ? (int)$_POST['nb_places'] : 4;
        $couleur         = trim($_POST['couleur']);
        $immatriculation = trim($_POST['immatriculation']);
        $energie         = trim($_POST['energie']);
        $date_immat      = !empty($_POST['date_premiere_immat']) ? $_POST['date_premiere_immat'] : null;

        if (!empty($marque) && !empty($modele) && !empty($immatriculation)) {
            try {
                // Ingestion de nb_places pour éviter l'erreur SQL 1364
                $stmt = $pdo->prepare("INSERT INTO vehicule (id_utilisateur, marque, modele, nb_places, couleur, energie, immatriculation, date_premiere_immat) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $marque, $modele, $nb_places, $couleur, $energie, $immatriculation, $date_immat]);

                // Passage automatique en chauffeur si un véhicule est ajouté (US 8)
                $stmtUser = $pdo->prepare("UPDATE utilisateur SET est_chauffeur = 1 WHERE id = ?");
                $stmtUser->execute([$user_id]);

                header('Location: ../profil.php?success=1');
                exit();
            } catch (PDOException $e) {
                die("Erreur lors de l'ajout du véhicule : " . $e->getMessage());
            }
        }
    }
}

header('Location: ../profil.php');
exit();