-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost
-- Généré le : mer. 22 avr. 2026 à 12:58
-- Version du serveur : 8.0.45-0ubuntu0.24.04.1
-- Version de PHP : 8.4.20

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `espace_membre_2026`
--

-- --------------------------------------------------------

--
-- Structure de la table `membres`
--

CREATE TABLE `membres` (
  `id` int NOT NULL,
  `pseudo` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `mail` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `motdepasse` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `role` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'user',
  `avatar` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `membres`
--

INSERT INTO `membres` (`id`, `pseudo`, `mail`, `motdepasse`, `role`, `avatar`, `created_at`, `updated_at`) VALUES
(21, 'In cumque eligendi v', 'tarijoh@mailinator.com', '$2y$12$V4zbykIp51/qsXvUTruMDuv3FVgMH1ySl.IErB02DIhaTO3a2RgVe', 'user', NULL, '2026-04-22 08:18:32', '2026-04-22 08:18:32'),
(22, 'cfpc-i111', 'rostoinfocus@gmail.com', '$2y$12$G/NJarKWAijoYGKdakAVJOzWn5eUn7.Qvc5jj4ks2QbrvaRuzik6i', 'admin', '22.jpeg', '2026-04-22 08:18:59', '2026-04-22 08:18:59'),
(23, '       Créer un compte                                                                        Pseudo             ', 'rostoinfocuhhs@gmail.com', '$2y$12$92Dqg.A1M3WB9ZFhXlCqO.vQBlVDrjrZAwz3zCh3E7XcxzeLC0W32', 'user', NULL, '2026-04-22 08:42:01', '2026-04-22 08:42:01'),
(24, 'Perferendis quidem q', 'koxag@mailinator.com', '$2y$12$YRLdJVwShxyXs9l2Vlzu4ed8RegGmdvHYhK1LCWy7/ELxOZx1SYPq', 'user', NULL, '2026-04-22 08:57:29', '2026-04-22 08:57:29'),
(25, 'lele', 'lele@gmail.com', '$2y$12$EBnCUhWm/6XB6UgQYysQfeUvOe8CsW.SMUWp5PSAlBZ3O2EFnF7dW', 'user', NULL, '2026-04-22 08:58:13', '2026-04-22 08:58:13'),
(26, 'lili', 'lili@gmail.com', '$2y$12$Z2SNpUVqopy8qsGdw/46V.gxxb7Gfln1ElXh6/hUxMBnCcieOP9Bu', 'admin', '26.jpeg', '2026-04-22 12:35:19', '2026-04-22 12:35:19');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `membres`
--
ALTER TABLE `membres`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `membres`
--
ALTER TABLE `membres`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
