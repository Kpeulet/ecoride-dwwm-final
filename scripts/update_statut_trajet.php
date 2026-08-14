<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $id_trajet = intval($_POST['id_trajet']);
    $nouveau_statut = $_POST['statut'];
    $id_chauffeur = $_SESSION['user_id'];

    try {
        $stmt = $pdo->prepare("UPDATE trajets SET statut = ? WHERE id = ? AND id_chauffeur = ?");
        $stmt->execute([$nouveau_statut, $id_trajet, $id_chauffeur]);

        // Vérification si une ligne a bien été modifiée
        if ($stmt->rowCount() > 0) {
            header('Location: ../mon_espace.php?success=Le statut a été mis à jour avec succès');
        } else {
            header('Location: ../mon_espace.php?error=Erreur lors de la mise à jour ou action non autorisée');
        }
    } catch (PDOException $e) {
        header('Location: ../mon_espace.php?error=Erreur SQL : ' . $e->getMessage());
    }
    exit;
}
header('Location: ../mon_espace.php');