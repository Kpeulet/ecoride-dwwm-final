<?php
session_start();
require_once '../config/db.php';

// Sécurité : Réservé à l'administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php?error=Accès+non+autorisé');
    exit;
}

if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id_user = (int)$_GET['id'];

    if ($id_user <= 0) {
        header('Location: ../espace_admin.php?error=Identifiant+invalide.');
        exit;
    }

    try {
        // Empêcher l'admin de se suspendre lui-même
        if ($id_user === (int)$_SESSION['user_id']) {
            header('Location: ../espace_admin.php?error=Vous+ne+pouvez+pas+suspendre+votre+propre+compte.');
            exit;
        }

        if ($action === 'suspendre') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut_compte = 'suspendu' WHERE id = ? AND role != 'admin'");
            $stmt->execute([$id_user]);
            $msg = "Le+compte+a+été+suspendu.";
        } elseif ($action === 'activer') {
            $stmt = $pdo->prepare("UPDATE utilisateur SET statut_compte = 'actif' WHERE id = ? AND role != 'admin'");
            $stmt->execute([$id_user]);
            $msg = "Le+compte+a+été+réactivé.";
        } else {
            header('Location: ../espace_admin.php');
            exit;
        }

        header("Location: ../espace_admin.php?success=$msg");
        exit;

    } catch (PDOException $e) {
        header('Location: ../espace_admin.php?error=Erreur+:+' . urlencode($e->getMessage()));
        exit;
    }
} else {
    header('Location: ../espace_admin.php');
    exit;
}