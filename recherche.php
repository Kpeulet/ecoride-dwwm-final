<?php
session_start();
require_once 'config/db.php';

// 1. Récupération des critères de base
$depart = trim($_GET['depart'] ?? '');
$arrivee = trim($_GET['arrivee'] ?? '');
$date_voyage = $_GET['date'] ?? ''; 

// 2. Récupération des filtres avancés (US 4)
$duree_max = !empty($_GET['duree_max']) ? floatval($_GET['duree_max']) : null;
$ecologique = isset($_GET['ecologique']) ? true : false;
$prix_max = !empty($_GET['prix_max']) ? floatval($_GET['prix_max']) : null;
$note_min = !empty($_GET['note_min']) ? intval($_GET['note_min']) : null;

$recherche_lancee = !empty($depart) && !empty($arrivee) && !empty($date_voyage);

$trajets = [];
$prochaine_date = null;

if ($recherche_lancee) {
    // 3. Construction de la requête SQL principale
    $sql = "SELECT DISTINCT t.id, t.*, u.pseudo, u.photo, v.energie, v.marque, v.modele,
                   (TIMESTAMPDIFF(MINUTE, t.date_depart, t.date_arrivee) / 60) AS duree_heures,
                   (SELECT AVG(a.note) 
                    FROM avis a 
                    INNER JOIN trajets t2 ON a.id_trajet = t2.id 
                    WHERE t2.id_chauffeur = u.id 
                    AND a.statut = 'valide') as note_dynamique
            FROM trajets t 
            JOIN utilisateur u ON t.id_chauffeur = u.id 
            JOIN vehicule v ON t.id_vehicule = v.id 
            WHERE t.ville_depart LIKE ? 
            AND t.ville_arrivee LIKE ? 
            AND DATE(t.date_depart) = ? 
            AND t.places_disponibles > 0 
            AND t.statut = 'ouvert'";

    $params = ["%$depart%", "%$arrivee%", $date_voyage];

    // Filtre Écologique
    if ($ecologique) {
        $sql .= " AND (LOWER(v.energie) LIKE '%electrique%' OR LOWER(v.energie) LIKE '%électrique%' OR LOWER(v.energie) LIKE '%hybride%')";
    }

    // Filtre Prix max (prix affiché = t.prix + 2 crédits de commission)
    if ($prix_max !== null) {
        $sql .= " AND (t.prix + 2) <= ?";
        $params[] = $prix_max;
    }

    // Filtre Durée max
    if ($duree_max !== null) {
        $sql .= " AND (TIMESTAMPDIFF(MINUTE, t.date_depart, t.date_arrivee) / 60) <= ?";
        $params[] = $duree_max;
    }

    $sql .= " GROUP BY t.id, u.id, v.id";

    // Filtre Note minimale du chauffeur
    if ($note_min !== null) {
        $sql .= " HAVING (note_dynamique >= ? OR note_dynamique IS NULL)";
        $params[] = $note_min;
    }

    $sql .= " ORDER BY t.date_depart ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $trajets = $stmt->fetchAll();

    // Logique de suggestion si aucun résultat (US 3)
    if (count($trajets) === 0) {
        $stmt_prev = $pdo->prepare("SELECT DATE(date_depart) 
                                    FROM trajets 
                                    WHERE ville_depart LIKE ? 
                                    AND ville_arrivee LIKE ? 
                                    AND date_depart > ? 
                                    AND statut = 'ouvert' 
                                    AND places_disponibles > 0 
                                    ORDER BY date_depart ASC LIMIT 1");
        $stmt_prev->execute(["%$depart%", "%$arrivee%", $date_voyage]);
        $prochaine_date = $stmt_prev->fetchColumn();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Trouver un trajet</title>

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
        .btn-outline-sapin {
            border: 2px solid #1F8653;
            color: #1F8653;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            transition: all 0.2s ease;
        }
        .btn-outline-sapin:hover {
            background-color: #1F8653;
            color: #ffffff;
        }
        .search-header-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        }
        .filter-card { 
            border: none; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.03); 
            border-radius: 16px;
        }
        .trajet-card { 
            border: none; 
            box-shadow: 0 4px 15px rgba(0,0,0,0.05); 
            border-radius: 16px;
            transition: transform 0.2s ease, box-shadow 0.2s ease; 
        }
        .trajet-card:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
        }
        .eco-badge { 
            background-color: #e8f8f5; 
            color: #1F8653; 
            font-weight: 700; 
            padding: 6px 14px; 
            border-radius: 20px; 
            display: inline-block; 
            font-size: 0.85rem;
        }
        .non-eco-badge { 
            background-color: #fef3c7; 
            color: #d97706; 
            font-weight: 700; 
            padding: 6px 14px; 
            border-radius: 20px; 
            display: inline-block; 
            font-size: 0.85rem;
        }
        .rating-stars { 
            color: #f1c40f; 
            font-weight: 700; 
        }
        .driver-avatar { 
            width: 56px; 
            height: 56px; 
            object-fit: cover; 
            border-radius: 50%; 
            border: 2px solid #1F8653; 
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php require_once 'includes/navbar.php'; ?>

    <main class="container my-4 my-md-5">

        <!-- Barre de recherche rapide dynamique -->
        <div class="card search-header-card p-4 bg-white mb-4">
            <form method="GET" action="recherche.php" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small"><i class="bi bi-geo-alt-fill text-sapin me-1"></i> Départ</label>
                    <input type="text" name="depart" class="form-control bg-light border-0 py-2" placeholder="Ex: Paris" value="<?= htmlspecialchars($depart) ?>" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold text-muted small"><i class="bi bi-pin-map-fill text-sapin me-1"></i> Arrivée</label>
                    <input type="text" name="arrivee" class="form-control bg-light border-0 py-2" placeholder="Ex: Lyon" value="<?= htmlspecialchars($arrivee) ?>" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold text-muted small"><i class="bi bi-calendar-event text-sapin me-1"></i> Date</label>
                    <input type="date" name="date" class="form-control bg-light border-0 py-2" value="<?= htmlspecialchars($date_voyage) ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sapin w-100 py-2 rounded-3">
                        <i class="bi bi-search me-1"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>

        <?php if (!$recherche_lancee): ?>
            <!-- État initial : Pas de paramètres saisis -->
            <div class="card border-0 shadow-sm text-center p-5 mx-auto rounded-4 bg-white" style="max-width: 650px;">
                <div class="display-1 text-sapin mb-3"><i class="bi bi-compass"></i></div>
                <h1 class="h3 fw-bold mb-2 text-dark">Où souhaitez-vous aller ?</h1>
                <p class="text-muted mb-0">Indiquez vos villes de départ et d'arrivée ainsi que votre date de voyage ci-dessus pour consulter les trajets disponibles.</p>
            </div>

        <?php else: ?>
            <!-- Résultats de la recherche -->
            <div class="row">
                
                <!-- Titre & Info -->
                <div class="col-12 mb-3">
                    <h1 class="h3 fw-bold text-dark">Trajets de <span class="text-sapin"><?= htmlspecialchars($depart) ?></span> à <span class="text-sapin"><?= htmlspecialchars($arrivee) ?></span></h1>
                    <p class="text-muted small mb-0"><i class="bi bi-calendar-check me-1"></i> Voyage le <?= date('d/m/Y', strtotime($date_voyage)) ?></p>
                </div>

                <!-- Barre de filtres avancés (US 4) -->
                <div class="col-12 mb-4">
                    <div class="card filter-card p-3 p-md-4 bg-white">
                        <form method="GET" action="recherche.php" class="row g-3 align-items-center">
                            <input type="hidden" name="depart" value="<?= htmlspecialchars($depart) ?>">
                            <input type="hidden" name="arrivee" value="<?= htmlspecialchars($arrivee) ?>">
                            <input type="hidden" name="date" value="<?= htmlspecialchars($date_voyage) ?>">
                            
                            <div class="col-lg-3 col-md-6">
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" name="ecologique" id="ecoCheck" <?= $ecologique ? 'checked' : '' ?>>
                                    <label class="form-check-label fw-bold text-sapin small" for="ecoCheck">🌿 100% Écologique (Électrique/Hybride)</label>
                                </div>
                            </div>
                            <div class="col-lg-2 col-md-3 col-6">
                                <input type="number" step="0.01" name="prix_max" class="form-control bg-light border-0 form-control-sm py-2" placeholder="Prix max (€)" value="<?= htmlspecialchars($prix_max ?? '') ?>">
                            </div>
                            <div class="col-lg-2 col-md-3 col-6">
                                <input type="number" step="0.5" name="duree_max" class="form-control bg-light border-0 form-control-sm py-2" placeholder="Durée max (h)" value="<?= htmlspecialchars($duree_max ?? '') ?>">
                            </div>
                            <div class="col-lg-2 col-md-6 col-6">
                                <input type="number" name="note_min" class="form-control bg-light border-0 form-control-sm py-2" placeholder="Note min (1-5)" min="1" max="5" value="<?= htmlspecialchars($note_min ?? '') ?>">
                            </div>
                            <div class="col-lg-3 col-md-6 col-6">
                                <button type="submit" class="btn btn-outline-sapin w-100 btn-sm py-2 rounded-3">
                                    <i class="bi bi-funnel me-1"></i> Appliquer les filtres
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Liste des trajets -->
                <div class="col-12">
                    <?php if (!empty($trajets)): ?>
                        <?php foreach ($trajets as $t): ?>
                            <div class="card trajet-card p-4 mb-3 bg-white">
                                <div class="row align-items-center">
                                    
                                    <!-- Conducteur & Détails véhicule -->
                                    <div class="col-md-7 mb-3 mb-md-0">
                                        <div class="d-flex align-items-center mb-3">
                                            <?php if (!empty($t['photo']) && file_exists($t['photo'])): ?>
                                                <img src="<?= htmlspecialchars($t['photo']) ?>" alt="<?= htmlspecialchars($t['pseudo']) ?>" class="driver-avatar me-3">
                                            <?php else: ?>
                                                <div class="driver-avatar me-3 d-flex align-items-center justify-content-center bg-light text-sapin fs-4 fw-bold">
                                                    <?= strtoupper(substr($t['pseudo'], 0, 1)) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <h5 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['pseudo']) ?></h5>
                                                <div>
                                                    <?php if (!empty($t['note_dynamique'])): ?>
                                                        <span class="rating-stars small"><i class="bi bi-star-fill"></i> <?= number_format($t['note_dynamique'], 1) ?> / 5</span>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><i class="bi bi-person-fill me-1"></i>Nouveau chauffeur</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <p class="text-secondary small mb-2">
                                            <i class="bi bi-car-front-fill text-sapin me-1"></i> <strong><?= htmlspecialchars($t['marque'] . " " . $t['modele']) ?></strong> <span class="text-muted">(<?= htmlspecialchars($t['energie']) ?>)</span>
                                        </p>
                                        <div class="small text-dark">
                                            <span class="me-3"><i class="bi bi-clock-history text-sapin me-1"></i> <strong>Départ :</strong> <?= date('H\hi', strtotime($t['date_depart'])) ?></span>
                                            <span><i class="bi bi-flag-fill text-danger me-1"></i> <strong>Arrivée :</strong> <?= date('H\hi', strtotime($t['date_arrivee'])) ?></span>
                                        </div>
                                    </div>

                                    <!-- Statut Éco, Prix et Action -->
                                    <div class="col-md-5 text-md-end text-start border-top border-md-0 pt-3 pt-md-0">
                                        <?php 
                                            $energie_clean = mb_strtolower(trim($t['energie']), 'UTF-8'); 
                                            if (in_array($energie_clean, ['electrique', 'électrique', 'hybride'])): 
                                        ?>
                                            <div class="mb-2"><span class="eco-badge"><i class="bi bi-leaf-fill me-1"></i>Voyage Éco-responsable</span></div>
                                        <?php else: ?>
                                            <div class="mb-2"><span class="non-eco-badge"><i class="bi bi-fuel-pump-fill me-1"></i>Voyage Thermique</span></div>
                                        <?php endif; ?>

                                        <div class="fs-3 fw-bold text-sapin mb-0"><?= number_format($t['prix'] + 2, 2) ?> <small class="fs-6">crédits</small></div>
                                        <p class="text-muted small mb-3"><i class="bi bi-person-check-fill me-1"></i><?= htmlspecialchars($t['places_disponibles']) ?> place<?= $t['places_disponibles'] > 1 ? 's' : '' ?> libre<?= $t['places_disponibles'] > 1 ? 's' : '' ?></p>
                                        
                                        <a href="detail_trajet.php?id=<?= $t['id'] ?>" class="btn btn-sapin rounded-pill px-4 py-2 small">
                                            Détail du trajet <i class="bi bi-arrow-right ms-1"></i>
                                        </a>
                                    </div>

                                </div>
                            </div>
                        <?php endforeach; ?>

                    <?php else: ?>
                        <!-- Aucun résultat trouvé -->
                        <div class="card border-0 shadow-sm p-5 text-center bg-white rounded-4">
                            <p class="fs-5 text-muted mb-3">Désolé, aucun trajet ne correspond à vos critères pour cette date.</p>
                            <?php if($prochaine_date): ?>
                                <div class="alert alert-success d-inline-block mx-auto mb-0 rounded-3 p-3" role="alert">
                                    💡 <strong>Alternative trouvée :</strong> Un trajet est disponible le <strong><?= date('d/m/Y', strtotime($prochaine_date)) ?></strong>. 
                                    <a href="recherche.php?depart=<?=urlencode($depart)?>&arrivee=<?=urlencode($arrivee)?>&date=<?=$prochaine_date?>" class="alert-link text-decoration-none ms-1 fw-bold">Voir cette date ➔</a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </main>

    <!-- Footer global -->
    <footer class="mt-auto">
        <?php require_once 'includes/footer.php'; ?>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>