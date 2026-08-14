<?php
session_start();
require_once 'config/db.php';

// SÉCURITÉ : L'utilisateur doit être connecté et l'ID du trajet doit être présent dans l'URL
if (!isset($_SESSION['user_id']) || !isset($_GET['id_trajet'])) {
    header('Location: mon_espace.php');
    exit;
}

$id_trajet = intval($_GET['id_trajet']);

// On récupère les infos du trajet pour l'affichage
$stmt = $pdo->prepare("SELECT ville_depart, ville_arrivee FROM trajets WHERE id = ?");
$stmt->execute([$id_trajet]);
$trajet = $stmt->fetch();

// SÉCURITÉ : Si le trajet n'existe pas en base, on redirige pour éviter une page vide
if (!$trajet) {
    header('Location: mon_espace.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laisser un avis - EcoRide</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700;800&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        body { 
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f7f6; 
            color: #2c3e50;
        }
        h1, h2, h3, h4, h5, .font-heading {
            font-family: 'Montserrat', sans-serif;
        }
        .text-sapin { color: #1F8653 !important; }
        .bg-sapin { background-color: #1F8653 !important; }
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
        .btn-sapin {
            background-color: #1F8653;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            border: none;
            transition: all 0.2s ease;
        }
        .btn-sapin:hover {
            background-color: #176840;
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Contenu principal -->
    <main class="container py-5 flex-grow-1 d-flex justify-content-center align-items-center">
        <div class="col-lg-6 col-md-8 col-12">
            
            <div class="card card-custom p-4 p-md-5 bg-white">
                
                <div class="text-center mb-4">
                    <div class="bg-success-subtle d-inline-block p-3 rounded-circle text-sapin mb-3">
                        <i class="bi bi-star-fill fs-2"></i>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-2">Votre avis sur le trajet</h1>
                    <p class="text-sapin fw-bold fs-5 m-0">
                        <?= htmlspecialchars($trajet['ville_depart']) ?> <i class="bi bi-arrow-right mx-1"></i> <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                    </p>
                </div>

                <form action="scripts/traitement_avis.php" method="POST">
                    <input type="hidden" name="id_trajet" value="<?= $id_trajet ?>">

                    <!-- Sélection de la note -->
                    <div class="mb-4">
                        <label for="note" class="form-label fw-bold small text-muted">Note :</label>
                        <select name="note" id="note" class="form-select bg-light border-0 py-2 fw-semibold" required>
                            <option value="5">⭐⭐⭐⭐⭐ (Excellent)</option>
                            <option value="4">⭐⭐⭐⭐ (Très bien)</option>
                            <option value="3" selected>⭐⭐⭐ (Moyen)</option>
                            <option value="2">⭐⭐ (Décevant)</option>
                            <option value="1">⭐ (À éviter)</option>
                        </select>
                    </div>

                    <!-- Statut du voyage -->
                    <div class="mb-4">
                        <label for="voyage_statut" class="form-label fw-bold small text-muted">Comment s'est déroulé le voyage ?</label>
                        <select name="voyage_statut" id="voyage_statut" class="form-select bg-light border-0 py-2 fw-semibold" required>
                            <option value="bien_passe" selected>✅ Tout s'est bien passé</option>
                            <option value="mal_passe">⚠️ J'ai rencontré un problème (Signalement)</option>
                        </select>
                    </div>

                    <!-- Commentaire -->
                    <div class="mb-4">
                        <label for="commentaire" class="form-label fw-bold small text-muted">Votre commentaire :</label>
                        <textarea name="commentaire" id="commentaire" class="form-control bg-light border-0 p-3" rows="4" placeholder="Racontez votre expérience..." required></textarea>
                    </div>

                    <!-- Bouton d'envoi uniformisé -->
                    <button type="submit" class="btn btn-sapin w-100 py-3 rounded-3 fs-6 shadow-sm mb-3">
                        <i class="bi bi-send-fill me-2"></i>Envoyer mon avis
                    </button>

                    <!-- Annuler -->
                    <div class="text-center">
                        <a href="mon_espace.php" class="text-muted small text-decoration-none">
                            <i class="bi bi-x-circle me-1"></i>Annuler
                        </a>
                    </div>
                </form>

            </div>

        </div>
    </main>

    <!-- Footer global -->
    <?php require_once 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>