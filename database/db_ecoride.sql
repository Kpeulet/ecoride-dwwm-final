-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:8889
-- Généré le : dim. 09 août 2026 à 09:18
-- Version du serveur : 8.0.44
-- Version de PHP : 8.3.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `db_ecoride`
--

-- --------------------------------------------------------

--
-- Structure de la table `avis`
--

CREATE TABLE `avis` (
  `id` int NOT NULL,
  `commentaire` text NOT NULL,
  `note` int NOT NULL,
  `id_trajet` int NOT NULL,
  `id_expediteur` int NOT NULL,
  `statut` varchar(20) DEFAULT 'en_attente',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `avis`
--

INSERT INTO `avis` (`id`, `commentaire`, `note`, `id_trajet`, `id_expediteur`, `statut`, `date_creation`) VALUES
(1, 'Chauffeur aimable, à recommander.', 4, 106, 104, 'valide', '2026-08-08 17:53:45'),
(2, 'J\'ai passé un excellent voyage,\r\nj\'avais déjà voyagé avec ce chauffeur vraiment aimable.\r\nAimable, communicatif et attentionné.', 5, 109, 104, 'valide', '2026-08-08 18:58:58');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int NOT NULL,
  `id_trajet` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `date_reservation` datetime NOT NULL,
  `statut` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'validée',
  `rappel_envoye` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `id_trajet`, `id_utilisateur`, `date_reservation`, `statut`, `rappel_envoye`) VALUES
(1, 106, 104, '2026-08-08 16:47:38', 'validé', 0),
(2, 109, 104, '2026-08-08 18:56:49', 'validé', 0);

-- --------------------------------------------------------

--
-- Structure de la table `trajets`
--

CREATE TABLE `trajets` (
  `id` int NOT NULL,
  `id_chauffeur` int NOT NULL,
  `ville_depart` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `ville_arrivee` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_depart` datetime NOT NULL,
  `date_arrivee` datetime NOT NULL,
  `prix` decimal(10,2) NOT NULL,
  `places_disponibles` int NOT NULL,
  `id_vehicule` int NOT NULL,
  `statut` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'ouvert'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `trajets`
--

INSERT INTO `trajets` (`id`, `id_chauffeur`, `ville_depart`, `ville_arrivee`, `date_depart`, `date_arrivee`, `prix`, `places_disponibles`, `id_vehicule`, `statut`) VALUES
(106, 105, 'Paris', 'Lyon', '2026-08-11 10:30:00', '2026-08-11 16:45:00', 10.00, 2, 103, 'en_cours'),
(109, 105, 'Lyon', 'Paris', '2026-08-14 17:40:00', '2026-08-14 23:46:00', 8.00, 1, 103, 'termine'),
(110, 105, 'Nantes', 'Bordeaux', '2026-08-11 09:45:00', '2026-08-11 13:30:00', 7.00, 3, 103, 'ouvert');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id` int NOT NULL,
  `pseudo` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `est_chauffeur` tinyint(1) NOT NULL DEFAULT '0',
  `preferences_libres` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `credits` int DEFAULT '20',
  `role` enum('visiteur','utilisateur','employe','admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT 'utilisateur',
  `photo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'images/default.png',
  `statut_compte` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'actif',
  `note` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id`, `pseudo`, `est_chauffeur`, `preferences_libres`, `email`, `password`, `credits`, `role`, `photo`, `statut_compte`, `note`) VALUES
(102, 'Admin', 0, NULL, 'admin@ecoride.fr', '$2y$10$91ZUtvO51zvaPFrLk6xbdub5wfZJW12mF3WwOV7UciwrzkpKFvIL6', 20, 'admin', 'images/default.png', 'actif', NULL),
(103, 'Employe1', 0, NULL, 'employe@ecoride.fr', '$2y$10$jZqKZ38rEwIKwmepl4XrR.GoSXoJ4DokW1mRe476y35w9Pz4HFuPa', 0, 'employe', 'images/default.png', 'actif', NULL),
(104, 'PassagerTest', 0, NULL, 'ecoride-passager@yopmail.com', '$2y$10$pYQ7z1lSxXZF5S4B/4HfdeO0vA08trGhmrDmqW/dDxFJrlOMPinHO', 90, 'utilisateur', 'images/default.png', 'actif', NULL),
(105, 'ChauffeurTest', 1, '• Bagages légers uniquement.\r\n• Je n\'accepte que 2 personnes à l\'arrière pour votre confort', 'ecoride-chauffeur@yopmail.com', '$2y$10$1NRP/KEk7wf1.xEJA0mF.O7E4j7fYWMzekNebp9/2ceGyelVvPT/e', 56, 'utilisateur', 'images/default.png', 'actif', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `vehicule`
--

CREATE TABLE `vehicule` (
  `id` int NOT NULL,
  `id_utilisateur` int NOT NULL,
  `marque` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `modele` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `couleur` varchar(30) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `energie` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `immatriculation` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `date_premiere_immat` date NOT NULL,
  `nb_places` int NOT NULL,
  `fumeur` tinyint(1) DEFAULT '0',
  `animaux` tinyint(1) DEFAULT '0',
  `preferences_additionnelles` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `vehicule`
--

INSERT INTO `vehicule` (`id`, `id_utilisateur`, `marque`, `modele`, `couleur`, `energie`, `immatriculation`, `date_premiere_immat`, `nb_places`, `fumeur`, `animaux`, `preferences_additionnelles`) VALUES
(103, 105, 'Tesla', '3', 'Gris', 'electrique', 'GE-591-KR', '2023-08-08', 3, 0, 0, NULL),
(104, 105, 'Peugeot', '208', 'Vert', 'thermique', 'AB-123-CD', '2021-02-09', 4, 0, 0, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_trajet` (`id_trajet`),
  ADD KEY `id_expediteur` (`id_expediteur`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_trajet` (`id_trajet`),
  ADD KEY `fk_utilisateur` (`id_utilisateur`);

--
-- Index pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_chauffeur` (`id_chauffeur`),
  ADD KEY `id_vehicule` (`id_vehicule`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_vehicule_utilisateur` (`id_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `trajets`
--
ALTER TABLE `trajets`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=111;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=106;

--
-- AUTO_INCREMENT pour la table `vehicule`
--
ALTER TABLE `vehicule`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `avis`
--
ALTER TABLE `avis`
  ADD CONSTRAINT `avis_ibfk_1` FOREIGN KEY (`id_trajet`) REFERENCES `trajets` (`id`),
  ADD CONSTRAINT `avis_ibfk_2` FOREIGN KEY (`id_expediteur`) REFERENCES `utilisateur` (`id`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `fk_trajet` FOREIGN KEY (`id_trajet`) REFERENCES `trajets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD CONSTRAINT `trajets_ibfk_1` FOREIGN KEY (`id_chauffeur`) REFERENCES `utilisateur` (`id`),
  ADD CONSTRAINT `trajets_ibfk_2` FOREIGN KEY (`id_vehicule`) REFERENCES `vehicule` (`id`);

--
-- Contraintes pour la table `vehicule`
--
ALTER TABLE `vehicule`
  ADD CONSTRAINT `fk_vehicule_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
