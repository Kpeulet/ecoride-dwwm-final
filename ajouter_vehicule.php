<?php
session_start();
require_once 'config/db.php';

// Sécurité : l'utilisateur doit être connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?erreur=auth_requise');
    exit;
}

$user_id = $_SESSION['user_id'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un véhicule - EcoRide</title>

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
        .form-check-input:checked {
            background-color: #1F8653;
            border-color: #1F8653;
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
                        <div class="fs-1 text-sapin"><i class="bi bi-car-front-fill"></i></div>
                        <div>
                            <h1 class="h3 fw-bold text-dark mb-0">Ajouter un véhicule & préférences</h1>
                            <p class="text-muted small mb-0">
                                Renseignez les détails de votre véhicule. En l'enregistrant, votre profil obtiendra le statut <strong>Chauffeur</strong>.
                            </p>
                        </div>
                    </div>

                    <hr class="my-4">

                    <form action="scripts/traitement_vehicule.php" method="POST">
                        
                        <h4 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-card-heading me-1"></i> 1. Caractéristiques du véhicule</h4>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Marque :</label>
                                <input type="text" name="marque" class="form-control" placeholder="Ex: Renault" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Modèle :</label>
                                <input type="text" name="modele" class="form-control" placeholder="Ex: Clio" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Couleur :</label>
                                <input type="text" name="couleur" class="form-control" placeholder="Ex: Noir" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Énergie :</label>
                                <select name="energie" class="form-select" required>
                                    <option value="electrique">⚡ Électrique</option>
                                    <option value="thermique">⛽ Thermique</option>
                                    <option value="hybride">🔄 Hybride</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Plaque d'immatriculation :</label>
                                <input type="text" name="immatriculation" class="form-control" placeholder="Ex: AB-123-CD" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Date de 1ère immatriculation :</label>
                                <input type="date" name="date_immatriculation" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nombre de places :</label>
                                <input type="number" name="places" class="form-control" min="1" max="8" placeholder="Ex: 4" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h4 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-sliders me-1"></i> 2. Préférences à bord</h4>
                        <div class="mb-3">
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" name="accepte_fumeurs" id="fumeur" value="1">
                                <label class="form-check-label fw-semibold" for="fumeur">🚬 J'accepte les fumeurs</label>
                            </div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="accepte_animaux" id="animal" value="1">
                                <label class="form-check-label fw-semibold" for="animal">🐶 J'accepte les animaux de compagnie</label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Vos préférences personnalisées :</label>
                            <textarea name="preferences_libres" class="form-control" rows="3" placeholder="Ex: Pas de musique forte, bagages légers uniquement, discussion bienvenue..."></textarea>
                        </div>

                        <div class="d-flex gap-2 justify-content-end mt-4">
                            <a href="mon_espace.php" class="btn btn-light fw-bold px-4 rounded-pill">Annuler</a>
                            <button type="submit" name="enregistrer_vehicule" class="btn btn-sapin fw-bold px-4 rounded-pill"><i class="bi bi-check-lg me-1"></i> Enregistrer le véhicule</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer global -->
    <?php require_once 'includes/footer.php'; ?>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>