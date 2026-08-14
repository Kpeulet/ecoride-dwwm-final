<?php
// 1. Affichage des erreurs pour le débogage
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../config/db.php';

// On vérifie si on reçoit bien des données
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 2. RÉCUPÉRATION DES DONNÉES
    $id_chauffeur  = $_SESSION['user_id'] ?? null;
    $ville_depart  = htmlspecialchars(trim($_POST['ville_depart'] ?? ''));
    $ville_arrivee = htmlspecialchars(trim($_POST['ville_arrivee'] ?? ''));
    $date_depart   = $_POST['date_depart'] ?? '';
    $date_arrivee  = $_POST['date_arrivee'] ?? '';
    $prix_saisi    = intval($_POST['prix'] ?? 0);
    $places        = intval($_POST['places_disponibles'] ?? 0);
    $id_vehicule   = intval($_POST['id_vehicule'] ?? 0);

    // Vérification de sécurité de la session
    if (!$id_chauffeur) {
        die("Erreur : Vous devez être connecté pour publier un trajet.");
    }

    // Validation des données obligatoires
    if (empty($ville_depart) || empty($ville_arrivee) || $prix_saisi <= 0 || $places <= 0 || $id_vehicule === 0 || empty($date_depart) || empty($date_arrivee)) {
        header('Location: ../proposer_trajet.php?error=champs_invalides');
        exit();
    }

    // Sécurité supplémentaire : cohérence des dates
    if (strtotime($date_depart) >= strtotime($date_arrivee)) {
        header('Location: ../proposer_trajet.php?error=dates_incoherentes');
        exit();
    }

    try {
        // 3. INSERTION SQL
        $sql = "INSERT INTO trajets (
                    id_chauffeur, 
                    ville_depart, 
                    ville_arrivee, 
                    date_depart, 
                    date_arrivee, 
                    prix, 
                    places_disponibles, 
                    id_vehicule,
                    statut
                ) VALUES (
                    :id_c, :v_dep, :v_arr, :d_dep, :d_arr, :prix, :places, :id_v, 'ouvert'
                )";

        $stmt = $pdo->prepare($sql);
        $resultat = $stmt->execute([
            'id_c'   => $id_chauffeur,
            'v_dep'  => $ville_depart,
            'v_arr'  => $ville_arrivee,
            'd_dep'  => $date_depart,
            'd_arr'  => $date_arrivee,
            'prix'   => $prix_saisi, // ✅ Enregistre 12 si le chauffeur a saisi 12
            'places' => $places,
            'id_v'   => $id_vehicule
        ]);

        if ($resultat) {
            // 4. REDIRECTION SI SUCCÈS
            header('Location: ../mon_espace.php?success=trajet_publie');
            exit();
        } else {
            echo "La requête a échoué sans erreur SQL précise.";
        }

    } catch (PDOException $e) {
        die("Erreur SQL : " . $e->getMessage());
    }
} else {
    header('Location: ../proposer_trajet.php');
    exit();
}