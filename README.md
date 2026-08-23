# 🍃 EcoRide - Plateforme de Covoiturage Écoresponsable

Projet réalisé dans le cadre du Titre Professionnel Développeur Web et Web Mobile (DWWM).

---

## Application en Ligne (Production)

* **URL de l'application :** [https://ecoride-dwwm-final.onrender.com](https://ecoride-dwwm-final.onrender.com)
* **Hébergement Web :** Render (Web Service PHP)
* **Base de données SQL :** AlwaysData (MySQL)
* **Base de données NoSQL :** MongoDB Atlas (Gestion des litiges et avis)

---

## Identifiants de démonstration

| Rôle | Email | Mot de passe |
| :--- | :--- | :--- |
| **Administrateur** | `admin@ecoride.fr` | `admin2026!` |
| **Employé** | `employe@ecoride.fr` | `Employe2026!` |
| **Chauffeur** | `ecoride-chauffeur@yopmail.com` | `Chauffeur123!` |
| **Passager** | `testuser@ecoride.fr` | `testUser123!` |

---

## Stack Technique

* **Backend :** PHP 8.x (Architecture MVC / Modulaire)
* **Bases de données :** MySQL (Relationnel), MongoDB (NoSQL)
* **Frontend :** HTML5, CSS3, JavaScript (ES6+), Bootstrap
* **Déploiement & CI/CD :** Git, GitHub, Render, AlwaysData

---

## Structure du Projet

```text
projet_ecoride/
├── config/             # Configuration des accès BDD (SQL & MongoDB)
├── database/           # Scripts SQL d'initialisation (db_ecoride.sql)
├── docs/               # Livrables de certification et documentation
├── public/             # Assets statiques (CSS, JS, images)
├── scripts/            # Traitements PHP (connexion, réservation, etc.)
├── index.php           # Page d'accueil / Point d'entrée
└── README.md           # Documentation du dépôt
```
## Installation en Local

### Prérequis
- Serveur Web local (MAMP, WAMP, XAMPP, Laragon) avec PHP 8.x et MySQL
- Extension PHP `mongodb` activée (ou accès à une instance MongoDB Atlas)

### Étapes d'installation

#### 1. Cloner le dépôt
```bash
git clone https://github.com/Kpeulet/ecoride-dwwm-final.git

#### 2. Placer les fichiers : Déplacer le dossier dans le répertoire Web (htdocs ou www).

#### 3. Importer la base de données MySQL :

- Créer une base nommée ecoride sur phpMyAdmin.

- Importer le fichier database/db_ecoride.sql.

#### 4. Configurer l'application :

- Ajuster les paramètres d'accès BDD dans config/db.php.

#### 5. Lancer le projet : Accéder à http://localhost/projet_ecoride (ou port 8888 sur MAMP).

## Documentation & Livrables
L'ensemble des documents requis pour le passage du titre professionnel (Dossier Projet, Manuel d'utilisation, Charte graphique, Schémas BDD) se trouve dans le dossier /docs.