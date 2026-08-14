<?php
session_start();
require_once 'config/db.php';

// Récupération des avis validés pour la section témoignages
$stmtAvis = $pdo->query("
    SELECT a.commentaire, a.note, u.pseudo 
    FROM avis a
    JOIN utilisateur u ON a.id_expediteur = u.id
    WHERE a.statut = 'valide' OR a.statut IS NULL
    ORDER BY a.id DESC 
    LIMIT 3
");
$avis_liste = $stmtAvis->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Covoiturage éco-responsable</title>

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
        .hero-section {
            background: linear-gradient(135deg, #1F8653 0%, #176840 100%);
            color: white;
            padding: 60px 0 90px 0;
            border-radius: 0 0 30px 30px;
        }
        .search-card {
            margin-top: -50px;
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        .btn-sapin {
            background-color: #1F8653;
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            border: none;
        }
        .btn-sapin:hover {
            background-color: #176840;
            color: #ffffff;
        }
        .text-sapin {
            color: #1F8653 !important;
        }
        .feature-img {
            border-radius: 16px;
            object-fit: cover;
            width: 100%;
            height: 220px;
            box-shadow: 0 6px 15px rgba(0,0,0,0.06);
        }
        .testimonial-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
            height: 100%;
            transition: transform 0.2s ease;
        }
        .testimonial-card:hover {
            transform: translateY(-4px);
        }
        .star-yellow {
            color: #ffc107;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Banner Hero -->
    <section class="hero-section text-center">
        <div class="container">
            <h1 class="fw-extrabold display-4 mb-3">Bienvenue chez EcoRide</h1>
            <p class="lead fs-5 text-white-50 max-w-600 mx-auto">
                La solution de covoiturage responsable, économique et 100% sécurisée.
            </p>
        </div>
    </section>

    <!-- Formulaire de recherche rapide -->
    <div class="container mb-5">
        <div class="card search-card p-4 bg-white">
            <form action="recherche.php" method="GET">
                <div class="row g-3 align-items-end">
                    <div class="col-lg-3 col-md-6">
                        <label for="depart" class="form-label fw-bold small text-muted">Ville de départ</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-geo-alt-fill text-sapin"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" id="depart" name="depart" placeholder="Ex: Paris" required>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="arrivee" class="form-label fw-bold small text-muted">Ville d'arrivée</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-pin-map-fill text-sapin"></i></span>
                            <input type="text" class="form-control bg-light border-start-0" id="arrivee" name="arrivee" placeholder="Ex: Lyon" required>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <label for="date" class="form-label fw-bold small text-muted">Date du voyage</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="bi bi-calendar-event text-sapin"></i></span>
                            <input type="date" class="form-control bg-light border-start-0" id="date" name="date">
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <button type="submit" class="btn btn-sapin w-100 py-2 rounded-3 fw-bold">
                            <i class="bi bi-search me-1"></i> Rechercher
                        </button>
                    </div>
                </div>

                <div class="form-check form-switch mt-3">
                    <input class="form-check-input" type="checkbox" role="switch" id="ecologique" name="ecologique" value="1">
                    <label class="form-check-input-label small fw-semibold text-muted" for="ecologique">
                        <i class="bi bi-lightning-charge-fill text-warning me-1"></i> Uniquement les trajets écologiques (Véhicule électrique)
                    </label>
                </div>
            </form>
        </div>
    </div>

    <!-- Section Concept -->
    <section class="container py-4 my-3">
        <div class="row align-items-center g-4">
            <div class="col-md-6">
                <h2 class="fw-bold text-dark mb-3"><i class="bi bi-leaf text-sapin me-2"></i>Notre concept éco-responsable</h2>
                <p class="text-muted">
                    Chez <strong>EcoRide</strong>, nous croyons qu'un déplacement quotidien ne devrait pas se faire au détriment de notre planète. Notre plateforme met en relation des conducteurs et des passagers partageant les mêmes valeurs afin de réduire l'empreinte carbone collective.
                </p>
                <p class="text-muted">
                    Que vous utilisiez un véhicule thermique optimisé ou une voiture 100% électrique, chaque trajet partagé contribue à désengorger nos routes et à assainir notre air. Économisez sur vos frais de route tout en faisant un geste concret pour l'environnement.
                </p>
            </div>
            <div class="col-md-6">
                <div class="row g-3">
                    <!-- Image 1 : Covoiturage -->
                    <div class="col-6">
                        <img src="assets/images/hero-covoiturage.jpg" 
                             alt="Passagers souriants en covoiturage EcoRide" 
                             class="img-fluid rounded-4 shadow" 
                             style="height: 200px; width: 100%; object-fit: cover;">
                    </div>
                    <!-- Image 2 : Borne Électrique -->
                    <div class="col-6">
                        <img src="assets/images/recharge-electrique.jpg" 
                             alt="Station de recharge électrique" 
                             class="img-fluid rounded-4 shadow-sm" 
                             style="height: 200px; width: 100%; object-fit: cover;">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section Témoignages / Avis -->
    <section class="container py-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold text-dark mb-1"><i class="bi bi-chat-quote text-sapin me-2"></i>Ce que nos membres disent de nous</h2>
            <p class="text-muted small">Avis authentiques de nos covoitureurs réguliers.</p>
        </div>

        <div class="row g-4">
            <?php if (!empty($avis_liste)): ?>
                <?php foreach ($avis_liste as $avis): ?>
                    <div class="col-md-4">
                        <div class="card testimonial-card p-4 bg-white">
                            <div class="mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi bi-star-fill <?= $i <= $avis['note'] ? 'star-yellow' : 'text-muted opacity-25' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="text-muted fst-italic small mb-3">"<?= htmlspecialchars_decode($avis['commentaire'], ENT_QUOTES) ?>"</p>
                            <div class="fw-bold text-dark small text-end">&mdash; <?= htmlspecialchars($avis['pseudo']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center text-muted fst-italic py-3">
                    Aucun avis affiché pour le moment.
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer global -->
    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>