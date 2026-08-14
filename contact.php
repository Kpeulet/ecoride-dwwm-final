<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - EcoRide</title>
    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .text-sapin { color: #1F8653; }
        .bg-sapin { background-color: #1F8653; }
        .btn-sapin {
            background-color: #1F8653;
            color: #ffffff;
            font-weight: 600;
        }
        .btn-sapin:hover {
            background-color: #176840;
            color: #ffffff;
        }
    </style>
</head>
<body class="bg-light d-flex flex-column min-vh-100">

    <!-- Navbar -->
    <?php include 'includes/navbar.php'; ?>

    <!-- Contenu Principal -->
    <main class="container my-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        
                        <div class="text-center mb-4">
                            <i class="bi bi-envelope-paper-heart-fill text-sapin display-4"></i>
                            <h2 class="fw-bold text-sapin mt-2">Contactez-nous</h2>
                            <p class="text-muted">Une question ou une suggestion ? Laissez-nous un message !</p>
                        </div>

                        <!-- Formulaire de contact (Visuel) -->
                        <form action="#" method="POST">
                            <div class="mb-3">
                                <label for="nom" class="form-label fw-bold text-secondary">Votre Nom / Pseudo</label>
                                <input type="text" class="form-class form-control rounded-3" id="nom" name="nom" placeholder="Ex: Sophie" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label fw-bold text-secondary">Adresse Email</label>
                                <input type="email" class="form-control rounded-3" id="email" name="email" placeholder="Ex: sophie@example.com" required>
                            </div>

                            <div class="mb-3">
                                <label for="sujet" class="form-label fw-bold text-secondary">Sujet</label>
                                <input type="text" class="form-control rounded-3" id="sujet" name="sujet" placeholder="Ex: Question sur un trajet" required>
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label fw-bold text-secondary">Message</label>
                                <textarea class="form-control rounded-3" id="message" name="message" rows="4" placeholder="Votre message..." required></textarea>
                            </div>

                            <button type="submit" class="btn btn-sapin w-100 rounded-pill py-2">
                                <i class="bi bi-send-fill me-1"></i> Envoyer le message
                            </button>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>