<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - EcoRide</title>

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
        
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Contenu principal avec espacement vertical fluide -->
    <main class="container my-auto py-5 d-flex justify-content-center">
        <div class="col-lg-5 col-md-7 col-12">
            
            <div class="card card-custom p-4 p-md-5 bg-white">
                
                <div class="text-center mb-4">
                    <div class="bg-success-subtle d-inline-block p-3 rounded-circle text-sapin mb-3">
                        <i class="bi bi-key-fill fs-2"></i>
                    </div>
                    <h1 class="h3 fw-bold text-dark mb-2">Récupération de mot de passe</h1>
                    <p class="text-muted small m-0">
                        Entrez votre adresse email ci-dessous pour recevoir un lien de réinitialisation.
                    </p>
                </div>

                <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                    <div class="alert alert-success rounded-3 p-3 mb-4 small" role="alert">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-check-circle-fill fs-4 me-2"></i>
                            <div>
                                <strong>Note :</strong> Cette fonctionnalité est en cours de développement. Dans la version finale, un email sera envoyé.
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="" method="POST">
                    
                    <!-- Champ Email -->
                    <div class="mb-4">
                        <label for="email" class="form-label fw-bold small text-muted">Votre adresse email :</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-0 text-muted"><i class="bi bi-envelope-fill"></i></span>
                            <input type="email" class="form-control bg-light border-0 py-2" id="email" name="email" placeholder="exemple@email.com" required>
                        </div>
                    </div>

                    <!-- Bouton d'envoi -->
                    <button type="submit" class="btn btn-sapin w-100 py-3 rounded-3 fs-6 shadow-sm mb-3">
                        <i class="bi bi-send-fill me-2"></i>Envoyer le lien
                    </button>

                    <!-- Lien retour -->
                    <div class="text-center mt-3">
                        <a href="login.php" class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i>Retour à la page de connexion
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