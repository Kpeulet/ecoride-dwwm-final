<?php
// 1. INDISPENSABLE : Démarrer la session en tout premier
session_start(); 

// 2. Connexion à la base de données
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sinscrire'])) {
    
    $pseudo = htmlspecialchars(trim($_POST['pseudo']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password_brut = $_POST['password'];

    // Double vérification de sécurité côté serveur (Regex) pour l'US 7
    if (!preg_match('/(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{8,}/', $password_brut)) {
        header('Location: ../inscription.php?erreur=mdp_faible');
        exit();
    }

    try {
        // CORRECTION SÉCURITÉ : Vérifier si le pseudo ou l'email existe déjà
        $stmtCheck = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ? OR pseudo = ?");
        $stmtCheck->execute([$email, $pseudo]);
        
        if ($stmtCheck->fetch()) {
            // Renvoie le code exact attendu par les alertes de ton inscription.php
            header('Location: ../inscription.php?erreur=email_deja_pris');
            exit;
        }

        // Hachage sécurisé du mot de passe
        $password_hache = password_hash($password_brut, PASSWORD_BCRYPT);

        // CORRECTION ÉNONCÉ : On offre bien 20 crédits de bienvenue
        // Assure-toi que la colonne s'appelle bien 'password' dans ta BDD (et non 'mot_de_passe')
        $sql = "INSERT INTO utilisateur (pseudo, email, password, role, credits) VALUES (?, ?, ?, 'utilisateur', 20)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$pseudo, $email, $password_hache]);
        
        $id_utilisateur = $pdo->lastInsertId();

        // Initialisation de la session immédiatement après inscription
        $_SESSION['user_id'] = $id_utilisateur;
        $_SESSION['pseudo'] = $pseudo; 
        $_SESSION['role'] = 'utilisateur';

        // Redirection vers l'espace personnel ou l'accueil avec un message de succès
        header('Location: ../index.php?success=Bienvenue sur EcoRide ! Vos 20 crédits de bienvenue ont été ajoutés.');
        exit;
        
    } catch (PDOException $e) {
        // En production/examen, on évite d'afficher l'erreur SQL brute à l'utilisateur
        error_log("Erreur inscription : " . $e->getMessage());
        header('Location: ../inscription.php?erreur=technique');
        exit;
    }
} else {
    header("Location: ../inscription.php");
    exit();
}
?>