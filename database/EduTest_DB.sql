-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 02 fév. 2026 à 14:22
-- Version du serveur : 8.3.0
-- Version de PHP : 8.3.19

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `edutest`
--

-- --------------------------------------------------------

--
-- Structure de la table `answer`
--

DROP TABLE IF EXISTS `answer`;
CREATE TABLE IF NOT EXISTS `answer` (
  `id` int NOT NULL AUTO_INCREMENT,
  `assignment_id` int DEFAULT NULL,
  `question_id` int DEFAULT NULL,
  `answer_text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `points_awarded` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DADD4A25D19302F8` (`assignment_id`),
  KEY `IDX_DADD4A251E27F6BF` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=52 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `answer`
--

INSERT INTO `answer` (`id`, `assignment_id`, `question_id`, `answer_text`, `points_awarded`) VALUES
(37, 10, 1, '', 5),
(38, 10, 2, '', 5),
(39, 10, 3, '', 0),
(40, 10, 4, '', 5),
(41, 11, 5, '', 10),
(42, 11, 6, '', 0),
(47, 13, 5, '', 10),
(48, 13, 6, '', 0),
(49, 14, 7, '', 0),
(50, 14, 8, '', 0),
(51, 15, 9, '', 5);

-- --------------------------------------------------------

--
-- Structure de la table `answer_choice`
--

DROP TABLE IF EXISTS `answer_choice`;
CREATE TABLE IF NOT EXISTS `answer_choice` (
  `answer_id` int NOT NULL,
  `choice_id` int NOT NULL,
  PRIMARY KEY (`answer_id`,`choice_id`),
  KEY `IDX_33526035AA334807` (`answer_id`),
  KEY `IDX_33526035998666D1` (`choice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `answer_choice`
--

INSERT INTO `answer_choice` (`answer_id`, `choice_id`) VALUES
(37, 1),
(38, 4),
(39, 8),
(40, 10),
(41, 13),
(42, 18),
(47, 13),
(48, 17),
(51, 26);

-- --------------------------------------------------------

--
-- Structure de la table `assignment`
--

DROP TABLE IF EXISTS `assignment`;
CREATE TABLE IF NOT EXISTS `assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `status` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `started_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `submitted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `final_grade` double DEFAULT NULL,
  `proctoring_report` json DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_30C544BA578D5E91` (`exam_id`),
  KEY `IDX_30C544BACB944F1A` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `assignment`
--

INSERT INTO `assignment` (`id`, `exam_id`, `student_id`, `status`, `assigned_at`, `started_at`, `submitted_at`, `final_grade`, `proctoring_report`, `is_flagged`) VALUES
(10, 1, 2, 'SUBMITTED', '2025-10-23 14:22:10', '2025-10-23 14:22:43', '2025-10-23 14:23:45', 15, NULL, 0),
(11, 2, 2, 'SUBMITTED', '2025-10-24 18:10:25', '2025-10-24 18:11:08', '2025-10-24 18:11:19', 13.33, NULL, 0),
(13, 2, 7, 'SUBMITTED', '2025-11-28 23:03:51', '2025-11-28 23:04:13', '2025-11-28 23:05:30', 13.33, NULL, 0),
(14, 3, 7, 'SUBMITTED', '2025-11-28 23:13:15', '2025-11-28 23:13:41', '2025-11-28 23:28:41', 0, NULL, 0),
(15, 4, 7, 'SUBMITTED', '2025-12-28 00:54:42', '2025-12-28 00:55:15', '2025-12-28 01:02:27', 20, '{\"copyCount\": 2, \"incidents\": [{\"t\": 1766883712440, \"type\": \"tab_hidden\"}, {\"t\": 1766883719487, \"type\": \"copy\"}, {\"t\": 1766883723987, \"type\": \"copy\"}, {\"t\": 1766883726058, \"type\": \"tab_hidden\"}, {\"t\": 1766883734250, \"type\": \"tab_hidden\"}, {\"t\": 1766883738040, \"type\": \"tab_hidden\"}, {\"t\": 1766883739979, \"type\": \"tab_hidden\"}, {\"t\": 1766883742908, \"type\": \"tab_hidden\"}], \"pasteCount\": 0, \"tabHiddenCount\": 6}', 1),
(16, 5, 7, 'ASSIGNED', '2025-12-28 01:44:59', NULL, NULL, NULL, NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `choice`
--

DROP TABLE IF EXISTS `choice`;
CREATE TABLE IF NOT EXISTS `choice` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_C1AB5A921E27F6BF` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `choice`
--

INSERT INTO `choice` (`id`, `question_id`, `label`, `is_correct`) VALUES
(1, 1, 'Δ = b² - 4ac', 1),
(2, 1, 'Δ = 4ac - b²', 0),
(3, 1, 'Δ = (a+b)² - 4c', 0),
(4, 2, 'Une seule', 1),
(5, 2, 'Deux', 0),
(6, 2, 'Aucune', 0),
(7, 3, 'Aucune solution réelle', 1),
(8, 3, 'Une solution réelle', 0),
(9, 3, 'Deux solutions réelles', 0),
(10, 4, '1', 1),
(11, 4, '2', 0),
(12, 4, '5', 0),
(13, 5, 'Paris', 1),
(14, 5, 'Italy', 0),
(15, 5, 'Bruxelles', 0),
(16, 6, 'Scrapy', 1),
(17, 6, 'Salomon', 0),
(18, 6, 'Lee', 0),
(19, 7, 'Select', 1),
(20, 7, 'From', 0),
(21, 7, 'Where', 0),
(22, 7, 'rien', 0),
(23, 8, 'Snekha', 1),
(24, 8, 'Alice', 0),
(25, 8, 'Alicia', 0),
(26, 9, 'Paris', 1),
(27, 9, 'Londres', 0),
(28, 9, 'Lille', 0),
(29, 10, '1', 1),
(30, 10, '2', 0),
(31, 10, '3', 0);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) CHARACTER SET utf8mb3 COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20251020141612', '2025-10-20 14:16:27', 915),
('DoctrineMigrations\\Version20251025130223', '2025-10-25 13:02:41', 130),
('DoctrineMigrations\\Version20251227234904', '2025-12-27 23:50:02', 279),
('DoctrineMigrations\\Version20251228014315', '2025-12-28 01:43:38', 417),
('DoctrineMigrations\\Version20260105161120', '2026-01-05 16:49:19', 54),
('DoctrineMigrations\\Version20260105162828', '2026-01-05 16:49:20', 224),
('DoctrineMigrations\\Version20260105164912', '2026-01-05 16:49:20', 16);

-- --------------------------------------------------------

--
-- Structure de la table `exam`
--

DROP TABLE IF EXISTS `exam`;
CREATE TABLE IF NOT EXISTS `exam` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL,
  `start_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `end_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `teacher_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_38BBA6C641807E1D` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam`
--

INSERT INTO `exam` (`id`, `title`, `description`, `duration_minutes`, `start_at`, `end_at`, `teacher_id`) VALUES
(1, 'Mathématiques - Chapitre 2 : Les équations du second degré', 'Évaluation sur la résolution des équations du second degré, la factorisation et le calcul du discriminant.', 45, '2025-10-22 13:43:00', '2025-10-22 14:43:00', 1),
(2, 'QCM Mongodb', 'un qcm pour apprendre les bases de mongodb', 15, NULL, NULL, 6),
(3, 'MySQL Requêtes', NULL, 15, '2025-11-29 00:10:00', '2025-11-29 00:25:00', 6),
(4, 'Education Civique - 1', 'Un petit rappel', 15, '2025-12-28 01:52:00', '2025-12-28 02:15:00', 6),
(5, 'Education Civique - 2', 'test', 10, '2025-12-28 02:44:00', '2025-12-28 05:44:00', 6);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `available_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `delivered_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_75EA56E0FB7336F0` (`queue_name`),
  KEY `IDX_75EA56E0E3BD61CE` (`available_at`),
  KEY `IDX_75EA56E016BA31DB` (`delivered_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `question`
--

DROP TABLE IF EXISTS `question`;
CREATE TABLE IF NOT EXISTS `question` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `text` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` double NOT NULL,
  `content` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_B6F7494E578D5E91` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `question`
--

INSERT INTO `question` (`id`, `exam_id`, `text`, `type`, `points`, `content`) VALUES
(1, 1, 'Quelle est la formule du discriminant ?', 'QCM', 5, NULL),
(2, 1, 'Si Δ = 0, combien de solutions possède l’équation ?', 'QCM', 5, NULL),
(3, 1, 'Si Δ < 0, alors l’équation a :', 'QCM', 5, NULL),
(4, 1, 'Si a = 1, b = -3, c = 2, alors Δ = ?', 'QCM', 5, NULL),
(5, 2, 'Quelle est la capitale de la France', 'QCM', 10, NULL),
(6, 2, 'Quel est ton nom ?', 'QCM', 5, NULL),
(7, 3, 'Quelle commande pour sélectionner des colonnes ?', 'QCM', 5, NULL),
(8, 3, 'Quel est mon nom ?', 'QCM', 5, NULL),
(9, 4, 'Quelle est la capitale de France ?', 'QCM', 5, NULL),
(10, 5, 'Hello Test', 'QCM', 5, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_number` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `full_name`, `student_number`, `is_approved`, `created_at`) VALUES
(1, 'enseignant@demo.fr', '[\"ROLE_TEACHER\"]', '$2y$13$6fxMl9ipL33aooXrSqEpj.Bv2jZroAMcYHX/yj1hyx7lbDb1Eln32', 'Enseigant Demo', NULL, 1, '0000-00-00 00:00:00'),
(2, 'etudiant@demo.fr', '[\"ROLE_STUDENT\"]', '$2y$13$GnjlfxNc9qEJtQiQGwbDledX/1OKX2gcMaGY4lX/8fCjpyfGWgxgu', 'Etudiant Demo', NULL, 1, '0000-00-00 00:00:00'),
(3, 'admin@demo.fr', '[\"ROLE_ADMIN\"]', '$2y$13$uVaLbqPAozzD2U.eXj83bOuiAHCMVgZo0bwg13KHJxiBF0zvNwjE6', 'Administrateur', NULL, 1, '2025-10-25 15:22:06'),
(6, 'snekha@mail.com', '[\"ROLE_TEACHER\"]', '$2y$13$dSbMfWZf.LST0QE9vHmdvOGSCWW1c7Ych59eJMKnyUPq.SelfAe1m', 'Snekha Harikrishnan', NULL, 1, '2025-10-30 20:15:28'),
(7, 'sana@mail.com', '[\"ROLE_STUDENT\"]', '$2y$13$3N7BetkyQXPLtI9352vtr.yuVQdclODq/OdWjNmdBsl4Y4HA2JsGe', 'Sana Har', NULL, 1, '2025-11-08 20:11:24'),
(8, 'dupont@mail.fr', '[\"ROLE_TEACHER\"]', '$2y$13$hwXLnLXeaNGLXGg6QKDZq.pNlt2MkgmtXVmbGANZZHImlNx7V2Zwi', 'Dupont Marc', NULL, 1, '2025-11-09 19:32:04');

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `answer`
--
ALTER TABLE `answer`
  ADD CONSTRAINT `FK_DADD4A251E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`),
  ADD CONSTRAINT `FK_DADD4A25D19302F8` FOREIGN KEY (`assignment_id`) REFERENCES `assignment` (`id`);

--
-- Contraintes pour la table `answer_choice`
--
ALTER TABLE `answer_choice`
  ADD CONSTRAINT `FK_33526035998666D1` FOREIGN KEY (`choice_id`) REFERENCES `choice` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_33526035AA334807` FOREIGN KEY (`answer_id`) REFERENCES `answer` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `assignment`
--
ALTER TABLE `assignment`
  ADD CONSTRAINT `FK_30C544BA578D5E91` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`id`),
  ADD CONSTRAINT `FK_30C544BACB944F1A` FOREIGN KEY (`student_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `choice`
--
ALTER TABLE `choice`
  ADD CONSTRAINT `FK_C1AB5A921E27F6BF` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`);

--
-- Contraintes pour la table `exam`
--
ALTER TABLE `exam`
  ADD CONSTRAINT `FK_38BBA6C641807E1D` FOREIGN KEY (`teacher_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `question`
--
ALTER TABLE `question`
  ADD CONSTRAINT `FK_B6F7494E578D5E91` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
