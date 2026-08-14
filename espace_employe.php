<?php
session_start();

// Protection d'accès : Réservé aux employés
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employe') {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoRide - Espace Modération Employé</title>

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

        .dashboard-header {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }
        .card-custom {
            border: none;
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
            transition: transform 0.2s ease;
        }
        .table-custom {
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .badge-rating {
            background-color: #fef3c7;
            color: #d97706;
            font-weight: 700;
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

    <?php include 'includes/navbar.php'; ?>

    <main class="container my-4 my-md-5">

        <!-- En-tête Espace Employé (Épuré sans le badge doublon) -->
        <div class="dashboard-header p-4 mb-4">
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="bi bi-shield-check text-sapin me-2"></i>Espace Modération & Gestion
            </h1>
            <p class="text-muted small mb-0">Bienvenue, <strong><?= htmlspecialchars($_SESSION['pseudo'] ?? 'Employé') ?></strong>. Vous pouvez ici valider les avis et traiter les signalements de trajet.</p>
        </div>

        <!-- SECTION 1 : AVIS EN ATTENTE (MySQL) -->
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 fw-bold text-dark mb-0">
                    <i class="bi bi-star-fill text-warning me-2"></i>Avis en attente de validation
                </h2>
                <span class="badge bg-light text-dark border"><i class="bi bi-database me-1"></i>MySQL</span>
            </div>

            <div id="container-avis">
                <div class="text-center text-muted py-4 bg-white rounded-3 shadow-sm">
                    <div class="spinner-border text-sapin spinner-border-sm me-2" role="status"></div>
                    Chargement des avis en cours...
                </div>
            </div>
        </section>

        <hr class="my-5 opacity-10">

        <!-- SECTION 2 : SIGNALEMENTS & LITIGES (MongoDB / NoSQL) -->
        <section class="mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="h4 fw-bold text-dark mb-0">
                    <i class="bi bi-exclamation-triangle-fill text-danger me-2"></i>Signalements & Litiges Trajets
                </h2>
                <span id="badge-mongo-status" class="badge bg-secondary">Connexion...</span>
            </div>

            <div class="table-responsive table-custom bg-white">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th scope="col">N°</th>
                            <th scope="col">Détails Voyage (Départ / Arrivée)</th>
                            <th scope="col">Chauffeur</th>
                            <th scope="col">Passager</th>
                            <th scope="col">Raison / Incident</th>
                        </tr>
                    </thead>
                    <tbody id="container-litiges">
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                <div class="spinner-border text-sapin spinner-border-sm me-2" role="status"></div>
                                Chargement des litiges...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    <!-- Inclusion de ton fichier footer.php existant -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            chargerDonneesEmploye();
        });

        function chargerDonneesEmploye() {
            fetch('api/get_donnees_employe.php')
                .then(response => {
                    if (!response.ok) throw new Error('Erreur réseau ou droits insuffisants');
                    return response.json();
                })
                .then(data => {
                    // 1. GESTION ET AFFICHAGE DES AVIS
                    const containerAvis = document.getElementById('container-avis');
                    containerAvis.innerHTML = '';

                    if (!data.avis || data.avis.length === 0) {
                        containerAvis.innerHTML = `
                            <div class="alert alert-light border text-muted p-4 text-center rounded-3 mb-0">
                                <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>Aucun avis en attente de moderation pour le moment.
                            </div>`;
                    } else {
                        data.avis.forEach(avis => {
                            const card = `
                                <div class="card card-custom p-4 mb-3 bg-white" id="card-avis-${avis.id}">
                                    <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                                        <span class="fw-bold text-sapin fs-6">
                                            <i class="bi bi-geo-alt-fill me-1"></i>${escapeHtml(avis.ville_depart)} ➔ ${escapeHtml(avis.ville_arrivee)}
                                        </span>
                                        <span class="badge badge-rating px-3 py-2 rounded-pill fs-6">
                                            ${escapeHtml(avis.note)} / 5 ★
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <p class="text-muted small mb-1">
                                            Auteur : <strong>${escapeHtml(avis.expediteur)}</strong>
                                        </p>
                                        <div class="p-3 bg-light rounded-3 text-dark italic">
                                            "${escapeHtml(avis.commentaire)}"
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button onclick="modererAvis(${avis.id}, 'valider', this)" class="btn btn-success btn-sm fw-bold px-3 rounded-pill">
                                            <i class="bi bi-check-lg me-1"></i> Valider
                                        </button>
                                        <button onclick="modererAvis(${avis.id}, 'refuser', this)" class="btn btn-outline-danger btn-sm fw-bold px-3 rounded-pill">
                                            <i class="bi bi-x-lg me-1"></i> Refuser
                                        </button>
                                    </div>
                                </div>
                            `;
                            containerAvis.innerHTML += card;
                        });
                    }

                    // 2. BADGE SOURCE BASE DE DONNÉES (MongoDB vs Fallback MySQL)
                    const badgeMongo = document.getElementById('badge-mongo-status');
                    if (data.mongo_disponible) {
                        badgeMongo.className = "badge bg-success px-3 py-2 fs-6 rounded-pill";
                        badgeMongo.innerHTML = '<i class="bi bi-hdd-network me-1"></i>Source : MongoDB (NoSQL)';
                    } else {
                        badgeMongo.className = "badge bg-info text-dark px-3 py-2 fs-6 rounded-pill";
                        badgeMongo.innerHTML = '<i class="bi bi-database me-1"></i>Source de secours : MySQL';
                    }

                    // 3. GESTION ET AFFICHAGE DES LITIGES (US 12)
                    const containerLitiges = document.getElementById('container-litiges');
                    containerLitiges.innerHTML = '';

                    if (!data.litiges || data.litiges.length === 0) {
                        containerLitiges.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">Aucun litige ou incident enregistré.</td></tr>`;
                    } else {
                        data.litiges.forEach(litige => {
                            // Formatage complet pour respecter stricto sensu l'US 12
                            const emailChauffeur = litige.email_chauffeur ? `<br><a href="mailto:${escapeHtml(litige.email_chauffeur)}" class="text-decoration-none small text-muted"><i class="bi bi-envelope me-1"></i>${escapeHtml(litige.email_chauffeur)}</a>` : '';
                            const emailPassager = litige.email_passager ? `<br><a href="mailto:${escapeHtml(litige.email_passager)}" class="text-decoration-none small text-muted"><i class="bi bi-envelope me-1"></i>${escapeHtml(litige.email_passager)}</a>` : '';
                            
                            const dateDepart = litige.date_depart ? `<br><small class="text-muted"><i class="bi bi-calendar-check me-1"></i>Départ : ${escapeHtml(litige.date_depart)}</small>` : '';
                            const dateArrivee = litige.date_arrivee ? `<br><small class="text-muted"><i class="bi bi-calendar-x me-1"></i>Arrivée : ${escapeHtml(litige.date_arrivee)}</small>` : '';

                            const ligne = `
                                <tr>
                                    <td class="fw-bold text-sapin">#${escapeHtml(litige.id_trajet.toString())}</td>
                                    <td>
                                        <strong>${escapeHtml(litige.depart)} ➔ ${escapeHtml(litige.arrivee)}</strong>
                                        ${dateDepart}
                                        ${dateArrivee}
                                    </td>
                                    <td>
                                        <strong>${escapeHtml(litige.chauffeur)}</strong>
                                        ${emailChauffeur}
                                    </td>
                                    <td>
                                        <strong>${escapeHtml(litige.passager)}</strong>
                                        ${emailPassager}
                                    </td>
                                    <td>
                                        <span class="badge bg-danger-subtle text-danger mb-1"><i class="bi bi-exclamation-circle me-1"></i>Incident</span><br>
                                        <small class="text-dark fw-medium">"${escapeHtml(litige.commentaire)}"</small>
                                    </td>
                                </tr>
                            `;
                            containerLitiges.innerHTML += ligne;
                        });
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    document.getElementById('container-avis').innerHTML = '<div class="alert alert-danger mb-0">Erreur lors du chargement des données.</div>';
                });
        }

        // Modération dynamique des avis via AJAX
        function modererAvis(id, action, bouton) {
            if (action === 'refuser' && !confirm('Refuser et supprimer définitivement cet avis ?')) {
                return;
            }

            const parentDiv = bouton.parentElement;
            const boutons = parentDiv.querySelectorAll('button');
            boutons.forEach(b => b.disabled = true);

            fetch(`scripts/moderer_avis.php?action=${action}&id=${id}`)
                .then(response => {
                    if (!response.ok) throw new Error('Erreur de traitement API');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        const cibleCard = document.getElementById(`card-avis-${id}`);
                        if (cibleCard) {
                            cibleCard.style.transition = "all 0.3s ease";
                            cibleCard.style.opacity = "0";
                            cibleCard.style.transform = "translateY(-10px)";
                            
                            setTimeout(() => {
                                cibleCard.remove();
                                const cartesRestantes = document.querySelectorAll('#container-avis .card-custom');
                                if (cartesRestantes.length === 0) {
                                    document.getElementById('container-avis').innerHTML = `
                                        <div class="alert alert-light border text-muted p-4 text-center rounded-3 mb-0">
                                            <i class="bi bi-check-circle-fill text-success me-2 fs-5"></i>Aucun avis en attente de moderation pour le moment.
                                        </div>`;
                                }
                            }, 300);
                        }
                    } else {
                        alert('Erreur : ' + (data.error || 'Impossible de modérer l\'avis.'));
                        boutons.forEach(b => b.disabled = false);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur réseau ou serveur est survenue.');
                    boutons.forEach(b => b.disabled = false);
                });
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
    </script>
</body>
</html>