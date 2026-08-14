<?php
session_start();
// Si l'utilisateur est déjà connecté, on le redirige vers l'accueil
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Connexion</title>

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
        .login-card { 
            max-width: 440px; 
            margin: 40px auto; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.06); 
            border: none; 
            border-radius: 16px;
            overflow: hidden;
        }
        .brand-header { 
            background: linear-gradient(135deg, #1F8653, #176840); 
            color: white; 
            text-align: center; 
            padding: 30px 20px; 
        }
        .form-control:focus {
            border-color: #1F8653;
            box-shadow: 0 0 0 0.25rem rgba(31, 134, 83, 0.15);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Zone principale étirable -->
    <main class="container flex-grow-1 py-5">
        <div class="w-100">
            <div class="card login-card">
                <div class="brand-header">
                    <h1 class="h3 mb-1 fw-bold">Ravi de vous revoir</h1>
                    <p class="mb-0 small text-white-50">Connectez-vous pour gérer vos covoiturages</p>
                </div>
                
                <div class="card-body p-4 bg-white">
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger p-2.5 small rounded-3" role="alert">
                            ⚠️ <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php elseif (isset($_GET['erreur']) && $_GET['erreur'] === 'auth_requise'): ?>
                        <div class="alert alert-warning p-2.5 small text-dark rounded-3" role="alert">
                            🔒 Vous devez vous connecter pour accéder à votre espace.
                        </div>
                    <?php elseif (isset($_GET['source']) && $_GET['source'] === 'trajet'): ?>
                        <div class="alert alert-info p-2.5 small text-dark fw-semibold rounded-3" role="alert">
                            🚗 Connectez-vous en un clic pour finaliser votre réservation sur ce trajet !
                        </div>
                    <?php endif; ?>

                    <form action="scripts/traitement_login.php" method="POST">
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Adresse e-mail</label>
                            <input type="email" name="email" id="email" class="form-control py-2 rounded-3" placeholder="Ex: jose@ecoride.fr" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Mot de passe</label>
                            <input type="password" name="password" id="password" class="form-control py-2 rounded-3" placeholder="••••••••" required>
                        </div>
                        
                        <button type="submit" class="btn btn-sapin w-100 rounded-pill py-2 mb-3">Se connecter</button>
                    </form>
                    
                    <div class="text-center mt-3 small">
                        <p class="text-muted mb-0">Nouveau sur EcoRide ? <a href="inscription.php" class="text-sapin fw-bold text-decoration-none">Créez un compte</a></p>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php require_once 'includes/footer.php'; ?>

    <!-- Scripts JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>