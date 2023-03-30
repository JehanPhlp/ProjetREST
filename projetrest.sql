-- phpMyAdmin SQL Dump
-- version 4.5.4.1
-- http://www.phpmyadmin.net
--
-- Client :  localhost
-- Généré le :  Jeu 30 Mars 2023 à 07:06
-- Version du serveur :  5.7.11
-- Version de PHP :  7.2.7

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `projetrest`
--

-- --------------------------------------------------------

--
-- Structure de la table `disliker`
--

CREATE TABLE `disliker` (
  `Id_Utilisateur` int(11) NOT NULL,
  `Id_Post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `disliker`
--

INSERT INTO `disliker` (`Id_Utilisateur`, `Id_Post`) VALUES
(2, 9),
(2, 10),
(6, 10),
(6, 11),
(6, 13),
(6, 20);

-- --------------------------------------------------------

--
-- Structure de la table `liker`
--

CREATE TABLE `liker` (
  `Id_Utilisateur` int(11) NOT NULL,
  `Id_Post` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `liker`
--

INSERT INTO `liker` (`Id_Utilisateur`, `Id_Post`) VALUES
(5, 9),
(6, 9),
(5, 10),
(2, 11),
(5, 14),
(5, 16);

-- --------------------------------------------------------

--
-- Structure de la table `post`
--

CREATE TABLE `post` (
  `Id_Post` int(11) NOT NULL,
  `contenu` varchar(256) NOT NULL,
  `date_publication` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `Id_Utilisateur` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `post`
--

INSERT INTO `post` (`Id_Post`, `contenu`, `date_publication`, `Id_Utilisateur`) VALUES
(9, 'aaaaa', '2023-03-16 08:22:44', 3),
(10, 'aaaaa', '2023-03-16 08:22:37', 2),
(11, 'aaaaa', '2023-03-16 08:22:41', 2),
(12, 'aaaaa', '2023-03-16 07:53:17', 1),
(13, 'contenu', '2023-03-30 06:59:05', 5),
(14, 'contenu', '2023-03-30 06:59:05', 6),
(15, 'contenu', '2023-03-30 06:59:11', 5),
(16, 'contenu', '2023-03-30 06:59:11', 6),
(17, 'contenu', '2023-03-30 06:59:47', 6),
(18, 'contenu', '2023-03-30 06:59:47', 3),
(19, 'contenu', '2023-03-30 07:00:04', 5),
(20, 'contenu', '2023-03-30 07:00:04', 2);

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `Id_Utilisateur` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `mot_de_passe` varchar(200) NOT NULL,
  `role_utilisateur` char(9) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Contenu de la table `utilisateur`
--

INSERT INTO `utilisateur` (`Id_Utilisateur`, `nom`, `mot_de_passe`, `role_utilisateur`) VALUES
(1, 'moderator_user', 'f4705802369ed753cff9f5c7aefc8d192a22ada675d97511a0ebe7096fec1fcc', 'moderator'),
(2, 'publisher_user', '71fbbf06dcfe07c63d08adb7d5445426c00c853ec765e933329854da78948cd9', 'publisher'),
(3, 'publisher2_user', '567c008df16a6f2a8ea0a3a2b51604197664fc4f81c8be6a9eb3f37ef528f816', 'publisher'),
(4, 'moderator2_user', '129e712dbce10e01e4a0b6580502a2fe4f24d1a8358f6e96f3d7b2324f47afad\r\n', 'moderator'),
(5, 'publisher3_user', 'f97dce6bb5f88588865a1298df064fad9336b8b0d57034b5e995eb9b1b3707bd', ''),
(6, 'publisher4_user', '085a0a75ce7cf15d5558ecbdf4f46ff343dcacbf8e49f78b06de88756c19d17f', '');

--
-- Index pour les tables exportées
--

--
-- Index pour la table `disliker`
--
ALTER TABLE `disliker`
  ADD PRIMARY KEY (`Id_Utilisateur`,`Id_Post`),
  ADD KEY `Id_Post` (`Id_Post`);

--
-- Index pour la table `liker`
--
ALTER TABLE `liker`
  ADD PRIMARY KEY (`Id_Utilisateur`,`Id_Post`),
  ADD KEY `Id_Post` (`Id_Post`);

--
-- Index pour la table `post`
--
ALTER TABLE `post`
  ADD PRIMARY KEY (`Id_Post`),
  ADD KEY `Id_Utilisateur` (`Id_Utilisateur`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`Id_Utilisateur`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `post`
--
ALTER TABLE `post`
  MODIFY `Id_Post` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `Id_Utilisateur` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `disliker`
--
ALTER TABLE `disliker`
  ADD CONSTRAINT `disliker_ibfk_1` FOREIGN KEY (`Id_Utilisateur`) REFERENCES `utilisateur` (`Id_Utilisateur`),
  ADD CONSTRAINT `disliker_ibfk_2` FOREIGN KEY (`Id_Post`) REFERENCES `post` (`Id_Post`);

--
-- Contraintes pour la table `liker`
--
ALTER TABLE `liker`
  ADD CONSTRAINT `liker_ibfk_1` FOREIGN KEY (`Id_Utilisateur`) REFERENCES `utilisateur` (`Id_Utilisateur`),
  ADD CONSTRAINT `liker_ibfk_2` FOREIGN KEY (`Id_Post`) REFERENCES `post` (`Id_Post`);

--
-- Contraintes pour la table `post`
--
ALTER TABLE `post`
  ADD CONSTRAINT `post_ibfk_1` FOREIGN KEY (`Id_Utilisateur`) REFERENCES `utilisateur` (`Id_Utilisateur`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
