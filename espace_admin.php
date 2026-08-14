<?php
session_start();
require_once 'config/db.php';

// 1. SÉCURITÉ : Vérifier que l'utilisateur est connecté ET qu'il est bien Administrateur
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?error=Accès+réservé+à+l\'administrateur.');
    exit;
}

try {
    // GRAPHIC 1 : Nombre de trajets réservés par jour
    $stmt1 = $pdo->query("
        SELECT DATE(t.date_depart) AS date_jour, COUNT(r.id) AS total_reservations
        FROM reservations r
        JOIN trajets t ON r.id_trajet = t.id
        GROUP BY DATE(t.date_depart)
        ORDER BY date_jour ASC
        LIMIT 30
    ");
    $data_reservations = $stmt1->fetchAll(PDO::FETCH_ASSOC);

    // GRAPHIC 2 : Crédits gagnés par la plateforme (2 crédits par réservation)
    $stmt2 = $pdo->query("
        SELECT DATE(t.date_depart) AS date_jour, (COUNT(r.id) * 2) AS gains_plateforme
        FROM reservations r
        JOIN trajets t ON r.id_trajet = t.id
        GROUP BY DATE(t.date_depart)
        ORDER BY date_jour ASC
        LIMIT 30
    ");
    $data_gains = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $labels_jours = [];
    $nb_trajets = [];
    $credits_gains = [];

    foreach ($data_reservations as $row) {
        $labels_jours[] = $row['date_jour'];
        $nb_trajets[] = (int)$row['total_reservations'];
    }

    foreach ($data_gains as $row) {
        $credits_gains[] = (int)$row['gains_plateforme'];
    }

    // US 13 : Calcul du nombre TOTAL historique de crédits gagnés par la plateforme
    $stmtTotalGains = $pdo->query("SELECT COUNT(id) * 2 AS total_historique FROM reservations");
    $total_gains_plateforme = $stmtTotalGains->fetchColumn();

    // US 13 : Récupération de la liste des comptes (Utilisateurs et Employés) pour gestion/suspension
    $stmtComptes = $pdo->prepare("SELECT id, pseudo, email, role, statut_compte FROM utilisateur WHERE role != 'admin' ORDER BY role DESC, pseudo ASC");
    $stmtComptes->execute();
    $liste_comptes = $stmtComptes->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur lors de la récupération des données : " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Espace Administrateur</title>
    <!-- Google Fonts conforme Charte: Montserrat (Titres) & Open Sans (Texte) -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --ecoride-vert-sapin: #1F8653;
            --ecoride-vert-eco: #28A74A;
            --ecoride-gris-fond: #F4F7F6;
            --ecoride-gris-ardoise: #2C3E50;
            --ecoride-card-bg: #FFFFFF;
        }

        body { 
            background-color: var(--ecoride-gris-fond); 
            font-family: 'Open Sans', sans-serif;
            color: var(--ecoride-gris-ardoise);
        }

        h1, h2, h3, h4, h5, h6, .navbar-brand {
            font-family: 'Montserrat', sans-serif;
        }

        /* Styles spécifiques aux cartes d'administration */
        .admin-card { 
            border: none; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
            border-radius: 16px; 
            background: var(--ecoride-card-bg);
        }

        .chart-box { 
            background: var(--ecoride-card-bg); 
            padding: 24px; 
            border-radius: 16px; 
            box-shadow: 0 4px 20px rgba(0,0,0,0.04); 
        }

        .counter-box { 
            background-color: var(--ecoride-vert-sapin); 
            color: white; 
            padding: 16px 28px; 
            border-radius: 16px; 
            box-shadow: 0 6px 15px rgba(31, 134, 83, 0.25); 
        }

        .btn-ecoride-primary {
            background-color: var(--ecoride-vert-sapin);
            color: white;
            border: none;
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-ecoride-primary:hover {
            background-color: #16633d;
            color: white;
        }

        .btn-outline-danger-custom {
            border: 1px solid #E74C3C;
            color: #E74C3C;
            border-radius: 8px;
            font-weight: 600;
        }

        .btn-outline-danger-custom:hover {
            background-color: #E74C3C;
            color: white;
        }

        .btn-success-custom {
            background-color: var(--ecoride-vert-eco);
            color: white;
            border-radius: 8px;
            font-weight: 600;
            border: none;
        }

        .btn-success-custom:hover {
            background-color: #218838;
            color: white;
        }

        .badge-role-employe {
            background-color: #E0F2FE;
            color: #0369A1;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 10px;
        }

        .badge-role-user {
            background-color: #F1F5F9;
            color: #475569;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 10px;
        }

        .badge-status-actif {
            background-color: #DCFCE7;
            color: #15803D;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 10px;
        }

        .badge-status-suspendu {
            background-color: #FEE2E2;
            color: #B91C1C;
            font-weight: 600;
            border-radius: 6px;
            padding: 6px 10px;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- Navbar globale -->
    <?php include 'includes/navbar.php'; ?>

    <div class="container my-5 flex-grow-1">
        <!-- Header Page -->
        <div class="d-flex justify-content-between align-items-center flex-wrap mb-4">
            <div>
                <h1 class="fw-bold h2 mb-1" style="color: var(--ecoride-vert-sapin);">Bonjour <?= htmlspecialchars($_SESSION['pseudo']); ?> 👋 | Espace Administrateur</h1>
                <p class="text-muted mb-0">Gestion globale de la plateforme EcoRide et analyses financières.</p>
            </div>
            <div class="counter-box text-center mt-3 mt-md-0">
                <span class="text-uppercase small fw-semibold opacity-75">Total Crédits Plateforme</span>
                <h2 class="fw-bold mb-0 mt-1"><?= number_format($total_gains_plateforme, 0, ',', ' '); ?> 🪙</h2>
            </div>
        </div>
        
        <!-- Messages d'alerte -->
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show fw-semibold rounded-4 mb-4" role="alert">
                ✅ <?= htmlspecialchars($_GET['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show fw-semibold rounded-4 mb-4" role="alert">
                ⚠️ <?= htmlspecialchars($_GET['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Graphiques -->
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="chart-box">
                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">📈 Nombre de réservations par jour</h3>
                    <canvas id="chartReservations"></canvas>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="chart-box">
                    <h3 class="h6 fw-bold text-uppercase text-muted mb-3">💰 Crédits collectés par la plateforme</h3>
                    <canvas id="chartGains"></canvas>
                </div>
            </div>
        </div>

        <!-- Formulaire + Liste Utilisateurs -->
        <div class="row g-4">
            <!-- Création Employé -->
            <div class="col-md-5">
                <div class="card admin-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-2" style="color: var(--ecoride-vert-sapin);">👤 Créer un compte Employé</h2>
                    <p class="text-muted small mb-4">L'employé pourra modérer les avis et visualiser l'historique NoSQL des trajets signalés.</p>
                    
                    <form action="scripts/traitement_creer_employe.php" method="POST">
                        <div class="mb-3">
                            <label for="pseudo" class="form-label fw-semibold small">Pseudo :</label>
                            <input type="text" class="form-control" id="pseudo" name="pseudo" required placeholder="Ex: Lucas_Modero">
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold small">Adresse e-mail :</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Ex: lucas@ecoride.fr">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold small">Mot de passe provisoire :</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 8 caractères">
                        </div>
                        
                        <button type="submit" class="btn btn-ecoride-primary w-100 mt-2">Créer le compte Employé</button>
                    </form>
                </div>
            </div>

            <!-- Management comptes -->
            <div class="col-md-7">
                <div class="card admin-card p-4 h-100">
                    <h2 class="h5 fw-bold mb-2" style="color: var(--ecoride-vert-sapin);">🛡️ Gestion & Suspension des Comptes</h2>
                    <p class="text-muted small mb-3">Bloquez temporairement ou réactivez les accès des utilisateurs et employés.</p>
                    
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle">
                            <thead>
                                <tr class="border-bottom text-muted small text-uppercase">
                                    <th>Utilisateur</th>
                                    <th>Rôle</th>
                                    <th>Statut</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($liste_comptes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-muted text-center py-4">Aucun compte enregistré pour le moment.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($liste_comptes as $compte): ?>
                                        <tr class="border-bottom">
                                            <td class="py-3">
                                                <span class="fw-semibold text-dark"><?= htmlspecialchars($compte['pseudo']); ?></span><br>
                                                <small class="text-muted"><?= htmlspecialchars($compte['email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="<?= $compte['role'] === 'employe' ? 'badge-role-employe' : 'badge-role-user'; ?>">
                                                    <?= ucfirst(htmlspecialchars($compte['role'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if(($compte['statut_compte'] ?? 'actif') === 'actif'): ?>
                                                    <span class="badge-status-actif">Actif</span>
                                                <?php else: ?>
                                                    <span class="badge-status-suspendu">Suspendu</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if(($compte['statut_compte'] ?? 'actif') === 'actif'): ?>
                                                    <a href="scripts/suspendre_compte.php?action=suspendre&id=<?= $compte['id']; ?>" 
                                                       class="btn btn-outline-danger-custom btn-sm"
                                                       onclick="return confirm('Suspendre le compte de <?= htmlspecialchars($compte['pseudo']); ?> ?')">
                                                        Suspendre
                                                    </a>
                                                <?php else: ?>
                                                    <a href="scripts/suspendre_compte.php?action=activer&id=<?= $compte['id']; ?>" 
                                                       class="btn btn-success-custom btn-sm">
                                                        Réactiver
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer global -->
    <?php include 'includes/footer.php'; ?>

    <!-- Script Chart.js -->
    <script>
        const labelsJours = <?php echo json_encode($labels_jours); ?>;
        const donneesTrajets = <?php echo json_encode($nb_trajets); ?>;
        const donneesGains = <?php echo json_encode($credits_gains); ?>;

        const ctxRes = document.getElementById('chartReservations').getContext('2d');
        new Chart(ctxRes, {
            type: 'line',
            data: {
                labels: labelsJours,
                datasets: [{
                    label: 'Réservations',
                    data: donneesTrajets,
                    borderColor: '#1F8653',
                    backgroundColor: 'rgba(31, 134, 83, 0.1)',
                    fill: true,
                    borderWidth: 2.5,
                    tension: 0.35,
                    pointBackgroundColor: '#1F8653'
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        const ctxGains = document.getElementById('chartGains').getContext('2d');
        new Chart(ctxGains, {
            type: 'bar',
            data: {
                labels: labelsJours,
                datasets: [{
                    label: 'Crédits générés',
                    data: donneesGains,
                    backgroundColor: '#28A74A',
                    borderRadius: 6
                }]
            },
            options: {
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>