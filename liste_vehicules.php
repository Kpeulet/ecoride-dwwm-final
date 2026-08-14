<?php
session_start();
require_once 'config/db.php';

// 1. SÉCURITÉ : Vérifier si l'utilisateur est bien connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 2. FILTRAGE : On récupère uniquement les véhicules de l'utilisateur connecté
$stmt = $pdo->prepare("SELECT * FROM vehicule WHERE id_utilisateur = :id_u");
$stmt->execute(['id_u' => $user_id]);
$vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes véhicules - EcoRide</title>

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
        .main-card { 
            border: none; 
            box-shadow: 0 8px 24px rgba(0,0,0,0.06); 
            border-radius: 16px; 
        }
        .badge-energie {
            font-size: 0.8rem;
            padding: 0.4em 0.8em;
            border-radius: 50rem;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php require_once 'includes/navbar.php'; ?>

    <!-- Contenu Principal -->
    <main class="container py-5 flex-grow-1">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card main-card p-4 p-md-5 bg-white">
                    
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                        <div class="d-flex align-items-center gap-3">
                            <div class="fs-1 text-sapin"><i class="bi bi-car-front"></i></div>
                            <div>
                                <h1 class="h3 fw-bold text-dark mb-0">Mes véhicules</h1>
                                <p class="text-muted small mb-0">Gérez votre garage et vos véhicules enregistrés.</p>
                            </div>
                        </div>
                        <div>
                            <a href="ajouter_vehicule.php" class="btn btn-sapin rounded-pill px-4 fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> Ajouter un véhicule
                            </a>
                        </div>
                    </div>

                    <?php if (count($vehicules) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="py-3">Véhicule</th>
                                        <th class="py-3">Couleur</th>
                                        <th class="py-3">Énergie</th>
                                        <th class="py-3">Immatriculation</th>
                                        <th class="py-3 text-center">Places</th>
                                        <th class="py-3">Préférences</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehicules as $v): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($v['marque']) ?> <?= htmlspecialchars($v['modele']) ?></div>
                                        </td>
                                        <td><?= htmlspecialchars($v['couleur']) ?></td>
                                        <td>
                                            <?php 
                                                $energie = strtolower($v['energie'] ?? '');
                                                if ($energie === 'electrique') {
                                                    echo '<span class="badge bg-warning text-dark badge-energie"><i class="bi bi-lightning-charge-fill me-1"></i> Électrique</span>';
                                                } elseif ($energie === 'hybride') {
                                                    echo '<span class="badge bg-info text-dark badge-energie"><i class="bi bi-arrow-repeat me-1"></i> Hybride</span>';
                                                } else {
                                                    echo '<span class="badge bg-secondary badge-energie"><i class="bi bi-fuel-pump-fill me-1"></i> Thermique</span>';
                                                }
                                            ?>
                                        </td>
                                        <td><code class="px-2 py-1 bg-light border rounded text-dark fw-bold"><?= htmlspecialchars($v['immatriculation']) ?></code></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-semibold">
                                                <i class="bi bi-person-fill text-muted me-1"></i><?= htmlspecialchars($v['nb_places'] ?? $v['places'] ?? '-') ?>
                                            </span>
                                        </td>
                                        <td class="small">
                                            <div class="d-flex flex-column gap-1">
                                                <span>
                                                    <?= !empty($v['fumeur']) || !empty($v['accepte_fumeurs']) 
                                                        ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Fumeur</span>' 
                                                        : '<span class="text-muted"><i class="bi bi-x-circle-fill me-1"></i>Non-fumeur</span>' ?>
                                                </span>
                                                <span>
                                                    <?= !empty($v['animaux']) || !empty($v['accepte_animaux']) 
                                                        ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Animaux</span>' 
                                                        : '<span class="text-muted"><i class="bi bi-x-circle-fill me-1"></i>Pas d\'animaux</span>' ?>
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5 bg-light rounded-4 border">
                            <i class="bi bi-car-front text-muted display-4 mb-3 d-block"></i>
                            <h5 class="fw-bold text-dark mb-2">Aucun véhicule enregistré</h5>
                            <p class="text-muted small mb-4">Vous n'avez pas encore ajouté de véhicule à votre profil.</p>
                            <a href="ajouter_vehicule.php" class="btn btn-sapin rounded-pill px-4 fw-bold">
                                <i class="bi bi-plus-lg me-1"></i> Ajouter mon premier véhicule
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4 pt-3 border-top">
                        <a href="mon_espace.php" class="text-sapin text-decoration-none fw-semibold small">
                            <i class="bi bi-arrow-left me-1"></i> Retour à mon espace
                        </a>
                    </div>

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