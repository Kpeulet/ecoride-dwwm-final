<?php
session_start();
require_once 'config/db.php';
// Vérifie que auth.php redirige bien vers login si non connecté
require_once 'auth.php'; 

$id_chauffeur = $_SESSION['user_id']; 

// On récupère les véhicules du chauffeur
$stmt = $pdo->prepare("SELECT id, marque, modele FROM vehicule WHERE id_utilisateur = ?");
$stmt->execute([$id_chauffeur]);
$vehicules = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proposer un trajet - EcoRide</title>

    <!-- Google Fonts (Montserrat & Open Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        html, body {
            height: 100%;
        }
        body { 
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f7f6; 
            color: #2c3e50;
        }
        h1, h2, h3, h4, h5, .font-heading {
            font-family: 'Montserrat', sans-serif;
        }
        .text-sapin {
            color: #1F8653 !important;
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
        .form-card { 
            border: none; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.06); 
            border-radius: 16px; 
        }
        .form-control:focus, .form-select:focus {
            border-color: #1F8653;
            box-shadow: 0 0 0 0.25rem rgba(31, 134, 83, 0.15);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Contenu Principal -->
    <main class="container py-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card form-card p-4 p-md-5 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="fs-1 text-sapin"><i class="bi bi-plus-circle-fill"></i></div>
                        <div>
                            <h1 class="h3 fw-bold text-dark mb-0">Proposer un nouveau trajet</h1>
                            <p class="text-muted small mb-0">Remplissez les détails pour ouvrir les réservations aux passagers.</p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <form action="scripts/traitement_trajet.php" method="POST">
                        
                        <h4 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-geo-alt-fill me-1"></i> 1. Itinéraire & Horaires</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ville de départ :</label>
                                <input type="text" name="ville_depart" class="form-control" placeholder="Ex: Paris" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Ville d'arrivée :</label>
                                <input type="text" name="ville_arrivee" class="form-control" placeholder="Ex: Lyon" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date et heure de départ :</label>
                                <input type="datetime-local" name="date_depart" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date et heure d'arrivée prévue :</label>
                                <input type="datetime-local" name="date_arrivee" class="form-control" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h4 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-car-front-fill me-1"></i> 2. Logistique & Tarification</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Véhicule utilisé :</label>
                                <select name="id_vehicule" class="form-select" required>
                                    <option value="" disabled selected>-- Choisir un véhicule --</option>
                                    <?php foreach ($vehicules as $v): ?>
                                        <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text mt-2 small">
                                    Véhicule non listé ? <a href="ajouter_vehicule.php" class="text-sapin fw-bold text-decoration-none">Ajoutez-en un nouveau ici</a>.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre de places :</label>
                                <input type="number" name="places_disponibles" class="form-control" placeholder="Places disponibles" min="1" max="8" required>
                            </div>

                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Prix souhaité (par passager) :</label>
                                <div class="input-group">
                                    <input type="number" name="prix" id="prix_chauffeur" class="form-control" min="1" placeholder="Ex: 10" required>
                                    <span class="input-group-text bg-light">crédits</span>
                                </div>
                                <div class="alert alert-info py-2 px-3 mt-2 mb-0 border-0 rounded-3 small text-primary" id="info_prix">
                                    <i class="bi bi-info-circle-fill me-1"></i> Coût total passager : <strong><span id="prix_total">0</span> crédits</strong> (dont 2 crédits de frais de plateforme inclus).
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="mon_espace.php" class="btn btn-light fw-bold px-4 rounded-pill">Annuler</a>
                            <button type="submit" class="btn btn-sapin fw-bold px-4 rounded-pill"><i class="bi bi-rocket-takeoff-fill me-1"></i> Publier l'annonce</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer global -->
    <?php require_once 'includes/footer.php'; ?>

    <!-- Calcul dynamique des frais -->
    <script>
        const inputPrix = document.getElementById('prix_chauffeur');
        const spanTotal = document.getElementById('prix_total');

        inputPrix.addEventListener('input', function() {
            const prixSaisi = parseInt(inputPrix.value) || 0;
            if (prixSaisi > 0) {
                spanTotal.textContent = prixSaisi + 2;
            } else {
                spanTotal.textContent = 0;
            }
        });
    </script>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>