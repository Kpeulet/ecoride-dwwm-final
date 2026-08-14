<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Détection dynamique du nom du fichier actuel pour souligner l'onglet actif
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .bg-custom-sapin {
        background-color: #1F8653 !important;
    }
    .custom-navbar {
        font-family: 'Montserrat', sans-serif;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .custom-navbar .nav-link {
        font-weight: 500;
        font-size: 1rem;
        transition: opacity 0.2s ease;
    }
    .custom-navbar .nav-link:hover {
        opacity: 0.85;
    }
    .custom-navbar .active-link {
        font-weight: 700 !important;
        text-decoration: underline;
        text-decoration-color: #ffffff;
        text-decoration-thickness: 2px;
        text-underline-offset: 6px;
    }
    .btn-nav-inscription {
        background-color: #ffffff;
        color: #1F8653 !important;
        font-weight: 700;
        border-radius: 50rem;
        padding: 0.4rem 1.2rem;
        border: none;
        transition: all 0.2s ease;
    }
    .btn-nav-inscription:hover {
        background-color: #e8f8f5;
        color: #176840 !important;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark bg-custom-sapin px-4 sticky-top custom-navbar">
    <div class="container-fluid">
        <a class="navbar-brand fw-bold fs-3 text-white" href="index.php">🍃 EcoRide</a>
      
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto align-items-center">
                
                <a class="nav-link text-white mx-2 <?= ($current_page == 'index.php') ? 'active-link' : '' ?>" href="index.php">Accueil</a>
                
                <a class="nav-link text-white mx-2 <?= ($current_page == 'recherche.php') ? 'active-link' : '' ?>" href="recherche.php">Trouver un trajet</a>
                
                <a class="nav-link text-white mx-2 <?= ($current_page == 'contact.php') ? 'active-link' : '' ?>" href="contact.php">Contact</a>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Bouton Mon Espace -->
                    <a class="btn btn-light fw-bold ms-2 rounded-pill px-3 text-dark <?= ($current_page == 'mon_espace.php' || $current_page == 'profil.php') ? 'border border-2 border-white' : '' ?>" href="mon_espace.php">
                        <i class="bi bi-person-circle text-sapin me-1"></i> Mon Espace (<?= htmlspecialchars($_SESSION['pseudo'] ?? '') ?>)
                    </a>

                    <!-- Lien pointant bien vers scripts/logout.php -->
                    <a class="btn btn-outline-light btn-sm ms-2 rounded-pill px-3" href="scripts/logout.php">Déconnexion</a>
                <?php else: ?>
                    <a class="nav-link text-white mx-2 <?= ($current_page == 'login.php') ? 'active-link' : '' ?>" href="login.php">Connexion</a>
                    <a class="btn btn-nav-inscription ms-2" href="inscription.php">Inscription</a>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</nav>