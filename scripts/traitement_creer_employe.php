<?php
session_start();
require_once '../config/db.php';

// Sécurité : Réservé à l'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php?error=Accès+non+autorisé');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pseudo = trim($_POST['pseudo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($pseudo) || empty($email) || empty($password)) {
        header('Location: ../espace_admin.php?error=Veuillez+remplir+tous+les+champs.');
        exit;
    }

    if (strlen($password) < 8) {
        header('Location: ../espace_admin.php?error=Le+mot+de+passe+doit+contenir+au+moins+8+caractères.');
        exit;
    }

    try {
        // Vérifier si le pseudo ou l'email existe déjà
        $stmtCheck = $pdo->prepare("SELECT id FROM utilisateur WHERE email = ? OR pseudo = ?");
        $stmtCheck->execute([$email, $pseudo]);

        if ($stmtCheck->fetch()) {
            header('Location: ../espace_admin.php?error=Ce+pseudo+ou+cet+email+est+déjà+utilisé.');
            exit;
        }

        // Création du compte employé
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmtInsert = $pdo->prepare("
            INSERT INTO utilisateur (pseudo, email, password, role, credits, statut_compte) 
            VALUES (?, ?, ?, 'employe', 0, 'actif')
        ");
        $stmtInsert->execute([$pseudo, $email, $passwordHash]);

        header('Location: ../espace_admin.php?success=Compte+employé+créé+avec+succès+!');
        exit;

    } catch (PDOException $e) {
        header('Location: ../espace_admin.php?error=Erreur+serveur+:+' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: ../espace_admin.php');
    exit;
}