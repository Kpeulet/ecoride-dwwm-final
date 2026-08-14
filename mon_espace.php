<?php
session_start();
require_once 'config/db.php';

// Sécurité : redirection vers login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?erreur=auth_requise');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fonction utilitaire pour formater les dates en français
function dateEnFrancais($dateStr) {
    $date = new DateTime($dateStr);
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    
    return $jours[$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[$date->format('n')] . ' ' . $date->format('Y') . ' à ' . $date->format('H\hi');
}

// 1. Récupération des informations de l'utilisateur
$stmtUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id = ?");
$stmtUser->execute([$user_id]);
$user = $stmtUser->fetch();

// 2. Récupération des véhicules de l'utilisateur
$stmtVehicules = $pdo->prepare("SELECT * FROM vehicule WHERE id_utilisateur = ?");
$stmtVehicules->execute([$user_id]);
$vehicules = $stmtVehicules->fetchAll();

// 3. Récupération des trajets que l'utilisateur PROPOSE en tant que CHAUFFEUR
$stmtTrajetsChauffeur = $pdo->prepare("
    SELECT t.*, v.modele, v.immatriculation, v.energie 
    FROM trajets t
    LEFT JOIN vehicule v ON t.id_vehicule = v.id
    WHERE t.id_chauffeur = ?
    ORDER BY t.date_depart DESC
");
$stmtTrajetsChauffeur->execute([$user_id]);
$mes_trajets_proposes = $stmtTrajetsChauffeur->fetchAll();

// 4. Récupération des réservations de l'utilisateur en tant que PASSAGER
$stmtReservations = $pdo->prepare("
    SELECT r.id AS id_reservation, r.id_trajet, t.ville_depart, t.ville_arrivee, t.date_depart, t.prix, t.statut AS statut_trajet,
           a.id AS avis_id
    FROM reservations r
    JOIN trajets t ON r.id_trajet = t.id
    LEFT JOIN avis a ON a.id_trajet = t.id AND a.id_expediteur = r.id_utilisateur
    WHERE r.id_utilisateur = ?
    ORDER BY t.date_depart DESC
");
$stmtReservations->execute([$user_id]);
$mes_reservations = $stmtReservations->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Espace Personnel - EcoRide</title>

    <!-- Google Fonts -->
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
        .space-card { 
            border: none; 
            box-shadow: 0 6px 18px rgba(0,0,0,0.05); 
            border-radius: 16px; 
        }
        .statut-badge { 
            padding: 6px 12px; 
            border-radius: 20px; 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase; 
        }
        .badge-ouvert { background-color: #e8f8f5; color: #16a085; }
        .badge-en-cours { background-color: #fef9e7; color: #f39c12; }
        .badge-termine { background-color: #ebf5fb; color: #2980b9; }
        .badge-annule { background-color: #fdf2f2; color: #c81e1e; }
        
        .credit-badge {
            background: linear-gradient(135deg, #1F8653 0%, #176840 100%);
            color: #fff;
            padding: 10px 20px;
            border-radius: 50rem;
            font-weight: 700;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Contenu Principal -->
    <main class="container py-5 flex-grow-1">
        
        <!-- Alerts Feedback -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fw-semibold rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_GET['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fw-semibold rounded-4 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($_GET['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Entête Espace Utilisateur -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
            <div>
                <h1 class="fw-bold h2 text-dark m-0">Mon Espace Personnel</h1>
                <p class="text-muted small mb-0">Bienvenue sur votre tableau de bord EcoRide.</p>
            </div>
            <div class="align-self-start align-self-md-auto">
                <div class="credit-badge shadow-sm d-inline-flex align-items-center gap-2 fs-5">
                    <i class="bi bi-wallet2"></i>
                    <span><?= htmlspecialchars($user['credits']) ?> crédits</span>
                </div>
            </div>
        </div>

        <!-- Profil & Infos -->
        <div class="card space-card p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="fs-2 text-sapin"><i class="bi bi-person-circle"></i></div>
                    <div>
                        <h3 class="h5 fw-bold text-dark mb-0">Bonjour, <?= htmlspecialchars($user['pseudo']) ?></h3>
                        <div class="small mt-1">
                            <strong>Statut : </strong> 
                            <?= $user['est_chauffeur'] 
                                ? '<span class="badge bg-success-subtle text-success border border-success px-2 py-1 rounded-pill"><i class="bi bi-car-front-fill me-1"></i> Passager & Chauffeur</span>' 
                                : '<span class="badge bg-secondary-subtle text-secondary border px-2 py-1 rounded-pill"><i class="bi bi-person-walking me-1"></i> Passager uniquement</span>' ?>
                        </div>
                    </div>
                </div>

                <!-- Bouton d'accès au profil -->
                <a href="profil.php" class="btn btn-outline-success btn-sm rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2">
                    <i class="bi bi-gear-fill"></i> Modifier mon profil
                </a>
            </div>
            
            <?php if (!empty($user['preferences_libres'])): ?>
                <div class="bg-light p-3 rounded-3 border-start border-success border-4 mt-2">
                    <div class="fw-semibold text-dark small mb-1"><i class="bi bi-chat-quote me-1 text-sapin"></i> Mes préférences de voyage :</div>
                    <div class="text-muted small fst-italic">"<?= htmlspecialchars_decode($user['preferences_libres']) ?>"</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section Véhicules -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="h4 fw-bold text-dark m-0"><i class="bi bi-car-front text-sapin me-2"></i>Mes Véhicules</h2>
            <?php if (!empty($vehicules)): ?>
                <a href="liste_vehicules.php" class="text-sapin text-decoration-none fw-semibold small">
                    Voir la liste complète <i class="bi bi-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>

        <div class="card space-card p-4 bg-white mb-4">
            <?php if (empty($vehicules)): ?>
                <div class="text-center py-3">
                    <p class="text-muted mb-3">Vous n'avez pas encore enregistré de véhicule. Ajoutez-en un pour pouvoir proposer vos propres trajets et devenir chauffeur !</p>
                    <a href="ajouter_vehicule.php" class="btn btn-sapin fw-bold rounded-pill px-4">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter mon premier véhicule
                    </a>
                </div>
            <?php else: ?>
                <div class="row g-3 mb-3">
                    <?php foreach ($vehicules as $vehicule): ?>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border h-100">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-sapin"><?= htmlspecialchars($vehicule['marque']) ?> <?= htmlspecialchars($vehicule['modele']) ?></strong>
                                    <span class="badge bg-white text-dark border px-2 py-1 rounded-pill small"><?= htmlspecialchars($vehicule['energie'] ?? 'Électrique') ?></span>
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-card-text me-1"></i> <?= htmlspecialchars($vehicule['immatriculation']) ?> &bull; 
                                    <i class="bi bi-palette me-1"></i> <?= htmlspecialchars($vehicule['couleur']) ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="d-flex flex-wrap gap-2 pt-2 border-top">
                    <a href="ajouter_vehicule.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill px-3">
                        <i class="bi bi-plus-lg me-1"></i> Ajouter un véhicule
                    </a>
                    <a href="proposer_trajet.php" class="btn btn-sapin btn-sm fw-bold rounded-pill px-3">
                        <i class="bi bi-rocket-takeoff me-1"></i> Proposer un trajet
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Section Trajets Proposés (Chauffeur) -->
        <?php if ($user['est_chauffeur']): ?>
            <h2 class="h4 fw-bold text-dark mt-4 mb-3"><i class="bi bi-calendar-event text-sapin me-2"></i>Mes Trajets proposés (Chauffeur)</h2>
            <?php if (empty($mes_trajets_proposes)): ?>
                <div class="card space-card p-4 bg-white text-muted mb-4 fst-italic">
                    Vous n'avez créé ou proposé aucun trajet pour le moment.
                </div>
            <?php else: ?>
                <div class="mb-4">
                    <?php foreach ($mes_trajets_proposes as $trajet) : 
                        $badgeClass = 'badge-ouvert'; $statutTexte = 'À venir';
                        if ($trajet['statut'] === 'en cours') { $badgeClass = 'badge-en-cours'; $statutTexte = 'En cours'; }
                        elseif ($trajet['statut'] === 'termine') { $badgeClass = 'badge-termine'; $statutTexte = 'Terminé'; }
                        elseif ($trajet['statut'] === 'annule') { $badgeClass = 'badge-annule'; $statutTexte = 'Annulé'; }
                    ?>
                        <div class="card space-card p-3 p-md-4 bg-white mb-3 border-start border-success border-5">
                            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                <div>
                                    <span class="fs-5 fw-bold text-dark">
                                        <?= htmlspecialchars($trajet['ville_depart']) ?> <i class="bi bi-arrow-right text-muted mx-1"></i> <?= htmlspecialchars($trajet['ville_arrivee']) ?>
                                    </span>
                                </div>
                                <div>
                                    <span class="text-sapin fw-bold fs-5"><?= htmlspecialchars($trajet['prix']) ?> crédits <span class="fs-6 text-muted fw-normal">/ place</span></span>
                                </div>
                            </div>

                            <div class="text-muted my-2 small d-flex flex-wrap align-items-center gap-2">
                                <span><i class="bi bi-clock me-1"></i> Départ : <strong><?= dateEnFrancais($trajet['date_depart']) ?></strong></span>
                                <span>&bull;</span>
                                <span><i class="bi bi-people me-1"></i> Places : <strong><?= $trajet['places_disponibles'] ?></strong></span>
                                <span>&bull;</span>
                                <span>Statut : <span class="statut-badge <?= $badgeClass ?>"><?= $statutTexte ?></span></span>
                                <?php if (isset($trajet['energie']) && in_array(strtolower($trajet['energie']), ['électrique', 'electrique', 'hybride'])) : ?>
                                    <span class="badge bg-success-subtle text-success border border-success"><i class="bi bi-leaf-fill me-1"></i> Éco</span>
                                <?php endif; ?>
                            </div>

                            <div class="d-flex gap-2 mt-2 pt-2 border-top">
                                <?php if ($trajet['statut'] === 'ouvert'): ?>
                                    <a href="scripts/demarrer_trajet.php?id=<?= $trajet['id'] ?>" class="btn btn-sm btn-primary fw-bold rounded-pill px-3">
                                        <i class="bi bi-play-fill me-1"></i> Démarrer
                                    </a>
                                    <a href="scripts/annuler_trajet_chauffeur.php?id=<?= $trajet['id'] ?>" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3" onclick="return confirm('Voulez-vous vraiment annuler ce trajet ?')">
                                        <i class="bi bi-x-circle me-1"></i> Annuler
                                    </a>
                                <?php elseif ($trajet['statut'] === 'en cours'): ?> 
                                    <a href="scripts/terminer_trajet.php?id=<?= $trajet['id'] ?>" class="btn btn-sm btn-success fw-bold rounded-pill px-3">
                                        <i class="bi bi-flag-fill me-1"></i> Arrivée à destination
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Section Réservations (Passager) -->
        <h2 class="h4 fw-bold text-dark mt-4 mb-3"><i class="bi bi-ticket-perforated text-sapin me-2"></i>Mes Réservations (Passager)</h2>
        <?php if (empty($mes_reservations)): ?>
            <div class="card space-card p-4 bg-white text-muted mb-4 fst-italic">
                Vous n'avez pas encore effectué de réservation en tant que passager.
            </div>
        <?php else: ?>
            <div class="mb-4">
                <?php foreach ($mes_reservations as $res) : 
                    $badgeClassPassager = 'badge-ouvert'; $statut_texte = 'À venir';
                    if ($res['statut_trajet'] === 'en cours') { $badgeClassPassager = 'badge-en-cours'; $statut_texte = 'En cours'; }
                    elseif ($res['statut_trajet'] === 'termine') { $badgeClassPassager = 'badge-termine'; $statut_texte = 'Terminé'; }
                    elseif ($res['statut_trajet'] === 'annule') { $badgeClassPassager = 'badge-annule'; $statut_texte = 'Annulé'; }
                ?>
                    <div class="card space-card p-3 p-md-4 bg-white mb-3 border-start border-primary border-5">
                        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                            <div>
                                <span class="fs-5 fw-bold text-dark">
                                    <?= htmlspecialchars($res['ville_depart']) ?> <i class="bi bi-arrow-right text-muted mx-1"></i> <?= htmlspecialchars($res['ville_arrivee']) ?>
                                </span>
                            </div>
                            <div>
                                <span class="text-primary fw-bold fs-5"><?= $res['prix'] + 2 ?> crédits</span>
                            </div>
                        </div>

                        <div class="text-muted my-2 small d-flex flex-wrap align-items-center gap-2">
                            <span><i class="bi bi-clock me-1"></i> Départ : <strong><?= dateEnFrancais($res['date_depart']) ?></strong></span>
                            <span>&bull;</span>
                            <span>Statut : <span class="statut-badge <?= $badgeClassPassager ?>"><?= $statut_texte ?></span></span>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2 mt-2 pt-2 border-top">
                            <?php if ($res['statut_trajet'] === 'termine' && is_null($res['avis_id'])) : ?>
                                <a href="laisser_avis.php?id_trajet=<?= $res['id_trajet'] ?>" class="btn btn-sm btn-warning fw-bold rounded-pill px-3 text-dark">
                                    <i class="bi bi-star-fill me-1"></i> Laisser un avis
                                </a>
                            <?php elseif (!is_null($res['avis_id'])): ?>
                                <span class="text-success fw-bold small"><i class="bi bi-check-circle-fill me-1"></i> Avis envoyé ! Merci pour votre retour.</span>
                            <?php endif; ?>

                            <?php if ($res['statut_trajet'] === 'ouvert') : ?>
                                <a href="scripts/annuler_reservation.php?id_trajet=<?= $res['id_trajet'] ?>" class="btn btn-sm btn-outline-danger fw-bold rounded-pill px-3" onclick="return confirm('Êtes-vous sûr de vouloir annuler ?')">
                                    <i class="bi bi-x-lg me-1"></i> Annuler ma réservation
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="recherche.php" class="text-sapin fw-semibold text-decoration-none">
                <i class="bi bi-search me-1"></i> Rechercher d'autres trajets
            </a>
        </div>

    </main>

    <!-- Footer global -->
    <?php require_once 'includes/footer.php'; ?>

    <!-- Script Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>