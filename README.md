# EcoRide - Plateforme de Covoiturage Écoresponsable

Projet réalisé dans le cadre du Titre Professionnel Développeur Web et Web Mobile (DWWM).

## 🚀 Installation en local

### Prérequis
* Serveur Web local (MAMP, WAMP, XAMPP ou Laragon) avec **PHP 8.x** et **MySQL**
* **MongoDB** (instance locale ou MongoDB Atlas pour la gestion des litiges)

### Étapes d'installation
1. Cloner le dépôt :
   `git clone https://github.com/VOTRE_PSEUDO/projet-ecoride.git`
2. Déplacer le dossier dans le répertoire Web de votre serveur (`htdocs` ou `www`).
3. Importer la base de données relationnelle :
   * Ouvrir phpMyAdmin.
   * Créer une base de données nommée `ecoride`.
   * Importer le fichier `database/ecoride.sql`.
4. Configurer la connexion BDD dans `config/db.php` si vos identifiants locaux diffèrent.
5. Accéder à l'application via `http://localhost/projet-ecoride`.

## 🔑 Identifiants de démonstration
* **Administrateur :** `admin@ecoride.com` / `EcoRide2026!Admin`
* **Conducteur :** `ecoride-chauffeur@yopmail.com` / `ChauffeurTest`

## 📄 Documentation & Livrables
L'ensemble des livrables de certification (Manuel d'utilisation, Documentation technique, Charte graphique, etc.) est disponible dans le dossier `/docs`.