-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : lun. 02 mars 2026 à 09:24
-- Version du serveur : 8.3.0
-- Version de PHP : 8.4.18

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `edutest_db`
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
  `answer_text` longtext COLLATE utf8mb4_unicode_ci,
  `points_awarded` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_DADD4A25D19302F8` (`assignment_id`),
  KEY `IDX_DADD4A251E27F6BF` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9052 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `answer`
--

INSERT INTO `answer` (`id`, `assignment_id`, `question_id`, `answer_text`, `points_awarded`) VALUES
(9001, 3, 101, NULL, 3),
(9002, 3, 102, NULL, 2),
(9003, 3, 103, NULL, 4.5),
(9011, 4, 301, NULL, 3),
(9012, 4, 302, NULL, 2),
(9013, 4, 303, NULL, 3.5),
(9021, 5, 401, NULL, 2),
(9022, 5, 402, NULL, 3),
(9023, 5, 403, NULL, 5),
(9031, 6, 501, NULL, 2),
(9032, 6, 502, NULL, 1.5),
(9033, 6, 503, NULL, 5),
(9037, 22, 601, '', 0),
(9038, 22, 602, '', 3),
(9039, 22, 603, '', 0),
(9040, 28, 401, '', 2),
(9041, 28, 402, '', 3),
(9042, 28, 403, '', 5),
(9043, 31, 604, '', 5),
(9044, 31, 605, '', 0),
(9045, 25, 101, '', 3),
(9046, 25, 102, '', 2),
(9047, 25, 103, '', 5),
(9048, 12, 201, '', 2),
(9049, 12, 202, '', 3),
(9050, 12, 203, '', 5),
(9051, 32, 607, '', 5);

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
(9001, 1001),
(9002, 1007),
(9003, 1009),
(9011, 3001),
(9012, 3006),
(9013, 3009),
(9021, 4001),
(9022, 4005),
(9023, 4009),
(9031, 5002),
(9032, 5005),
(9033, 5009),
(9037, 6002),
(9038, 6005),
(9039, 6010),
(9040, 4001),
(9041, 4005),
(9042, 4009),
(9043, 6014),
(9044, 6017),
(9045, 1001),
(9046, 1007),
(9047, 1009),
(9048, 2001),
(9049, 2006),
(9050, 2011),
(9051, 6025);

-- --------------------------------------------------------

--
-- Structure de la table `assignment`
--

DROP TABLE IF EXISTS `assignment`;
CREATE TABLE IF NOT EXISTS `assignment` (
  `id` int NOT NULL AUTO_INCREMENT,
  `exam_id` int NOT NULL,
  `student_id` int NOT NULL,
  `status` varchar(15) COLLATE utf8mb4_unicode_ci NOT NULL,
  `assigned_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `started_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `submitted_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `final_grade` double DEFAULT NULL,
  `proctoring_report` json DEFAULT NULL,
  `is_flagged` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_30C544BA578D5E91` (`exam_id`),
  KEY `IDX_30C544BACB944F1A` (`student_id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `assignment`
--

INSERT INTO `assignment` (`id`, `exam_id`, `student_id`, `status`, `assigned_at`, `started_at`, `submitted_at`, `final_grade`, `proctoring_report`, `is_flagged`) VALUES
(1, 1, 5, 'ASSIGNED', '2026-02-27 10:00:00', NULL, NULL, NULL, NULL, 0),
(3, 1, 9, 'SUBMITTED', '2026-02-27 10:00:00', '2026-02-27 10:02:00', '2026-02-27 10:35:00', 15.5, NULL, 0),
(4, 3, 8, 'SUBMITTED', '2026-02-27 11:00:00', '2026-02-27 11:02:00', '2026-02-27 11:42:00', 13.5, NULL, 0),
(5, 4, 7, 'SUBMITTED', '2026-02-27 11:30:00', '2026-02-27 11:35:00', '2026-02-27 11:55:00', 17, NULL, 0),
(6, 5, 5, 'SUBMITTED', '2026-02-27 12:00:00', '2026-02-27 12:03:00', '2026-02-27 12:28:00', 12, NULL, 0),
(7, 6, 6, 'ASSIGNED', '2026-02-27 12:30:00', NULL, NULL, NULL, NULL, 0),
(8, 1, 7, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:15:00', '2026-02-27 18:45:00', 14, NULL, 0),
(10, 2, 5, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:12:00', '2026-02-27 18:43:00', 16, NULL, 0),
(11, 2, 6, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:14:00', '2026-02-27 18:47:00', 11.5, NULL, 0),
(12, 2, 9, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-28 20:52:56', '2026-02-28 20:54:48', 20, '{\"copyCount\": 6, \"incidents\": [{\"t\": 1772311983888, \"type\": \"copy\"}, {\"t\": 1772311987634, \"type\": \"tab_hidden\"}, {\"t\": 1772311998184, \"type\": \"copy\"}, {\"t\": 1772312001280, \"type\": \"copy\"}, {\"t\": 1772312004769, \"type\": \"copy\"}, {\"t\": 1772312008296, \"type\": \"copy\"}, {\"t\": 1772312010648, \"type\": \"copy\"}, {\"t\": 1772312011350, \"type\": \"tab_hidden\"}, {\"t\": 1772312013782, \"type\": \"tab_hidden\"}, {\"t\": 1772312017568, \"type\": \"tab_hidden\"}, {\"t\": 1772312019765, \"type\": \"tab_hidden\"}, {\"t\": 1772312022469, \"type\": \"tab_hidden\"}, {\"t\": 1772312024128, \"type\": \"tab_hidden\"}, {\"t\": 1772312026541, \"type\": \"tab_hidden\"}, {\"t\": 1772312029096, \"type\": \"tab_hidden\"}], \"pasteCount\": 0, \"tabHiddenCount\": 9}', 1),
(14, 3, 6, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:20:00', '2026-02-27 19:05:00', 9.5, NULL, 0),
(15, 3, 7, 'ASSIGNED', '2026-02-27 18:10:32', NULL, NULL, NULL, NULL, 0),
(16, 4, 5, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:12:00', '2026-02-27 18:30:00', 18, NULL, 0),
(19, 5, 7, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:12:00', '2026-02-27 18:35:00', 10, NULL, 0),
(20, 5, 8, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:12:00', '2026-02-27 18:37:00', 14.5, NULL, 0),
(22, 6, 5, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-28 14:54:07', '2026-02-28 14:54:45', 6, '{\"copyCount\": 0, \"incidents\": [], \"pasteCount\": 0, \"tabHiddenCount\": 0}', 0),
(23, 6, 7, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:25:00', '2026-02-27 19:00:00', 15, NULL, 0),
(24, 6, 8, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-27 18:26:00', '2026-02-27 19:02:00', 7, NULL, 0),
(25, 1, 6, 'SUBMITTED', '2026-02-27 10:00:00', '2026-02-28 20:48:51', '2026-02-28 20:51:17', 20, '{\"copyCount\": 0, \"incidents\": [], \"pasteCount\": 0, \"tabHiddenCount\": 0}', 0),
(26, 1, 8, 'ASSIGNED', '2026-02-27 18:10:32', NULL, NULL, NULL, NULL, 0),
(27, 3, 5, 'ASSIGNED', '2026-02-27 18:10:32', NULL, NULL, NULL, NULL, 0),
(28, 4, 8, 'SUBMITTED', '2026-02-27 18:10:32', '2026-02-28 16:25:02', '2026-02-28 16:26:05', 20, '{\"copyCount\": 3, \"incidents\": [{\"t\": 1772295912244, \"type\": \"copy\"}, {\"t\": 1772295918590, \"type\": \"tab_hidden\"}, {\"t\": 1772295928322, \"type\": \"copy\"}, {\"t\": 1772295929250, \"type\": \"tab_hidden\"}, {\"t\": 1772295932769, \"type\": \"tab_hidden\"}, {\"t\": 1772295941650, \"type\": \"copy\"}], \"pasteCount\": 0, \"tabHiddenCount\": 3}', 1),
(29, 4, 9, 'ASSIGNED', '2026-02-27 18:10:32', NULL, NULL, NULL, NULL, 0),
(30, 5, 9, 'ASSIGNED', '2026-02-27 18:10:32', NULL, NULL, NULL, NULL, 0),
(31, 13, 9, 'SUBMITTED', '2026-02-28 17:16:39', '2026-02-28 17:16:57', '2026-02-28 17:17:15', 10, '{\"copyCount\": 0, \"incidents\": [], \"pasteCount\": 0, \"tabHiddenCount\": 0}', 0),
(32, 14, 5, 'SUBMITTED', '2026-03-01 22:57:09', '2026-03-01 22:57:28', '2026-03-01 22:57:41', 20, '{\"copyCount\": 0, \"incidents\": [], \"pasteCount\": 0, \"tabHiddenCount\": 0}', 0);

-- --------------------------------------------------------

--
-- Structure de la table `choice`
--

DROP TABLE IF EXISTS `choice`;
CREATE TABLE IF NOT EXISTS `choice` (
  `id` int NOT NULL AUTO_INCREMENT,
  `question_id` int NOT NULL,
  `label` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_correct` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `IDX_C1AB5A921E27F6BF` (`question_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6029 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `choice`
--

INSERT INTO `choice` (`id`, `question_id`, `label`, `is_correct`) VALUES
(1001, 101, 'Δ = b² - 4ac', 1),
(1002, 101, 'Δ = a² - 4bc', 0),
(1003, 101, 'Δ = (a+b)² - c', 0),
(1004, 101, 'Δ = b² + 4ac', 0),
(1005, 102, 'Aucune solution réelle', 0),
(1006, 102, 'Une solution réelle', 0),
(1007, 102, 'Deux solutions réelles', 1),
(1008, 102, 'Trois solutions réelles', 0),
(1009, 103, 'a(x - α)² + β', 1),
(1010, 103, '(x + a)² + b', 0),
(1011, 103, 'a(x + b)² + c', 0),
(1012, 103, 'ax² + bx + c', 0),
(2001, 201, '&&', 1),
(2002, 201, '||', 0),
(2003, 201, '!=', 0),
(2004, 201, '==', 0),
(2005, 202, 'if', 0),
(2006, 202, 'while', 1),
(2007, 202, 'switch', 0),
(2008, 202, 'return', 0),
(2009, 203, 'O(1)', 0),
(2010, 203, 'O(log n)', 0),
(2011, 203, 'O(n)', 1),
(2012, 203, 'O(n²)', 0),
(3001, 301, 'HAVING', 1),
(3002, 301, 'WHERE', 0),
(3003, 301, 'ORDER BY', 0),
(3004, 301, 'LIMIT', 0),
(3005, 302, 'INNER JOIN', 0),
(3006, 302, 'LEFT JOIN', 1),
(3007, 302, 'RIGHT JOIN', 0),
(3008, 302, 'CROSS JOIN', 0),
(3009, 303, 'UNIQUE', 1),
(3010, 303, 'CHECK', 0),
(3011, 303, 'DEFAULT', 0),
(3012, 303, 'FOREIGN KEY', 0),
(4001, 401, 'Collection', 1),
(4002, 401, 'Row', 0),
(4003, 401, 'Schema', 0),
(4004, 401, 'Trigger', 0),
(4005, 402, 'find()', 1),
(4006, 402, 'select()', 0),
(4007, 402, 'fetch()', 0),
(4008, 402, 'getAll()', 0),
(4009, 403, 'Accélérer les recherches', 1),
(4010, 403, 'Sauvegarder automatiquement les documents', 0),
(4011, 403, 'Chiffrer la base', 0),
(4012, 403, 'Supprimer les doublons sans contrainte', 0),
(5001, 501, '5', 0),
(5002, 501, '7', 1),
(5003, 501, '8', 0),
(5004, 501, '10', 0),
(5005, 502, 'Réseau', 0),
(5006, 502, 'Transport', 1),
(5007, 502, 'Session', 0),
(5008, 502, 'Physique', 0),
(5009, 503, 'HTTP', 1),
(5010, 503, 'IP', 0),
(5011, 503, 'Ethernet', 0),
(5012, 503, 'ARP', 0),
(6001, 601, 'SHA-256', 1),
(6002, 601, 'RSA', 0),
(6003, 601, 'AES', 0),
(6004, 601, 'Diffie-Hellman', 0),
(6005, 602, 'Assurer la confidentialité', 1),
(6006, 602, 'Accélérer le réseau', 0),
(6007, 602, 'Supprimer les malwares', 0),
(6008, 602, 'Garantir l’unicité des données', 0),
(6009, 603, 'Mot de passe uniquement', 0),
(6010, 603, 'Email uniquement', 0),
(6011, 603, 'Mot de passe + code SMS / appli', 1),
(6012, 603, 'Nom d’utilisateur uniquement', 0),
(6013, 604, '3x', 0),
(6014, 604, '6x', 1),
(6015, 604, '5x', 0),
(6016, 604, '3x²', 0),
(6017, 605, '-sin(x)', 0),
(6018, 605, '-sin(x)', 0),
(6019, 605, 'cos(x)', 1),
(6020, 605, '-cos(x)', 0),
(6025, 607, 'SELECT * FROM clients;', 1),
(6026, 607, 'SELECT clients;', 0),
(6027, 607, 'GET * FROM clients;', 0),
(6028, 607, 'SELECT ALL clients;', 0);

-- --------------------------------------------------------

--
-- Structure de la table `doctrine_migration_versions`
--

DROP TABLE IF EXISTS `doctrine_migration_versions`;
CREATE TABLE IF NOT EXISTS `doctrine_migration_versions` (
  `version` varchar(191) COLLATE utf8mb3_unicode_ci NOT NULL,
  `executed_at` datetime DEFAULT NULL,
  `execution_time` int DEFAULT NULL,
  PRIMARY KEY (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

--
-- Déchargement des données de la table `doctrine_migration_versions`
--

INSERT INTO `doctrine_migration_versions` (`version`, `executed_at`, `execution_time`) VALUES
('DoctrineMigrations\\Version20251020141612', '2026-02-27 16:29:28', 775),
('DoctrineMigrations\\Version20251025130223', '2026-02-27 16:29:28', 72),
('DoctrineMigrations\\Version20260105161120', '2026-02-27 16:29:29', 194),
('DoctrineMigrations\\Version20260105162828', '2026-02-27 16:29:29', 113),
('DoctrineMigrations\\Version20260105164912', '2026-02-27 16:29:29', 17);

-- --------------------------------------------------------

--
-- Structure de la table `exam`
--

DROP TABLE IF EXISTS `exam`;
CREATE TABLE IF NOT EXISTS `exam` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `duration_minutes` int NOT NULL,
  `start_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `end_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  `teacher_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_38BBA6C641807E1D` (`teacher_id`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `exam`
--

INSERT INTO `exam` (`id`, `title`, `description`, `duration_minutes`, `start_at`, `end_at`, `teacher_id`) VALUES
(1, 'Mathématiques - Équations du second degré', 'Discriminant, solutions, factorisation et formes canoniques.', 45, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 2),
(2, 'Algorithmique - Structures conditionnelles', 'Conditions, boucles, pseudo-code et cas limites.', 40, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 2),
(3, 'Base de données - SQL Avancé', 'Jointures, GROUP BY, sous-requêtes, contraintes, normalisation.', 50, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 3),
(4, 'MongoDB - Fondamentaux', 'Documents, collections, filtres, projections, index.', 25, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 3),
(5, 'Réseaux - Modèle OSI', 'Les 7 couches OSI, protocoles et exemples.', 30, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 4),
(6, 'Cybersécurité - Bases', 'Authentification, hashage, chiffrement sym/asym, bonnes pratiques.', 35, '2026-02-27 09:00:00', '2026-04-28 23:59:59', 4),
(13, 'Mathématiques — Fonctions & dérivées', 'Examen semestre 2', 30, '2026-02-28 09:00:00', '2026-02-28 19:00:00', 2),
(14, 'MySQL Requêtes', 'Examen - semestre 2', 10, '2026-03-01 23:55:00', '2026-03-02 23:55:00', 3);

-- --------------------------------------------------------

--
-- Structure de la table `messenger_messages`
--

DROP TABLE IF EXISTS `messenger_messages`;
CREATE TABLE IF NOT EXISTS `messenger_messages` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `body` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `headers` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue_name` varchar(190) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  `text` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `points` double NOT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_B6F7494E578D5E91` (`exam_id`)
) ENGINE=InnoDB AUTO_INCREMENT=608 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `question`
--

INSERT INTO `question` (`id`, `exam_id`, `text`, `type`, `points`, `content`) VALUES
(101, 1, 'Quelle est la formule du discriminant Δ pour ax²+bx+c ?', 'QCM', 3, NULL),
(102, 1, 'Si Δ > 0, combien de solutions réelles possède l’équation ?', 'QCM', 2, NULL),
(103, 1, 'Quelle est la forme canonique de ax²+bx+c ?', 'QCM', 5, NULL),
(201, 2, 'Quel opérateur représente le ET logique dans la plupart des langages ?', 'QCM', 2, NULL),
(202, 2, 'Quelle structure permet de répéter tant qu’une condition est vraie ?', 'QCM', 3, NULL),
(203, 2, 'Quelle est la complexité en temps d’une recherche linéaire ?', 'QCM', 5, NULL),
(301, 3, 'Quelle clause filtre les groupes après un GROUP BY ?', 'QCM', 3, NULL),
(302, 3, 'Quelle jointure conserve toutes les lignes de la table de gauche ?', 'QCM', 2, NULL),
(303, 3, 'Quelle contrainte garantit l’unicité d’une colonne ?', 'QCM', 5, NULL),
(401, 4, 'Quel est l’équivalent d’une table en MongoDB ?', 'QCM', 2, NULL),
(402, 4, 'Quelle méthode permet de récupérer des documents ?', 'QCM', 3, NULL),
(403, 4, 'À quoi sert un index ?', 'QCM', 5, NULL),
(501, 5, 'Combien de couches possède le modèle OSI ?', 'QCM', 2, NULL),
(502, 5, 'Dans quelle couche se situe TCP ?', 'QCM', 3, NULL),
(503, 5, 'Quel protocole est typiquement en couche Application ?', 'QCM', 5, NULL),
(601, 6, 'Quel algorithme est un exemple de hash (empreinte) ?', 'QCM', 2, NULL),
(602, 6, 'Quel est l’objectif principal du chiffrement ?', 'QCM', 3, NULL),
(603, 6, 'Lequel est un exemple d’authentification multi-facteurs ?', 'QCM', 5, NULL),
(604, 13, 'Quelle est la dérivée de f(x) = 3x² ?', 'QCM', 5, NULL),
(605, 13, 'Quelle est la dérivée de sin(x) ?', 'QCM', 5, NULL),
(607, 14, 'Quelle requête permet d’afficher toutes les colonnes de la table clients ?', 'QCM', 5, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

DROP TABLE IF EXISTS `user`;
CREATE TABLE IF NOT EXISTS `user` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `student_number` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_8D93D649E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `full_name`, `student_number`, `is_approved`, `created_at`) VALUES
(1, 'admin@demo.fr', '[\"ROLE_ADMIN\"]', '$2y$13$uVaLbqPAozzD2U.eXj83bOuiAHCMVgZo0bwg13KHJxiBF0zvNwjE6', 'Administrateur', NULL, 1, '2026-02-27 16:31:17'),
(2, 'snekha.hari@edutest.fr', '[\"ROLE_TEACHER\"]', '$2y$13$Isb/.OVE9ybTYHUxxuAlFeRbBIfKjFdrAYF3YkkttkNIMsLidMO.G', 'Snekha Harikrishnan', NULL, 1, '2026-02-27 16:40:27'),
(3, 'karine.deflandre@edutest.fr', '[\"ROLE_TEACHER\"]', '$2y$13$na4JvEgWCU18dAjCgG3b2.c8cNE/isxQQeOkmU4i28Nnq38TX/C1y', 'Karine Deflandre', NULL, 1, '2026-02-27 16:42:50'),
(4, 'augustin.bertran@edutest.fr', '[\"ROLE_TEACHER\"]', '$2y$13$0RarHpF/iJNfRRtdsmvx1uExjj6nPc6c2GNCM8fmVqPmV.hmyUo7G', 'Augustin Bertran', NULL, 1, '2026-02-27 16:44:03'),
(5, 'alicia.danjean@edutest.fr', '[\"ROLE_STUDENT\"]', '$2y$13$fxyI.GfbMo7QHflF1XTgb.eKoP6STOTmigHLsBHvgW0PKypGtV2sW', 'Alicia Danjean', NULL, 1, '2026-02-27 16:44:43'),
(6, 'angelique.morel@edutest.fr', '[\"ROLE_STUDENT\"]', '$2y$13$5H1aAAFI3cnQAIIspBQEH.7ZRVBV/1BtZL7aM4uQJh/I4bGvUhzD2', 'Angelique Morel', NULL, 1, '2026-02-27 16:45:08'),
(7, 'yanis.leblanc@edutest.fr', '[\"ROLE_STUDENT\"]', '$2y$13$WlVNeURSCsbD/PzLYyHuC.eKnz.Yyx1JkT4QfwNcu3Cat2ET724me', 'Yanis Leblanc', NULL, 1, '2026-02-27 16:46:11'),
(8, 'thomas.ribeiro@edutest.fr', '[\"ROLE_STUDENT\"]', '$2y$13$ooAO88C0sTigEwha/wXFauE8QpthEz0Pvz1xYvFMQjotNm90z.APG', 'Thomas Ribeiro', NULL, 1, '2026-02-27 16:47:49'),
(9, 'sana.bouchra@edutest.fr', '[\"ROLE_STUDENT\"]', '$2y$13$xjEvE.Ai5T6qIMAgfSKrB.UNAsZSwHwti.bjhmvWYZ25f.DH25VWy', 'Sana Bouchra', NULL, 1, '2026-02-27 16:48:42');

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
