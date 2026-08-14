<?php
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Inscription</title>

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
        .register-card { 
            border: none; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.06); 
            border-radius: 16px; 
            max-width: 460px;
            margin: 0 auto;
        }
        .form-control:focus {
            border-color: #1F8653;
            box-shadow: 0 0 0 0.25rem rgba(31, 134, 83, 0.15);
        }
        .input-group-text {
            border-top-left-radius: 0.5rem;
            border-bottom-left-radius: 0.5rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Zone principale étirable avec espacement aéré -->
    <main class="container py-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-12">
                
                <div class="card register-card p-4 bg-white">
                    <div class="text-center mb-4">
                        <div class="fs-1 text-sapin"><i class="bi bi-person-plus-fill"></i></div>
                        <h1 class="h3 fw-bold text-dark mt-2">Créer un compte</h1>
                        <p class="text-muted small">Rejoins la communauté EcoRide dès aujourd'hui.</p>
                    </div>

                    <?php if (isset($_GET['erreur'])): ?>
                        <?php if ($_GET['erreur'] === 'email_deja_pris'): ?>
                            <div class="alert alert-danger text-center fw-semibold rounded-3 p-2.5 small" role="alert">
                                ⚠️ Cette adresse email est déjà associée à un compte.
                            </div>
                        <?php elseif ($_GET['erreur'] === 'mdp_faible'): ?>
                            <div class="alert alert-danger text-center fw-semibold rounded-3 p-2.5 small" role="alert">
                                ⚠️ Le mot de passe ne respecte pas les critères de sécurité.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <form action="scripts/traitement_inscription.php" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pseudo</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                                <input type="text" name="pseudo" class="form-control py-2" placeholder="Votre pseudo" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope"></i></span>
                                <input type="email" name="email" class="form-control py-2" placeholder="exemple@email.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Mot de passe</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="bi bi-lock"></i></span>
                                <input type="password" 
                                       name="password" 
                                       class="form-control py-2" 
                                       placeholder="Mot de passe sécurisé" 
                                       pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W]).{8,}"
                                       title="Le mot de passe doit contenir au moins 8 caractères, dont une majuscule, une minuscule, un chiffre et un caractère spécial."
                                       required>
                            </div>
                            <div class="form-text text-muted mt-2" style="font-size: 0.8rem;">
                                💡 Doit contenir au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre et 1 symbole.
                            </div>
                        </div>

                        <button type="submit" name="sinscrire" class="btn btn-sapin w-100 fw-bold py-2 rounded-pill mb-3">S'inscrire</button>
                    </form>

                    <div class="text-center mt-3 small">
                        <p class="mb-0 text-muted">Déjà inscrit ? <a href="login.php" class="text-sapin fw-bold text-decoration-none">Connectez-vous ici</a></p>
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