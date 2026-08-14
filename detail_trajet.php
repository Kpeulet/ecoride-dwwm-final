<?php
session_start(); 
require_once 'config/db.php';

// ==================== MEMORISATION DE L'URL ====================
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
}
// ==============================================================================

// 1. Récupération de l'ID du trajet
$id = $_GET['id'] ?? null;
if (!$id) { 
    header('Location: index.php'); 
    exit; 
}

// 2. Requête SQL complète (Trajet + Chauffeur + Véhicule)
$sql = "SELECT t.*, u.id AS id_du_chauffeur, u.pseudo, u.note, u.preferences_libres, u.photo, 
               v.marque, v.modele, v.energie, v.fumeur, v.animaux 
        FROM trajets t
        JOIN utilisateur u ON t.id_chauffeur = u.id
        JOIN vehicule v ON t.id_vehicule = v.id
        WHERE t.id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t) { 
    die("<div class='container my-5 alert alert-danger'>Trajet introuvable.</div>"); 
}

// 2b. Récupération des avis VALIDÉS du chauffeur
$stmtAvis = $pdo->prepare("
    SELECT a.commentaire, a.note, u.pseudo 
    FROM avis a
    JOIN utilisateur u ON a.id_expediteur = u.id
    WHERE a.id_trajet IN (SELECT id FROM trajets WHERE id_chauffeur = ?)
    AND a.statut = 'valide'
    ORDER BY a.id DESC
");
$stmtAvis->execute([$t['id_du_chauffeur']]);
$listeAvis = $stmtAvis->fetchAll();

// 2c. Calcul dynamique de la note moyenne basée sur les avis validés
$note_moyenne = null;
if (!empty($listeAvis)) {
    $somme = array_sum(array_column($listeAvis, 'note'));
    $note_moyenne = $somme / count($listeAvis);
}

// 3. Calculs des dates & tarifs
$start = new DateTime($t['date_depart']);
$end = new DateTime($t['date_arrivee']);
$duree = $start->diff($end);

$prix_total = intval($t['prix']) + 2;

$energie_clean = mb_strtolower(trim($t['energie']), 'UTF-8');
$est_ecologique = ($energie_clean === 'electrique' || $energie_clean === 'électrique' || $energie_clean === 'hybride');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Détails du trajet</title>

    <!-- Google Fonts (Montserrat & Open Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Bootstrap CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        :root {
            --vert-sapin: #1F8653;
            --vert-eco: #28A74A;
            --bg-light: #F4F7F6;
            --text-dark: #2C3E50;
        }

        body { 
            font-family: 'Open Sans', sans-serif;
            background-color: var(--bg-light); 
            color: var(--text-dark);
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Montserrat', sans-serif;
        }

        .text-sapin { color: var(--vert-sapin) !important; }
        .text-eco { color: var(--vert-eco) !important; }
        .bg-sapin { background-color: var(--vert-sapin) !important; }

        .btn-sapin {
            background-color: var(--vert-sapin);
            color: #ffffff;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            border: none;
            transition: all 0.2s ease-in-out;
        }
        .btn-sapin:hover {
            background-color: #176840;
            color: #ffffff;
        }

        .custom-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            background-color: #ffffff;
        }

        .driver-avatar {
            width: 64px;
            height: 64px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--vert-sapin);
        }

        .badge-eco {
            background-color: #e8f8f5;
            color: var(--vert-sapin);
            font-weight: 600;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            border: 1px solid rgba(31, 134, 83, 0.2);
        }

        .avis-box {
            background-color: #f8f9fa;
            border-left: 4px solid #f1c40f;
            border-radius: 8px;
        }

        .sticky-summary {
            top: 90px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php require_once 'includes/navbar.php'; ?>

    <div class="container my-5">

        <!-- Bouton Retour -->
        <div class="mb-4">
            <a href="javascript:history.back()" class="text-decoration-none text-muted fw-semibold">
                <i class="bi bi-arrow-left me-1"></i> Retour aux résultats
            </a>
        </div>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger rounded-3 shadow-sm mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Impossible de réserver :</strong> <?= htmlspecialchars($_GET['error']) ?>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            
            <!-- COLONNE GAUCHE : Détails du trajet, Chauffeur & Avis -->
            <div class="col-lg-8">
                
                <!-- Carte Itinéraire -->
                <div class="card custom-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                        <div>
                            <h1 class="h3 fw-bold text-dark mb-1">
                                <?= htmlspecialchars($t['ville_depart']) ?> 
                                <i class="bi bi-arrow-right text-sapin mx-2"></i> 
                                <?= htmlspecialchars($t['ville_arrivee']) ?>
                            </h1>
                            <span class="text-muted small">Date du voyage : <?= $start->format('d/m/Y') ?></span>
                        </div>
                        <?php if ($est_ecologique): ?>
                            <span class="badge-eco">
                                <i class="bi bi-leaf-fill me-1"></i> Voyage Éco-responsable
                            </span>
                        <?php endif; ?>
                    </div>

                    <hr class="text-muted opacity-25 my-3">

                    <!-- Horaires et Durée -->
                    <div class="row text-center py-2 bg-light rounded-3 align-items-center g-2">
                        <div class="col-4">
                            <span class="text-muted small d-block">Départ</span>
                            <span class="fs-5 fw-bold text-dark"><?= $start->format('H\hi') ?></span>
                        </div>
                        <div class="col-4 border-start border-end">
                            <span class="text-muted small d-block">Durée estimée</span>
                            <span class="fw-bold text-sapin"><i class="bi bi-clock me-1"></i><?= $duree->format('%h h %i min') ?></span>
                        </div>
                        <div class="col-4">
                            <span class="text-muted small d-block">Arrivée estimée</span>
                            <span class="fs-5 fw-bold text-dark"><?= $end->format('H\hi') ?></span>
                        </div>
                    </div>
                </div>

                <!-- Carte Chauffeur & Préférences -->
                <div class="card custom-card p-4 mb-4">
                    <h2 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-person-badge me-2"></i>Chauffeur & Véhicule</h2>
                    
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success text-white d-flex align-items-center justify-content-center me-3 fw-bold fs-4" style="width: 50px; height: 50px; min-width: 50px;">
                            <?= strtoupper(substr($t['pseudo'] ?? 'C', 0, 1)); ?>
                        </div>
                        <div>
                            <h3 class="h5 fw-bold mb-0 text-dark"><?= htmlspecialchars($t['pseudo']) ?></h3>
                            <div class="text-warning small fw-bold">
                                <?php if (is_null($note_moyenne)): ?>
                                    <span class="badge bg-secondary text-white fw-normal">Pas encore d'avis</span>
                                <?php else: ?>
                                    <i class="bi bi-star-fill"></i> <?= number_format($note_moyenne, 1, ',', ' ') ?> / 5
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Mot du chauffeur -->
                    <div class="p-3 bg-light rounded-3 mb-4 border-start border-4 border-success">
                        <strong class="text-sapin small"><i class="bi bi-chat-quote-fill me-1"></i> Le mot du chauffeur :</strong>
                        <p class="mb-0 mt-1 fst-italic text-secondary small">
                            "<?= htmlspecialchars($t['preferences_libres'] ?? 'Pas de précisions particulières.') ?>"
                        </p>
                    </div>

                    <!-- Détails véhicule -->
                    <div class="row g-3">
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 h-100">
                                <span class="text-muted small d-block mb-1">Véhicule utilisé</span>
                                <strong class="text-dark d-block"><?= htmlspecialchars($t['marque'] . ' ' . $t['modele']) ?></strong>
                                <span class="badge bg-light text-dark border mt-1"><?= htmlspecialchars($t['energie']) ?></span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="p-3 border rounded-3 h-100">
                                <span class="text-muted small d-block mb-2">Règles à bord</span>
                                <div class="d-flex gap-2 flex-wrap">
                                    <span class="badge bg-light text-dark border">
                                        <?= $t['fumeur'] ? '🚬 Fumeur' : '🚭 Non-fumeur' ?>
                                    </span>
                                    <span class="badge bg-light text-dark border">
                                        <?= $t['animaux'] ? '🐾 Animaux OK' : '🚫 Pas d\'animaux' ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Carte Avis Passagers -->
                <div class="card custom-card p-4">
                    <h2 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-chat-left-text me-2"></i>Avis vérifiés des passagers (<?= count($listeAvis) ?>)</h2>
                    
                    <?php if (empty($listeAvis)): ?>
                        <p class="fst-italic text-muted small mb-0">Aucun avis public pour le moment pour ce chauffeur.</p>
                    <?php else: ?>
                        <div class="d-flex flex-column gap-2">
                            <?php foreach ($listeAvis as $av): ?>
                                <div class="avis-box p-3 shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <strong class="small text-dark"><?= htmlspecialchars($av['pseudo']) ?></strong>
                                        <span class="text-warning small"><?= str_repeat('★', $av['note']) ?></span>
                                    </div>
                                    <p class="mb-0 fst-italic small text-secondary">"<?= htmlspecialchars($av['commentaire']) ?>"</p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- COLONNE DROITE : Réservation & Tarification -->
            <div class="col-lg-4">
                <div class="card custom-card p-4 sticky-top sticky-summary">
                    <h2 class="h5 fw-bold text-sapin mb-3"><i class="bi bi-ticket-perforated me-2"></i>Réservation</h2>
                    
                    <!-- Tarification -->
                    <div class="text-center bg-light p-3 rounded-4 mb-3 border border-success-subtle shadow-sm">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1" style="letter-spacing: 0.5px;">Prix total par place</span>
                        <div class="d-flex align-items-baseline justify-content-center gap-1 my-1">
                            <span class="text-sapin" style="font-size: 3rem; font-weight: 800; line-height: 1; letter-spacing: -1.5px;">
                                <?= $prix_total ?>
                            </span>
                            <span class="text-sapin fw-bold fs-4">crédits</span>
                        </div>
                        <small class="text-muted d-block mt-2" style="font-size: 0.75rem;">(dont 2 crédits de frais de service)</small>
                    </div>

                    <!-- Disponibilité -->
                    <div class="d-flex align-items-center justify-content-between mb-4 px-2">
                        <span class="text-secondary fw-semibold"><i class="bi bi-person-fill me-1"></i>Places restantes :</span>
                        <span class="badge bg-success-subtle text-success fs-6 border border-success px-3 rounded-pill">
                            <?= $t['places_disponibles'] ?>
                        </span>
                    </div>

                    <!-- Zone d'action -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['user_id'] != $t['id_chauffeur']): ?>
                            <button type="button" class="btn btn-sapin btn-lg w-100 rounded-3 shadow-sm py-3 fw-bold" data-bs-toggle="modal" data-bs-target="#modalConfirmation">
                                Participer au covoiturage
                            </button>
                        <?php else: ?>
                            <div class="alert alert-secondary text-center rounded-3 mb-0" role="alert">
                                <i class="bi bi-info-circle-fill me-1"></i> Vous êtes le conducteur de ce trajet.
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-center">
                            <p class="small text-muted mb-3">Vous devez être connecté pour effectuer une réservation.</p>
                            <a href="login.php?source=trajet" class="btn btn-sapin w-100 rounded-3 py-2 fw-bold">Se connecter / S'inscrire</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

    </div>

    <!-- Modal de Double Confirmation -->
    <div class="modal fade" id="modalConfirmation" tabindex="-1" aria-labelledby="modalConfirmationLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header bg-sapin text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold" id="modalConfirmationLabel"><i class="bi bi-shield-check me-2"></i>Confirmation de réservation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <p class="mb-3">Vous allez réserver 1 place pour le trajet :<br>
                    <strong class="text-dark fs-5"><?= htmlspecialchars($t['ville_depart']) ?> <i class="bi bi-arrow-right text-sapin"></i> <?= htmlspecialchars($t['ville_arrivee']) ?></strong></p>
                    
                    <div class="alert bg-light border rounded-3 my-3 p-3">
                        <span class="d-block small text-muted">Montant total de la réservation</span>
                        <span class="fs-2 fw-bold text-sapin"><?= $prix_total ?> crédits</span><br>
                        <small class="text-muted">Ce montant sera directement déduit de votre solde EcoRide.</small>
                    </div>

                    <form action="scripts/traitement_reservation.php" method="POST">
                        <input type="hidden" name="id_trajet" value="<?= $t['id'] ?>">
                        
                        <div class="form-check text-start mb-4">
                            <input class="form-check-input" type="checkbox" required id="acceptCheck">
                            <label class="form-check-label small text-muted" for="acceptCheck">
                                J'accepte les conditions de réservation et d'annulation d'EcoRide.
                            </label>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <button type="button" class="btn btn-light rounded-3 px-3" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-sapin rounded-3 px-4 fw-bold" id="submitBtn" disabled>Confirmer et Payer</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php require_once 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const checkbox = document.getElementById('acceptCheck');
        const submitBtn = document.getElementById('submitBtn');

        if (checkbox && submitBtn) {
            checkbox.addEventListener('change', function() {
                submitBtn.disabled = !this.checked;
            });
        }
    </script>
    
</body>
</html>