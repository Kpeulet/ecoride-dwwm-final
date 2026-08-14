<?php
session_start();
require_once '../config/db.php'; 

// 1. SÉCURITÉ : Si pas de session, on redirige vers la connexion
if (!isset($_SESSION['user_id'])) {
    header("Location: ../connexion.php?error=session_expiree");
    exit;
}

// 2. VÉRIFICATION DE LA MÉTHODE
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../ajouter_vehicule.php");
    exit;
}

// 3. RÉCUPÉRATION ET NETTOYAGE DES DONNÉES (Synchronisé avec les names du formulaire)
$id_utilisateur   = $_SESSION['user_id'];
$marque           = htmlspecialchars($_POST['marque'] ?? '');
$modele           = htmlspecialchars($_POST['modele'] ?? '');
$couleur          = htmlspecialchars($_POST['couleur'] ?? '');
$energie          = htmlspecialchars($_POST['energie'] ?? '');
$immatriculation  = htmlspecialchars($_POST['immatriculation'] ?? '');
$date_imm         = $_POST['date_immatriculation'] ?? null; // Ajusté pour le formulaire
$nb_places        = intval($_POST['places'] ?? 0);          // Ajusté pour le formulaire

// Gestion des switchs/checkboxes de l'US 8 (1 si coché, 0 sinon)
$fumeur  = isset($_POST['accepte_fumeurs']) ? 1 : 0;
$animaux = isset($_POST['accepte_animaux']) ? 1 : 0;

// Récupération des préférences textuelles libres (US 8)
$preferences_libres = htmlspecialchars($_POST['preferences_libres'] ?? '');

try {
    // On démarre une transaction pour s'assurer que l'insertion du véhicule ET la mise à jour du profil réussissent ensemble
    $pdo->beginTransaction();

    // 4. INSERTION DANS LA TABLE VEHICULE
    $sqlVehicule = "INSERT INTO vehicule (marque, modele, couleur, energie, immatriculation, date_premiere_immat, nb_places, fumeur, animaux, id_utilisateur) 
                    VALUES (:marque, :modele, :couleur, :energie, :immatriculation, :date_imm, :nb_places, :fumeur, :animaux, :id_u)";
    
    $stmtVehicule = $pdo->prepare($sqlVehicule);
    $stmtVehicule->execute([
        'marque'          => $marque,
        'modele'          => $modele,
        'couleur'         => $couleur,
        'energie'         => $energie,
        'immatriculation' => $immatriculation,
        'date_imm'        => $date_imm,
        'nb_places'       => $nb_places,
        'fumeur'          => $fumeur,
        'animaux'         => $animaux,
        'id_u'            => $id_utilisateur
    ]);

    // 5. MISE À JOUR DE L'UTILISATEUR (Passage en chauffeur + stockage des préférences textuelles)
    $sqlUtilisateur = "UPDATE utilisateur SET est_chauffeur = 1, preferences_libres = :pref_libres WHERE id = :id_u";
    $stmtUtilisateur = $pdo->prepare($sqlUtilisateur);
    $stmtUtilisateur->execute([
        'pref_libres' => $preferences_libres,
        'id_u'        => $id_utilisateur
    ]);

    // Tout s'est bien passé, on valide la transaction en base
    $pdo->commit();

    // On met à jour la variable de session pour que l'affichage de mon_espace.php s'adapte immédiatement !
    $_SESSION['est_chauffeur'] = 1;

    // REDIRECTION : Vers l'espace personnel pour déclencher ton alerte "vehicule_ajoute"
    header("Location: ../mon_espace.php?success=vehicule_ajoute");
    exit;

} catch (PDOException $e) {
    // En cas de bug, on annule les modifications pour garder la base propre
    $pdo->rollBack();
    die("Erreur lors de l'enregistrement : " . $e->getMessage());
}
