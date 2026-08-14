<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    // Infos utilisateur
    $stmt = $pdo->prepare("SELECT id, pseudo, email, est_chauffeur, preferences_libres FROM utilisateur WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        session_destroy();
        header('Location: connexion.php');
        exit();
    }

    // Récupération des véhicules
    $stmtVehicules = $pdo->prepare("SELECT * FROM vehicule WHERE id_utilisateur = ?");
    $stmtVehicules->execute([$user_id]);
    $vehicules = $stmtVehicules->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de chargement du profil : " . $e->getMessage());
}

// Analyse des préférences enregistrées pour pré-cocher les radios
$pref_str = $user['preferences_libres'] ?? '';
$is_fumeur = (strpos($pref_str, '• Cigarette : Fumeur accepté') !== false) ? 1 : 0;
$has_animal = (strpos($pref_str, '• Animaux : Animaux acceptés') !== false) ? 1 : 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Mon Profil</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --ecoride-vert-sapin: #1F8653;
            --ecoride-vert-eco: #28A74A;
            --ecoride-gris-fond: #F4F7F6;
            --ecoride-gris-ardoise: #2C3E50;
        }
        body { 
            background-color: var(--ecoride-gris-fond); 
            font-family: 'Open Sans', sans-serif;
            color: var(--ecoride-gris-ardoise);
        }
        h1, h2, h3, .navbar-brand { font-family: 'Montserrat', sans-serif; }
        .profile-card {
            background: #FFFFFF;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
            border: none;
        }
        .btn-ecoride {
            background-color: var(--ecoride-vert-sapin);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 10px 20px;
            border: none;
        }
        .btn-ecoride:hover { background-color: #16633d; color: white; }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'includes/navbar.php'; ?>

    <div class="container my-5 flex-grow-1" style="max-width: 750px;">
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4 fw-semibold mb-4">
                ✅ Vos informations ont été enregistrées avec succès !
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Carte Profil & Préférences -->
        <div class="card profile-card p-4 p-md-5 mb-4">
            <div class="d-flex align-items-center gap-3 mb-4">
                <span class="fs-1">⚙️</span>
                <div>
                    <h1 class="h3 fw-bold mb-0" style="color: var(--ecoride-vert-sapin);">Mon Profil</h1>
                    <p class="text-muted small mb-0">Modifiez vos rôles et vos habitudes de trajet.</p>
                </div>
            </div>

            <form action="scripts/update_profil.php" method="POST">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Pseudo</label>
                        <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($user['pseudo']); ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-muted">Adresse e-mail</label>
                        <input type="email" class="form-control bg-light" value="<?= htmlspecialchars($user['email']); ?>" disabled>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Rôle (US 8) -->
                <div class="mb-4">
                    <label class="form-label fw-bold d-block">🚘 Préférence de rôle</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="est_chauffeur" id="rolePassager" value="0" <?= !$user['est_chauffeur'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="rolePassager">Passager uniquement</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="est_chauffeur" id="roleChauffeur" value="1" <?= $user['est_chauffeur'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="roleChauffeur">Chauffeur & Passager</label>
                    </div>
                </div>

                <!-- Préférences Chauffeur : Radios + Textarea (US 8) -->
                <div class="mb-4 border p-3 rounded-3 bg-light">
                    <label class="form-label fw-bold d-block text-success">💬 Préférences à bord</label>

                    <div class="row g-3 mb-3">
                        <!-- Fumeur / Non-fumeur -->
                        <div class="col-md-6">
                            <span class="small fw-semibold text-muted d-block mb-1">Cigarette :</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="fumeur" id="fumeurNon" value="0" <?= ($is_fumeur === 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="fumeurNon">Non-fumeur</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="fumeur" id="fumeurOui" value="1" <?= ($is_fumeur === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="fumeurOui">Fumeur accepté</label>
                            </div>
                        </div>

                        <!-- Animal / Pas d'animal -->
                        <div class="col-md-6">
                            <span class="small fw-semibold text-muted d-block mb-1">Animaux :</span>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="animal" id="animalNon" value="0" <?= ($has_animal === 0) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="animalNon">Pas d'animaux</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="animal" id="animalOui" value="1" <?= ($has_animal === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label small" for="animalOui">Animaux acceptés</label>
                            </div>
                        </div>
                    </div>

                    <!-- Champ texte multiligne -->
                    <div>
                        <label for="preferences_libres" class="form-label small fw-semibold text-muted">Autres précisions / préférences libres :</label>
                        <?php 
                        // Extraction propre du texte libre pour la zone d'édition
                        $texte_libre = $user['preferences_libres'] ?? '';
                        if (preg_match('/• Autres\s*:\s*(.*)/s', $texte_libre, $matches)) {
                            $texte_libre = trim($matches[1]);
                        } elseif (strpos($texte_libre, '• Cigarette') === 0) {
                            $texte_libre = ''; // Si uniquement composé des choix radio
                        }
                        ?>
                        <textarea class="form-control" id="preferences_libres" name="preferences_libres" rows="3" placeholder="Ex: Musique douce, petits bagages uniquement..."><?= htmlspecialchars($texte_libre); ?></textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <a href="mon_espace.php" class="btn btn-outline-secondary rounded-3">Retour au tableau de bord</a>
                    <button type="submit" name="update_profil" class="btn btn-ecoride">Enregistrer le profil</button>
                </div>
            </form>
        </div>

        <!-- Section Véhicules -->
        <div class="card profile-card p-4 p-md-5">
            <h2 class="h5 fw-bold mb-3" style="color: var(--ecoride-vert-sapin);">🚗 Mes Véhicules</h2>

            <?php if (!empty($vehicules)): ?>
                <div class="table-responsive mb-4">
                    <table class="table align-middle">
                        <thead>
                            <tr class="text-muted small">
                                <th>Marque & Modèle</th>
                                <th>Places</th>
                                <th>Couleur</th>
                                <th>Immatriculation</th>
                                <th>Énergie</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($vehicules as $v): ?>
                                <tr>
                                    <td class="fw-semibold"><?= htmlspecialchars($v['marque'] . ' ' . $v['modele']); ?></td>
                                    <td><?= htmlspecialchars($v['nb_places']); ?></td>
                                    <td><?= htmlspecialchars($v['couleur'] ?? 'Non précisée'); ?></td>
                                    <td><?= htmlspecialchars($v['immatriculation']); ?></td>
                                    <td>
                                        <span class="badge <?= strtolower($v['energie']) === 'electrique' || strtolower($v['energie']) === 'électrique' ? 'bg-success' : 'bg-secondary'; ?>">
                                            <?= htmlspecialchars($v['energie']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-muted small mb-4">Vous n'avez pas encore ajouté de véhicule.</p>
            <?php endif; ?>

            <!-- Formulaire Ajout Véhicule -->
            <h3 class="h6 fw-bold mb-3">Ajouter un nouveau véhicule</h3>
            <form action="scripts/update_profil.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="marque" class="form-control" placeholder="Marque (ex: Peugeot)" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="modele" class="form-control" placeholder="Modèle (ex: 208)" required>
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="nb_places" class="form-control" placeholder="Nombre de places" min="1" max="9" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="couleur" class="form-control" placeholder="Couleur (ex: Noir)">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="immatriculation" class="form-control" placeholder="Immatriculation" required>
                    </div>
                    <div class="col-md-4">
                        <select name="energie" class="form-select" required>
                            <option value="electrique">Électrique</option>
                            <option value="hybride">Hybride</option>
                            <option value="thermique">Thermique</option>
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small text-muted mb-1">Date de 1ère immatriculation</label>
                        <input type="date" name="date_premiere_immat" class="form-control" required>
                    </div>
                </div>
                <button type="submit" name="add_vehicle" class="btn btn-outline-success mt-3 w-100 rounded-3 fw-semibold">Ajouter ce véhicule</button>
            </form>
        </div>

    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>