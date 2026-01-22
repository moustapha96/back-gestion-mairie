-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 22 jan. 2026 à 15:10
-- Version du serveur : 9.1.0
-- Version de PHP : 8.1.31

SET FOREIGN_KEY_CHECKS=0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion-mairie-complet`
--

-- --------------------------------------------------------

--
-- Structure de la table `contact_messages`
--

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `reference` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent` tinyint(1) NOT NULL,
  `piece_jointe` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `nom`, `email`, `telephone`, `categorie`, `reference`, `message`, `consent`, `piece_jointe`, `created_at`) VALUES
(1, 'Alioun', 'aejqUFJKN@ZSFJ.Fr', '+221 77 123 45 67', 'DEMANDE_PARCELLE', NULL, 'Q.DFSNK', 1, NULL, '2025-11-13 13:04:42');

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
('DoctrineMigrations\\Version20250929203239', '2025-09-29 20:32:59', 1015),
('DoctrineMigrations\\Version20250930091358', '2025-09-30 09:15:49', 319),
('DoctrineMigrations\\Version20250930092221', '2025-09-30 09:22:27', 168),
('DoctrineMigrations\\Version20250930121441', '2025-09-30 12:14:48', 202),
('DoctrineMigrations\\Version20251003082705', '2025-10-03 08:27:24', 674),
('DoctrineMigrations\\Version20251017120741', '2025-10-17 12:08:14', 342);

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_articles_terrains`
--

DROP TABLE IF EXISTS `gs_mairie_articles_terrains`;
CREATE TABLE IF NOT EXISTS `gs_mairie_articles_terrains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categorie_id` int DEFAULT NULL,
  `auteur_id` int DEFAULT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `content` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_B5715083BCF5E72D` (`categorie_id`),
  KEY `IDX_B571508360BB6FE6` (`auteur_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_attribuation_historiques`
--

DROP TABLE IF EXISTS `gs_mairie_attribuation_historiques`;
CREATE TABLE IF NOT EXISTS `gs_mairie_attribuation_historiques` (
  `id` int NOT NULL AUTO_INCREMENT,
  `attribution_id` int NOT NULL,
  `from_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `to_status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `changed_at` datetime NOT NULL,
  `comment` longtext COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  KEY `IDX_4CF43F71EEB69F7B` (`attribution_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_attribuation_parcelle`
--

DROP TABLE IF EXISTS `gs_mairie_attribuation_parcelle`;
CREATE TABLE IF NOT EXISTS `gs_mairie_attribuation_parcelle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `demande_id` int DEFAULT NULL,
  `parcelle_id` int DEFAULT NULL,
  `date_effet` datetime DEFAULT NULL,
  `date_fin` datetime DEFAULT NULL,
  `montant` double DEFAULT NULL,
  `frequence` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etat_paiement` tinyint(1) DEFAULT NULL,
  `conditions_mise_en_valeur` longtext COLLATE utf8mb4_unicode_ci,
  `duree_validation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `decision_conseil` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pv_commision` longtext COLLATE utf8mb4_unicode_ci,
  `pv_validation_provisoire` longtext COLLATE utf8mb4_unicode_ci,
  `pv_attribution_provisoire` longtext COLLATE utf8mb4_unicode_ci,
  `pv_approbation_prefet` longtext COLLATE utf8mb4_unicode_ci,
  `pv_approbation_conseil` longtext COLLATE utf8mb4_unicode_ci,
  `statut_attribution` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `date_validation_provisoire` datetime DEFAULT NULL,
  `date_attribution_provisoire` datetime DEFAULT NULL,
  `date_approbation_prefet` datetime DEFAULT NULL,
  `date_approbation_conseil` datetime DEFAULT NULL,
  `date_attribution_definitive` datetime DEFAULT NULL,
  `doc_notification_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdf_notification_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bulletin_liquidation_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_FD50F1F980E95E18` (`demande_id`),
  UNIQUE KEY `UNIQ_FD50F1F94433ED66` (`parcelle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_audit_log`
--

DROP TABLE IF EXISTS `gs_mairie_audit_log`;
CREATE TABLE IF NOT EXISTS `gs_mairie_audit_log` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `actor_id` int DEFAULT NULL,
  `actor_identifier` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_class` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entity_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `http_method` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `route` varchar(1024) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `path` varchar(2048) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` longtext COLLATE utf8mb4_unicode_ci,
  `request_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correlation_id` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci COMMENT '(DC2Type:json)',
  `changes` longtext COLLATE utf8mb4_unicode_ci COMMENT '(DC2Type:json)',
  `metadata` longtext COLLATE utf8mb4_unicode_ci COMMENT '(DC2Type:json)',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `idx_auditlog_created` (`created_at`),
  KEY `idx_auditlog_actor` (`actor_id`),
  KEY `idx_auditlog_event` (`event`),
  KEY `idx_auditlog_entity` (`entity_class`,`entity_id`),
  KEY `idx_auditlog_request` (`request_id`)
) ENGINE=InnoDB AUTO_INCREMENT=209 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_audit_log`
--

INSERT INTO `gs_mairie_audit_log` (`id`, `actor_id`, `actor_identifier`, `event`, `entity_class`, `entity_id`, `http_method`, `route`, `path`, `ip`, `user_agent`, `request_id`, `correlation_id`, `payload`, `changes`, `metadata`, `status`, `message`, `created_at`) VALUES
(1, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"www.kaolackcommune.sn\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(2, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"support@kaolackcommune.sn\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(3, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"Mairie de Kaolack\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(4, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"Dakar , Lamine Gueye\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(5, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"339009090\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(6, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"\",\"new\":\"SERIGNE MBOUP\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(7, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"khouma964@gmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_AGENT\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$9nVIOtd0LuAhZ7CeKHmJNOQDpGl4Z6mYqgH6CRJx2X2.Mz8mTdUTe\"},\"email\":{\"old\":null,\"new\":\"khouma964@gmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"f251835a2c603d04c45ecef87afe9926634c174587246e11deff824c757a4a6b\"},\"passwordClaire\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":\"Agent\"},\"nom\":{\"old\":null,\"new\":\"Khouma\"},\"dateNaissance\":{\"old\":null,\"new\":\"2000-01-01T00:00:00+01:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Dakar\"},\"adresse\":{\"old\":null,\"new\":\"Dakar\"},\"profession\":{\"old\":null,\"new\":\"ING\"},\"telephone\":{\"old\":null,\"new\":\"784537547\"},\"numeroElecteur\":{\"old\":null,\"new\":\"1209120912091\"},\"habitant\":{\"old\":null,\"new\":false},\"situationMatrimoniale\":{\"old\":null,\"new\":null},\"nombreEnfant\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(8, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '67', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(9, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"khouma964@gmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_AGENT\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$rlx8PvRfCaq0J3T4pkyzZ.GnBqHnlWSFUJDCMlMlSBl\\/x9egprRMa\"},\"email\":{\"old\":null,\"new\":\"khouma964@gmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"012afe115c225601b87dd31aabc650ed7dd56dd10a4fb663b523d32d4fb88e1d\"},\"passwordClaire\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":\"Agent\"},\"nom\":{\"old\":null,\"new\":\"Khouma\"},\"dateNaissance\":{\"old\":null,\"new\":\"2000-10-10T00:00:00+02:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Dakar\"},\"adresse\":{\"old\":null,\"new\":\"Dakar\"},\"profession\":{\"old\":null,\"new\":\"ING\"},\"telephone\":{\"old\":null,\"new\":\"784003456\"},\"numeroElecteur\":{\"old\":null,\"new\":\"25481952000933\"},\"habitant\":{\"old\":null,\"new\":true},\"situationMatrimoniale\":{\"old\":null,\"new\":null},\"nombreEnfant\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(10, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '68', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(11, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '68', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"password\":{\"old\":\"$2y$10$rlx8PvRfCaq0J3T4pkyzZ.GnBqHnlWSFUJDCMlMlSBl\\/x9egprRMa\",\"new\":\"$2y$04$aAk4\\/YJDLeSOtEmTjfW8ZODjpcT0rt1yfNrhiJeUgToOqckuejo8m\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(12, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"prix\":{\"old\":100000000000,\"new\":0}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(13, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"prix\":{\"old\":1000000000,\"new\":0}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(14, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"longitude\":{\"old\":-16.12323,\"new\":0}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(15, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"latitude\":{\"old\":14.16556,\"new\":-0.83444}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(16, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"localisation\":{\"old\":\"Ngane Alassane\",\"new\":\"\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(17, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"localisation\":{\"old\":\"\",\"new\":\"Ngane Alassane\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(18, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"description\":{\"old\":\"Lotissement sur le TF 6550 appartenant \\u00e0 l\'Etat\",\"new\":\"\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(19, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"description\":{\"old\":\"\",\"new\":\"Lotissement sur le TF 6550 appartenant \\u00e0 l\'Etat\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(20, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"statut\":{\"old\":\"achev\\u00e9\",\"new\":\"en_cours\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(21, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Lotissement', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"statut\":{\"old\":\"en_cours\",\"new\":\"acheve\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(22, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Parcelle', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"test\"},\"surface\":{\"old\":null,\"new\":0},\"statut\":{\"old\":null,\"new\":\"DISPONIBLE\"},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null},\"typeParcelle\":{\"old\":null,\"new\":null},\"tfDe\":{\"old\":null,\"new\":null},\"lotissement\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Lotissement\",\"id\":\"15\"}},\"proprietaire\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(23, NULL, NULL, 'ENTITY_DELETED', 'App\\Entity\\Parcelle', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(24, NULL, NULL, 'ENTITY_DELETED', 'App\\Entity\\TitreFoncier', '1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(25, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"prix\":{\"old\":10000000000,\"new\":0}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(26, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Localite', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"prix\":{\"old\":10000000000,\"new\":0}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(27, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '58', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"roles\":{\"old\":[\"ROLE_AGENT\"],\"new\":[\"ROLE_ADMIN\"]}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(28, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"roles\":{\"old\":[\"ROLE_AGENT\"],\"new\":[\"ROLE_ADMIN\"]}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(29, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '56', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"roles\":{\"old\":[\"ROLE_AGENT\"],\"new\":[\"ROLE_ADMIN\"]}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(30, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '55', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"roles\":{\"old\":[\"ROLE_AGENT\"],\"new\":[\"ROLE_ADMIN\"]}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(31, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"roles\":{\"old\":[\"ROLE_AGENT\"],\"new\":[\"ROLE_ADMIN\"]}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(32, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"serigne.mboup@ccbm.sn\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_MAIRE\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$KV\\/z9zG4qccFEtDgbbCD3OT2O1IL4yMai6qjidbU0XG0Lj8mm2lqC\"},\"email\":{\"old\":null,\"new\":\"serigne.mboup@ccbm.sn\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"d97fb5df7dfc4ce125f301c3dacabcb74fe15a20b2c664af148f167306e2387a\"},\"passwordClaire\":{\"old\":null,\"new\":\"QpqRDxL1\"},\"prenom\":{\"old\":null,\"new\":\"Serigne\"},\"nom\":{\"old\":null,\"new\":\"Mboup\"},\"dateNaissance\":{\"old\":null,\"new\":\"2000-09-30T00:00:00+02:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"KAOLACK\"},\"adresse\":{\"old\":null,\"new\":\"Kaolack\"},\"profession\":{\"old\":null,\"new\":\"Maire\"},\"telephone\":{\"old\":null,\"new\":\"776388473\"},\"numeroElecteur\":{\"old\":null,\"new\":\"12000000000SN\"},\"habitant\":{\"old\":null,\"new\":false},\"situationMatrimoniale\":{\"old\":null,\"new\":\"Mari\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":0},\"situationDemandeur\":{\"old\":null,\"new\":\"Propri\\u00e9taire\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(33, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\DemandeTerrain', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"Attribution\"},\"superficie\":{\"old\":null,\"new\":300},\"usagePrevu\":{\"old\":null,\"new\":\"HABITATION\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":true},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-09-30T18:15:50+02:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"document\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"CNI\"},\"recto\":{\"old\":null,\"new\":null},\"verso\":{\"old\":null,\"new\":null},\"typeTitre\":{\"old\":null,\"new\":\"Bail communal\"},\"terrainAKaolack\":{\"old\":null,\"new\":true},\"terrainAilleurs\":{\"old\":null,\"new\":true},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"57\"}},\"localite\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"3\"}},\"niveauValidationActuel\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(34, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"L\\u00e9ona\"},\"prix\":{\"old\":null,\"new\":1000000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":14.1754},\"longitude\":{\"old\":null,\"new\":16.075}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(35, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 42\"},\"superficie\":{\"old\":null,\"new\":2024},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(36, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 44\"},\"superficie\":{\"old\":null,\"new\":2305},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(37, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 78\"},\"superficie\":{\"old\":null,\"new\":4275},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(38, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 219\"},\"superficie\":{\"old\":null,\"new\":1315},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(39, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 477\"},\"superficie\":{\"old\":null,\"new\":1852},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(40, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 912\"},\"superficie\":{\"old\":null,\"new\":12593200},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"5\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(41, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 913\"},\"superficie\":{\"old\":null,\"new\":7065000},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"5\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(42, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2084\"},\"superficie\":{\"old\":null,\"new\":564467},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"3\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(43, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2153\"},\"superficie\":{\"old\":null,\"new\":2506},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(44, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"khoumatest@yopmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_DEMANDEUR\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$PfvFXvUhstgYktS6VkgT8upZ9RSr.cqq.N8HKrA77QHF4RQREyABe\"},\"email\":{\"old\":null,\"new\":\"khoumatest@yopmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"60dfb18fb69aba91ea9ea3683265eb545c645d81848cabdcf25e37dfcd54257a\"},\"passwordClaire\":{\"old\":null,\"new\":\"password\"},\"prenom\":{\"old\":null,\"new\":\"Al husseinTest\"},\"nom\":{\"old\":null,\"new\":\"KhoumaTest\"},\"dateNaissance\":{\"old\":null,\"new\":\"2000-10-10T00:00:00+02:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Dakar\"},\"adresse\":{\"old\":null,\"new\":\"Dakar\"},\"profession\":{\"old\":null,\"new\":\"PDG\"},\"telephone\":{\"old\":null,\"new\":\"781001010\"},\"numeroElecteur\":{\"old\":null,\"new\":\"2312091092993\"},\"habitant\":{\"old\":null,\"new\":null},\"situationMatrimoniale\":{\"old\":null,\"new\":\"Mari\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":0},\"situationDemandeur\":{\"old\":null,\"new\":\"Propri\\u00e9taire\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(45, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\DemandeTerrain', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"Attribution\"},\"superficie\":{\"old\":null,\"new\":200},\"usagePrevu\":{\"old\":null,\"new\":\"Habitation\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":true},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-10-01T14:44:56+02:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"document\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"CNI\"},\"recto\":{\"old\":null,\"new\":\"\\/home\\/c2616155c\\/public_html\\/backendgl\\/public\\/documents\\/cni-attribution-recto-khoumatest@yopmail.com.pdf\"},\"verso\":{\"old\":null,\"new\":\"\\/home\\/c2616155c\\/public_html\\/backendgl\\/public\\/documents\\/cni-attribution-verso-khoumatest@yopmail.com.pdf\"},\"typeTitre\":{\"old\":null,\"new\":null},\"terrainAKaolack\":{\"old\":null,\"new\":null},\"terrainAilleurs\":{\"old\":null,\"new\":null},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"\"}},\"localite\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"1\"}},\"niveauValidationActuel\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(46, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\DemandeTerrain', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"Attribution\"},\"superficie\":{\"old\":null,\"new\":300},\"usagePrevu\":{\"old\":null,\"new\":\"HH\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":true},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-10-01T18:21:16+02:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"document\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"CNI\"},\"recto\":{\"old\":null,\"new\":\"\\/home\\/c2616155c\\/public_html\\/backendgl\\/public\\/documents\\/cni-attribution-recto-thierno.seck@ccbm.sn.pdf\"},\"verso\":{\"old\":null,\"new\":\"\\/home\\/c2616155c\\/public_html\\/backendgl\\/public\\/documents\\/cni-attribution-verso-thierno.seck@ccbm.sn.pdf\"},\"typeTitre\":{\"old\":null,\"new\":null},\"terrainAKaolack\":{\"old\":null,\"new\":null},\"terrainAilleurs\":{\"old\":null,\"new\":null},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"66\"}},\"localite\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"13\"}},\"niveauValidationActuel\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(47, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2455\"},\"superficie\":{\"old\":null,\"new\":239},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(48, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2599\"},\"superficie\":{\"old\":null,\"new\":2115},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(49, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2664\"},\"superficie\":{\"old\":null,\"new\":11390},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(50, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2667\"},\"superficie\":{\"old\":null,\"new\":15146},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"13\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(51, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2682\"},\"superficie\":{\"old\":null,\"new\":29280},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(52, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2683\"},\"superficie\":{\"old\":null,\"new\":4312},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(53, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2920\"},\"superficie\":{\"old\":null,\"new\":300},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(54, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4019\"},\"superficie\":{\"old\":null,\"new\":779},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(55, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4060\"},\"superficie\":{\"old\":null,\"new\":19431},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(56, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4062\"},\"superficie\":{\"old\":null,\"new\":16678},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(57, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4204\"},\"superficie\":{\"old\":null,\"new\":625},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(58, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4235\"},\"superficie\":{\"old\":null,\"new\":679},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(59, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 4617\"},\"superficie\":{\"old\":null,\"new\":33565},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"7\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(60, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 7244\"},\"superficie\":{\"old\":null,\"new\":179973},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"1\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(61, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2668\"},\"superficie\":{\"old\":null,\"new\":1600},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(62, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\TitreFoncier', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"numero\":{\"old\":null,\"new\":\"TF 2684\"},\"superficie\":{\"old\":null,\"new\":9187},\"titreFigure\":{\"old\":null,\"new\":null},\"etatDroitReel\":{\"old\":null,\"new\":null},\"type\":{\"old\":null,\"new\":\"Titre foncier\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"14\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(63, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"CITE SENGHOR\"},\"prix\":{\"old\":null,\"new\":10000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(64, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"BONGRE EXTENSION\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(65, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"LYNDIANE NORD\"},\"prix\":{\"old\":null,\"new\":null},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(66, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"MEDINA FASS 1\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(67, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"MEDINA FASS 2\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(68, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"SAMA MOUSSA\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(69, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"ABBATOIRS\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(70, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"FASS CHEIKH TIDIANE\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(71, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"CITE SENGHOR 2\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(72, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"SARA NIMZATT\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(73, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"LYNDIANE JARDIN\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(74, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"KOUNDAME\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(75, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"KASSAVILLE\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(76, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"DAROU SALAM\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(77, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"SING SING TF 2084\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(78, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"MEDINA MBABA\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(79, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"SING SING LOGEMENT SOCIAL\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(80, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"titreFigure\":{\"old\":null,\"new\":[]},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"21\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(81, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Localite', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"MEDINA\"},\"prix\":{\"old\":null,\"new\":1000},\"description\":{\"old\":null,\"new\":null},\"latitude\":{\"old\":null,\"new\":null},\"longitude\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(82, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\NiveauValidation', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"VALIDATION AGENT\"},\"roleRequis\":{\"old\":null,\"new\":\"ROLE_AGENT\"},\"ordre\":{\"old\":null,\"new\":1}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(83, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\NiveauValidation', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"VALIDATION COMMISSION\"},\"roleRequis\":{\"old\":null,\"new\":\"ROLE_PRESIDENT_COMMISSION\"},\"ordre\":{\"old\":null,\"new\":2}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(84, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\NiveauValidation', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"VALIDATION \"},\"roleRequis\":{\"old\":null,\"new\":\"ROLE_CHEF_SERVICE\"},\"ordre\":{\"old\":null,\"new\":3}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(85, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\NiveauValidation', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"Monsieur le Maire\"},\"roleRequis\":{\"old\":null,\"new\":\"ROLE_MAIRE\"},\"ordre\":{\"old\":null,\"new\":4}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(86, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\HistoriqueValidation', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"action\":{\"old\":null,\"new\":\"NIVEAU_SET\"},\"motif\":{\"old\":null,\"new\":null},\"dateAction\":{\"old\":null,\"new\":\"2025-10-03T17:34:00+02:00\"},\"niveauNom\":{\"old\":null,\"new\":null},\"niveauOrdre\":{\"old\":null,\"new\":null},\"roleRequis\":{\"old\":null,\"new\":null},\"statutAvant\":{\"old\":null,\"new\":null},\"statutApres\":{\"old\":null,\"new\":null},\"request\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Request\",\"id\":\"12901\"}},\"validateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"10\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(87, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Configuration', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"valeur\":{\"old\":\"Dakar , Lamine Gueye\",\"new\":\"Kaolack \"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(88, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(89, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":true,\"new\":false}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(90, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(91, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"password\":{\"old\":\"$2y$10$KV\\/z9zG4qccFEtDgbbCD3OT2O1IL4yMai6qjidbU0XG0Lj8mm2lqC\",\"new\":\"$2y$10$TohK.B2Xh3Rv1OAbnUV\\/v.thWN0WmDFSM0ctfurkvI6RYgWaD.IBK\"},\"passwordClaire\":{\"old\":\"QpqRDxL1\",\"new\":\"serigne6619\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(92, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"password\":{\"old\":\"$2y$10$TohK.B2Xh3Rv1OAbnUV\\/v.thWN0WmDFSM0ctfurkvI6RYgWaD.IBK\",\"new\":\"$2y$04$o\\/qj6VE.Xgj.EjkaTA9R\\/e6bsNp5fObartNYG8HCQFgIqd6Q7Jdka\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(93, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '69', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"avatar\":{\"old\":null,\"new\":\"69-serigne.mboup@ccbm.sn-profile.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(94, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"cbb38f20844477ed199a4c57dbb039642daf0a94b31e6a44d367312a5173cbf382cfa048779e6e47aea72b21c21c62586ba36d195ff477657d5d8b98a9e3afd0\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T14:09:45+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(95, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"3071363b8fc0647118d6eca4b1618e620a90d703fb9f000ace459126869c5fbd0dbbfca24faa9919dba4b8cb2e0e79eec7b1d16e3b9bb6ee857f68e607bc4f84\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T14:09:45+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(96, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"f99b8dd982baf6cb49f3a3adc6d758a69cc81680d47442709808aedb41e40f8bf5703512a511534765d8aeafec55367ad1eed00888d02dc5907bba0e69703323\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T14:09:56+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(97, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"1712d63c4c3607d2b408037ab606d6ec3ec74e593cb248c226ac904bd0cc9ce6660ba09a51dd05282f852da48af1d026057e4261d58ef3d3aef1c1a04e0b39fe\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T14:09:56+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(98, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"therese@yopmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_DEMANDEUR\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$iGMxtbaAa6EYL0fRJdt6ieeMREyCgr4fawBO\\/FAr928kPfH6u5cAK\"},\"email\":{\"old\":null,\"new\":\"therese@yopmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"8801bff55966630b4cfb954ca5004a5f6ece3efe4a80cd76b54aee426b708d61\"},\"passwordClaire\":{\"old\":null,\"new\":\"password\"},\"prenom\":{\"old\":null,\"new\":\"THERESE\"},\"nom\":{\"old\":null,\"new\":\"KANE\"},\"dateNaissance\":{\"old\":null,\"new\":\"2000-01-01T00:00:00+01:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"dakar\"},\"adresse\":{\"old\":null,\"new\":\"PARCELLES ASSAINIES\"},\"profession\":{\"old\":null,\"new\":\"AGENT ADMINISTRATIF\"},\"telephone\":{\"old\":null,\"new\":\"775490000\"},\"numeroElecteur\":{\"old\":null,\"new\":\"121212121212212\"},\"habitant\":{\"old\":null,\"new\":false},\"situationMatrimoniale\":{\"old\":null,\"new\":\"C\\u00e9libataire\"},\"nombreEnfant\":{\"old\":null,\"new\":0},\"situationdemande_demandeurur\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":\"H\\u00e9berg\\u00e9(e)\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(99, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Request', '12863', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(100, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/26\\/Titre foncier-20251017141213-8bc4cd84.pdf\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(101, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"343f0cc9fe2c3507e39a870a3223368cc641196f9f110702acc6873d81c5d1a626ca7c5ebd7d445fdb4c00378cfd95edbfd4a79c6da296db850731fa953caf2e\"},\"username\":{\"old\":null,\"new\":\"barcheikh@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T16:43:36+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(102, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"56cf22ed0250d514e288514032cf68c8cf73f75abfed8da5b8daaefacd26ee9833d2d7b550e002b484f76cb8947631e42e10fc584c4262a923bf4a1eab9c16c3\"},\"username\":{\"old\":null,\"new\":\"barcheikh@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-16T16:43:36+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(103, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Request', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"Attribution\"},\"superficie\":{\"old\":null,\"new\":300},\"usagePrevu\":{\"old\":null,\"new\":\"HABITATION\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":false},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-10-17T16:56:06+02:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"CNI\"},\"recto\":{\"old\":null,\"new\":null},\"verso\":{\"old\":null,\"new\":null},\"typeTitre\":{\"old\":null,\"new\":\"Permis d\'occuper\"},\"terrainAKaolack\":{\"old\":null,\"new\":false},\"terrainAilleurs\":{\"old\":null,\"new\":false},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":\"CHEIKH\"},\"nom\":{\"old\":null,\"new\":\"BAR\"},\"dateNaissance\":{\"old\":null,\"new\":\"1979-01-11T16:56:06+01:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"RUFISQUE\"},\"adresse\":{\"old\":null,\"new\":\"KAOLACK\"},\"profession\":{\"old\":null,\"new\":\"AGENT MUNICIPAL\"},\"telephone\":{\"old\":null,\"new\":\"781250219\"},\"numeroElecteur\":{\"old\":null,\"new\":\"1770197900079\"},\"habitant\":{\"old\":null,\"new\":null},\"email\":{\"old\":null,\"new\":\"barcheikh@gmail.com\"},\"situationMatrimoniale\":{\"old\":null,\"new\":\"Mari\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":2},\"statutLogement\":{\"old\":null,\"new\":\"Locataire\"},\"localite\":{\"old\":null,\"new\":\"SING SING TF 2084\"},\"numero\":{\"old\":null,\"new\":\"DP202510171656\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"29\"}},\"utilisateur\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(104, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '57', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"situationMatrimoniale\":{\"old\":null,\"new\":\"Mari\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":2},\"situationDemandeur\":{\"old\":null,\"new\":\"Locataire\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(105, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\Request', '12913', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"57\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(106, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"e86c01f2790ad7e9f14b6b781f4503e6ff10a60ab30e4a51b9ac0969a7ba5d7628ae185be17eed4746313a3988aa913771f976b1d6f7fe0f079a9214ef74eaf5\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-17T16:50:47+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(107, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"c01edd3e9fa667064cc3724d0088663ed08b1853b97ed670e6c69b06ae63afe7431ecad8673a4a1b0a2b82d5612731c2b563cae8f02ff0241f85620894e603aa\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-17T16:50:47+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(108, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"4cfc66201c219f65968010957785ae6570995d0cd9374ada1352f48f537c168aa4a24b7bba55ae91f44e255a331ca6cd19ebb591686f5307026d847d1fc11a56\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-19T12:25:48+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(109, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"011bf0fe675c1a373786a05c7462c31e6618717e4b5c139cafbf04f42b06220d7310998a21a2b486e4c7be2ef2dacdd8da975c86c73bd0be95506a07046a0c5c\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-19T12:25:48+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00');
INSERT INTO `gs_mairie_audit_log` (`id`, `actor_id`, `actor_identifier`, `event`, `entity_class`, `entity_id`, `http_method`, `route`, `path`, `ip`, `user_agent`, `request_id`, `correlation_id`, `payload`, `changes`, `metadata`, `status`, `message`, `created_at`) VALUES
(110, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"5f8d60932bdf246ce1fdc19b01e09aaa3aa23e93d379338651987301457b2ee458cbbfdc9fab838dd802172300fc9fddae6e683914a49c338b4536cf8ce49077\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:02:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(111, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"269fbe0a248d5d2e4adb5316dabd10c7262c6b07bf83e9abca4e955356d72f16f3ea477c1ad25b023521ee688fd88000530dbe4a278502fd2cee9705cbb662cb\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:02:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(112, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"893ca4b4abf5af042a3256767eb598e1192d3e126c7d796985dfb79b4240d2c5f30bc262d5321b2db15c8fe5633fbe455f6dc69edc64c5a9b859dc93e155373b\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:09:06+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(113, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"6288ce9e43f20e06a8d80bd113e4c93297643b1ce81b00537f6ca520c4f7232c313ce6ce3f7fbe3ae549214d8053816586362adeaa03a7214eebe911fc30e0fd\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:09:06+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(114, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"27a50572350cdf05873a85bb83e6aab6dea924902cd86b0f52fa1cda1ab26271c5612e74e4547add931a555015c0655ef79ac99ad4319c230682c0305977cfce\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:09:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(115, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"5ec4d766e16c4796f0476b332004c058cb6bbd91f4180fe46bec1cb8a722c90c11ceeb4acfdcdcf9db9f0891f474b27fec575f1adcda96dd77dd4dcbabaa6b26\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:09:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(116, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"2f5ecc4dc4b4104f51e8d8d54a75b16491d1a6806320751c5b700a7c759c19fa21f9d30102db248a076120031d36c5286b558749d7632de6cb4ecee442aa5ce1\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:11:36+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(117, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"daa95fcb84a88785e4028ab4377c68e29ac389ec54701d9594a43ed34904df87b251a6341a59a6cc69e3eadd5020ccac13290bf9351317eddfc6e7b098c0e235\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-20T12:11:36+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(118, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"54297e1c031b460e236c6b941480ac05b562caa3851f2e4aa3bfd74c27d087c1fcd4e8760f630a26e3e98e97e40171ea45331b4c7ef3223238ce2d6fe9ca9f68\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-22T12:01:15+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(119, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"022dcbba7866151175ec4399a66e1ebdfc4f935cd1f73be03820867ab8b4fd30314c0a0dbbbf85530380e0251184b2449db8722db48172602d10d85c98b2a3a4\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-22T12:01:15+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(120, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"31f8344f1203d5472ce64e4362de708607da245a57d953ab2eff56b943b66ff5299aefd909035f5becec2117c18e01b204ceef74e7c0afc047986197204773a4\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-23T11:52:45+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(121, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"e5798a444f458e23e8903128b0191cc617496a51625cd28bca300fe1e2502d393ece861cf89576b4ea50d6206e0e978b21721d287211358a493e2377836c86a7\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-23T11:52:45+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(122, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"a13fe355f433a91f244da00d97c60da5dcab1b7241153285840cea564e08e36f9707d30718fecf725d4058cba054e4072ad0716baa54a6cee50f12cc7fc79906\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-29T10:29:04+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(123, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"3e7e71b1fd97a7b45e4765a3f3d083258adaecb7fdcf91c136cfa5aa48c9e31762b7ea2d67dbf9848145166ff4ceca3ac671e3fc54df54a98f2c347639073af1\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-29T10:29:06+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(124, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(125, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(126, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"f10de999bea288bda5d6f7e6d8dbdf72e87054243d0f93692f4d69696728ae4b1d104e50184920dba9713dd166cdc826e4aea5d22dfa05b9553f0c15cda3d6f3\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T09:09:41+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(127, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"cd850fef2750edc81b70a242ca1d0c1cf3729abc772f12906a6bbf9684b97b7441f2de77825a27d870cd8c0c12cf13cffaf8a708a93fe930b0d08bd1649d59e7\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T09:09:41+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(128, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"6002de1c7c815254182129d3fcb90c3dff823be8fbfe683ba178ff7c993fde9a1b4f4563dc7c4b4cdcaea9f5afe022ff785cc5ea3a2cbf578e369591186f8c57\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T10:12:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(129, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"878c4696d269baff0aba324295d7eafaf462d329899886fe0d88986cc1ac709744f9784566213956e8d85b9f5f53cb8bea830992857d0b5a64675d1d56c917b0\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T10:12:32+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(130, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '26', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/26\\/Titre foncier-20251031101330-92675d2e.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(131, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '25', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/25\\/Titre foncier-20251031101356-d4970376.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(132, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '24', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/24\\/Titre foncier-20251031101419-019f7e21.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(133, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '23', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/23\\/Titre foncier-20251031101446-4006e240.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(134, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '22', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/22\\/Titre foncier-20251031101503-7ca9bd55.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(135, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '21', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/21\\/Titre foncier-20251031101528-23a8eed7.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(136, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '20', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/20\\/Titre foncier-20251031101540-d94af4ec.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(137, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '19', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/19\\/Titre foncier-20251031101555-b8c2b4db.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(138, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '18', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/18\\/Titre foncier-20251031101612-1ef0881f.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(139, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '17', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/17\\/Titre foncier-20251031101627-63fe267c.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(140, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '16', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/16\\/Titre foncier-20251031101649-92677a10.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(141, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '15', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/15\\/Titre foncier-20251031101712-00a63686.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(142, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '14', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/14\\/Titre foncier-20251031101732-044828ef.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(143, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '13', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/13\\/Titre foncier-20251031101757-57f65ef7.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(144, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '12', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/12\\/Titre foncier-20251031101816-b010b23b.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(145, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '11', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/11\\/Titre foncier-20251031101836-8d3fc357.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(146, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '10', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/10\\/Titre foncier-20251031101853-7999b869.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(147, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '9', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/9\\/Titre foncier-20251031101906-643140c7.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(148, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '8', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/8\\/Titre foncier-20251031101921-e81c757a.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(149, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '7', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/7\\/Titre foncier-20251031101936-a7f9fed1.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(150, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '6', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/6\\/Titre foncier-20251031101953-b484cf4b.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(151, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '5', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/5\\/Titre foncier-20251031102028-2958122b.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(152, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '4', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/4\\/Titre foncier-20251031102041-4e0bcb58.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(153, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '3', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/3\\/Titre foncier-20251031102057-8c235fa3.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(154, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\TitreFoncier', '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"etatDroitReel\":{\"old\":null,\"new\":\"\"},\"fichier\":{\"old\":null,\"new\":\"\\/uploads\\/titres\\/2\\/Titre foncier-20251031102130-81828104.png\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(155, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"c4410ae61502d1f10e55ea6bd2fed5a97d67f259472e3c079f569f0a7e4d113482633820febbb9355f04ba25669185291dd571ffd87c4c110daf4d60698230f9\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T16:12:52+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(156, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"b18378d5a25f70629399a6a684c3bd5d3a7595400e41e8e3e6bb542c9ddd6fe1e780de37eaf5498640d3716076d8ff96b73b5759e6c62e232092c65aa51ffdbc\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-11-30T16:12:52+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(157, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"bbe79d84c0449f768f840648126c239462dfa8044096822ee00825f7735564ac6f3082f70341ad233b08fb2c5fa45a4cb7cb93cb5692641c1688bcd892f41a4f\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-02T21:48:37+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(158, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"b3f9f5478b0909daf6603fa7c4e93da0d9b14ec2b7419cb75ca4421960305ce21bffc47729598ec96d4bd7a9c506d63d55601aefdce9a55e6ab76cb660ed84e2\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-02T21:48:37+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(159, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"d7adc3c1d58343bc9d3bfe1b17a21ff03be8477b518d50c07b338c0c636c6ad4dc707b3da02816f3a40fe799b9fcb011e87fa4db4937574808f88daea613e823\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-03T10:05:37+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(160, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"83375b55734ff5eb0b9f69f5c6602cf94a8d88117883260d7b074b79013cf127ca51ffa852650a977d03ce68536cbbc4c797b32bdc661e729808067c48e6cd32\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-03T10:05:37+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(161, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"81a46e757916d4be21c95be9adf5f2c7ab0450356fb1365ecc50bf2000670b83ab41be797fd7e820a9eb6c26542879b0d6393dfe43c57ecac23e35c8e45e0e37\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-03T10:21:23+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(162, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"b59f97ce55d89456b41fd7e15e6d0d726dbf2746fd9a03a25ccb86aaf5562876337c6543ecfbfc980088d1b473a85aa7d58a01ff2210f599df590f3d4f378277\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-03T10:21:23+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(163, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"ce354cb4b36259a2994e55f11b6a2afc0c0ff6c05d97c5724ea27839fb62dd614781e7168998097c7afa4cd5af5f5028db8980ca0d106e59ba3ee4592290f434\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-07T11:37:05+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(164, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"feece5ec23c56f3a4dbf60967056abf6f61adc9cf11338c9bfc0605efe7c4c1bc85a2a3f023d80071807ccab194098a0b075db515eb972fd49c955bc8828391d\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-07T11:37:05+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(165, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"2c351600b6778107fa22d8459816930cb14f2c9f75447e7d3cd1f8e7b5fef3493bb9026491f6a6a01eb900a34bf325849623c9955f05e8cc0fb5a6d1584a4cdf\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-07T11:42:25+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(166, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"90eb7c8d70feafb26401976352c53cf5a9faed5b7908f67a85f4e06e3650f4a472afa4c9438da817e91e8141e263bf8c6cb76279f5e19a2d9df7cfd08b64cfab\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-07T11:42:25+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(167, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"35dc7eb0d59dd277fed3ead846ab69b90887855cd5538b1f16c7333f864a2c70295c6db44c78761b49f1b4a44298a534b5ab9bd12c84a97ace091d1386f469f2\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-10T11:26:33+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(168, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"e4d4e383b0303dc870a59ee025f203358156d4badb3ee700b11d3ab2a3f88750da5e773535f3ef320a05c58f2e4904facc0a505cf03f1889fe7c04f0c7273c4d\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-10T11:26:33+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(169, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"ad0e1291ac79c7b07b6eff988475506d0c70513faed21690efdec9d5a45f85b977e6b920ac707795362e29a7d77d81bcd7c183d9d39ad902372c372847d706b0\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-10T12:54:46+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(170, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"18d1d883ca72727f9669d5513106a147aca018d10a93f288b72ffe810810f0380ce365f990c279a2e047bd0f5eb8b2beadafdbc9df5da67fbef3633908c29a93\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-10T12:54:46+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(171, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\ContactMessage', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"nom\":{\"old\":null,\"new\":\"Alioun\"},\"email\":{\"old\":null,\"new\":\"aejqUFJKN@ZSFJ.Fr\"},\"telephone\":{\"old\":null,\"new\":\"+221 77 123 45 67\"},\"categorie\":{\"old\":null,\"new\":\"DEMANDE_PARCELLE\"},\"reference\":{\"old\":null,\"new\":null},\"message\":{\"old\":null,\"new\":\"Q.DFSNK\"},\"consent\":{\"old\":null,\"new\":true},\"pieceJointe\":{\"old\":null,\"new\":null},\"createdAt\":{\"old\":null,\"new\":\"2025-11-13T13:04:42+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(172, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"3fa1d0d6acaae6c6dccb41783a7d873d304b2249af1dd3d469120daa9f2aecbc5974642135ed6f1ac514ea3a67b1987a9d9986eaf14f91822a06be78bc54efdb\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:12:14+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(173, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"459ea9d53c6d0beafe000e2a1a132580554fb8d3115d72729553ec7db505e1b3e6caaca7655e53ce4def4f4270219fa423194f57025db077b6117af66ff4611e\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:12:14+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(174, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"bd64df768b7e765852a24a241162eb17883404c8dae5c3af738d07c0c370fe24d35d2d1e248bc8bce0be9b77fdad8b7bf4cd945656879ec3b99a63ae377c1e1e\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:38:04+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(175, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"d2ab4568c349baa864663f889bc418eef3eec290a05f11a0fda0dd9c085d4244e1f12e6ded403f2e6b3b93ea1f6d9a814aef47f08d43ed96c84a8ef448b11424\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:38:04+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(176, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"78e7c2000876d65b7ecb4e7d12dd15752b7d4711e492aa84c98f5f4e5bb3141491c3bf9f2d900682ddf945ecfd1c00181893aa8f12d07aaf418cc5d1b0de15c0\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:40:14+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(177, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"62bd9665cd299eaa692591bea34f6c4d30f06d877ababa86481e7e6604c1a680bd7b34852eff88fb78f2c1f303ac0a09d01df9facd96da95b7cb045c9f3483a2\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:40:14+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(178, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"952b009ba6269a622d027bd80a029863c1d4dc8183d6224f73b4e83e32497143f6027e4993c8c6abf8338ccd6007c025f3542fc800ad49c2138e9f0d6de27886\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:42:57+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(179, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"e6264ce14796fda7a5f8e254ca6aa87d631725e2e8406f3fb63723c35241e016fbc06c8d8a0b112382da1d33ca025ea80a28bfe8b753190a4f30b2dd098701ee\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:42:57+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(180, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"75f00830421356798b4b396034aa2fdec8d2e582aea8e87dc65203941a9a647ec02b07eb5fe216cb2de039aef8e7809aff9e5d90eb534b48c08375e9c5f2a992\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:46:25+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(181, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"cf41c8822442b491f5be8565444911afbc5dc53c60a0f11cf1f29130e4f21b70bafb75fd6723433c344d26a63634d4d98a4305233a4e1d295575083d8f6867a1\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:46:25+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(182, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"c51d95f0ffdb38f58374bc8a75933d9ec0cb1c3f3a2cd7edcf3d080cb3ac9972a7b39c58613959bf5befadadd55b1b9b83ba90dea8597a48ee336c564dcb51e1\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:47:44+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(183, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"ad65f70a3d1732babad6c0de0784f692fd80f70d0990552f3ab3b16e5c69574c89e1b110d8c3b3f1e155a503225e41c319964d686ec52f22fd90dc07441b3168\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-13T22:47:44+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(184, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"913eea3b7fffce338aac58e9c57f7a82b7d284ee36fb534edb87aae772923cee3e73b98c340a133f3733eb854777609ff0727c237329ef2b3fb752d62897f4d5\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-14T10:14:42+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(185, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"caefda8b17795ff368cb90af3568129b8a525a09a03ce8d191fb3412ff0bdb01b50a9a3d97c79d18dd981206326c0a4ceaa32285647c0fa389141183f2bf5f95\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-14T10:14:42+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(186, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"d78fb726342410d0345681352e535f5c1866869f529ca1157942d57e5fdbe1e5e24e822ac6f7b41934ac4d02ce6dd4a5b451474206781c43f0354e2e33a8a38e\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-14T11:20:21+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(187, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"ba0c2573e57021990beea71c2978629453d1490c11f64243ea21601e4590020fe6714b4942ef3562a352bdda36f2e6420d568c31e3972ebfb6f8c0495425dbb4\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-14T11:20:21+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(188, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"d4180e3293304f34dc30ac5638c1f181aba35df5dd78c59efc0778beb1600fb6d5a0e2b35ddd0a39f3096df05121402b2d93a4811ccac749aaa14bf594d8af3b\"},\"username\":{\"old\":null,\"new\":\"admin@admin.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-14T11:29:13+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(189, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"2a25769d339dc1b0ee13742d1eaf386a0c4960a87f61eac3a9d8cf46840c4cfde6a0bc8d36c6d58ae5bf753b29d90c62248cc6d1e5d41d1ca4f6280dfb8b9c59\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-19T10:14:13+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(190, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"3f9d6c5ffb9f03bc4fa4fbaa6f65cf053aaa20fe8909a49393b1af7589eb610f9df861edc4303a3d132f069a995264b4e81a5ad39dd1f9fe8bed0ae92f6db32f\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2025-12-19T10:14:13+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(191, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"alhusseinkhouma0@gmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_DEMANDEUR\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$CzO3X3Ioby8AaM4TVzIc6.tByQC0nFaa3I.M17j4Us24tzb4WOLSi\"},\"email\":{\"old\":null,\"new\":\"alhusseinkhouma0@gmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"48fb86078e2a7ec8aedd1ed36f7b64fe93a94dba77bed071b07e774315569ce4\"},\"passwordClaire\":{\"old\":null,\"new\":\"Password123!\"},\"prenom\":{\"old\":null,\"new\":\"Al hussein\"},\"nom\":{\"old\":null,\"new\":\"Khouma\"},\"dateNaissance\":{\"old\":null,\"new\":\"1999-11-25T00:00:00+01:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Dakar\"},\"adresse\":{\"old\":null,\"new\":\"Dakar\"},\"profession\":{\"old\":null,\"new\":\"Informaticien\"},\"telephone\":{\"old\":null,\"new\":\"784537547\"},\"numeroElecteur\":{\"old\":null,\"new\":\"18701999010101\"},\"habitant\":{\"old\":null,\"new\":null},\"situationMatrimoniale\":{\"old\":null,\"new\":\"Mari\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":0},\"situationdemande_demandeurur\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":\"H\\u00e9berg\\u00e9(e)\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(192, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Request', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"R\\u00e9gularisation\"},\"superficie\":{\"old\":null,\"new\":123},\"usagePrevu\":{\"old\":null,\"new\":\"HABITATION\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":true},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-11-25T10:26:04+01:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"cni\"},\"recto\":{\"old\":null,\"new\":\"\\/documents\\/cni-r--gularisation-recto-alhusseinkhouma0atgmail.com.jpg\"},\"verso\":{\"old\":null,\"new\":\"\\/documents\\/cni-r--gularisation-verso-alhusseinkhouma0atgmail.com.jpg\"},\"typeTitre\":{\"old\":null,\"new\":\"Bail communal\"},\"terrainAKaolack\":{\"old\":null,\"new\":false},\"terrainAilleurs\":{\"old\":null,\"new\":true},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":null},\"nom\":{\"old\":null,\"new\":null},\"dateNaissance\":{\"old\":null,\"new\":null},\"lieuNaissance\":{\"old\":null,\"new\":null},\"adresse\":{\"old\":null,\"new\":null},\"profession\":{\"old\":null,\"new\":null},\"telephone\":{\"old\":null,\"new\":null},\"numeroElecteur\":{\"old\":null,\"new\":null},\"habitant\":{\"old\":null,\"new\":null},\"email\":{\"old\":null,\"new\":null},\"situationMatrimoniale\":{\"old\":null,\"new\":null},\"nombreEnfant\":{\"old\":null,\"new\":null},\"statutLogement\":{\"old\":null,\"new\":null},\"localite\":{\"old\":null,\"new\":\"DAROU SALAM\"},\"numero\":{\"old\":null,\"new\":\"DP202511251026\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"28\"}},\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(193, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"a1842d24c02daf9894036024c8e8616600140f6515f51a36b430d6bc810aff389f1763b26db3412dc1eba96d75a29d43dfcd7f4871aa559bbc659012f0ef8d12\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-29T12:05:05+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(194, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"5b040870c6a11dd58670ca65e14d76a28430374d4c0d19af35c03ef98ee212ae0addc35a94725adfa635cb16aab07f86185754c801d17507e2c24c05ac80a5db\"},\"username\":{\"old\":null,\"new\":\"salioumbayegee24@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2025-12-29T12:05:05+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(195, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_DEMANDEUR\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$\\/Yi484lsw9MsfUEQaYfkS.LblFIAzePn12bQKbZfRvpvNASr2iGgi\"},\"email\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"3cc9277c0bc8706a72c42c525f4d7b26cdc6580fbfa9b670ec462fe7bc8b3502\"},\"passwordClaire\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":\"Aminata\"},\"nom\":{\"old\":null,\"new\":\"Mbow\"},\"dateNaissance\":{\"old\":null,\"new\":\"2007-07-11T00:00:00+00:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Kaolack\"},\"adresse\":{\"old\":null,\"new\":\"kaolack\"},\"profession\":{\"old\":null,\"new\":\"Etudiant\"},\"telephone\":{\"old\":null,\"new\":\"787365355\"},\"numeroElecteur\":{\"old\":null,\"new\":null},\"habitant\":{\"old\":null,\"new\":false},\"situationMatrimoniale\":{\"old\":null,\"new\":null},\"nombreEnfant\":{\"old\":null,\"new\":0},\"situationdemande_demandeurur\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":null}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(196, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"cfe61c0ec862b1876c140ce2d8c9b1aea8ce5a11144d59a17fee511b142e7851028b0b77daf6b710c5d4061122893a518642cad652950e77ea99291428227611\"},\"username\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2026-01-03T20:31:04+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(197, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"0db44335e3ea8b0ac95b59ca2d31fab1c483d09763e3ce7dd0bcd25a89cc41f18a4f1258f389d09b1a54922140397eba05cc811d803acbcd261f5b1563e5a2af\"},\"username\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2026-01-03T20:31:04+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(198, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '73', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"password\":{\"old\":\"$2y$10$\\/Yi484lsw9MsfUEQaYfkS.LblFIAzePn12bQKbZfRvpvNASr2iGgi\",\"new\":\"$2y$04$oxgWaMo2ZpgDhKZg4Q\\/A4OX8p\\/eg8zQp3xIQ1MFpRFY4ucDmHKJVW\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(199, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '73', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(200, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"b97d4ae4e6cd595af96406d92d99fd4198910a274a913f5c3213106a62eb1f42c99efca0a986005395686b4732751251cca93364547c597dc8d60b0dfad57f1e\"},\"username\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2026-01-03T20:31:38+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(201, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"8d8183d2183d0532c5b34e0925c5f996bbb2f28a248eaa46eda7d0d138598e9fde6fda0399b52e3e7d9ddd6280231cc6277924931d6495596ed2a64f19c7c0ee\"},\"username\":{\"old\":null,\"new\":\"coleamymbow@gmail.com\"},\"valid\":{\"old\":null,\"new\":\"2026-01-03T20:31:38+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(202, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\User', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"username\":{\"old\":null,\"new\":\"nougatine77@free.fr\"},\"roles\":{\"old\":null,\"new\":[\"ROLE_DEMANDEUR\"]},\"password\":{\"old\":null,\"new\":\"$2y$10$rxCJHqqbEGnDhlwNrmo02.\\/cWt1meQomvBHJ2k5AlS1VWyMiSuI.q\"},\"email\":{\"old\":null,\"new\":\"nougatine77@free.fr\"},\"avatar\":{\"old\":null,\"new\":null},\"reset_token\":{\"old\":null,\"new\":null},\"reset_token_expired_at\":{\"old\":null,\"new\":null},\"enabled\":{\"old\":null,\"new\":true},\"activeted\":{\"old\":null,\"new\":false},\"tokenActiveted\":{\"old\":null,\"new\":\"14af15b7aa070896d2a8daebca740b0e50865110172473e1dbb50c2ec40ea2b5\"},\"passwordClaire\":{\"old\":null,\"new\":\"Password123!\"},\"prenom\":{\"old\":null,\"new\":\"AMSATOU\"},\"nom\":{\"old\":null,\"new\":\"BADIANE\"},\"dateNaissance\":{\"old\":null,\"new\":\"1959-10-23T00:00:00+01:00\"},\"lieuNaissance\":{\"old\":null,\"new\":\"Kaolack \"},\"adresse\":{\"old\":null,\"new\":\"Parcelle 3221 ndorong kaolack \"},\"profession\":{\"old\":null,\"new\":\"Retraitee\"},\"telephone\":{\"old\":null,\"new\":\"786852367\"},\"numeroElecteur\":{\"old\":null,\"new\":\"5910232F3112261\"},\"habitant\":{\"old\":null,\"new\":null},\"situationMatrimoniale\":{\"old\":null,\"new\":\"Divorc\\u00e9(e)\"},\"nombreEnfant\":{\"old\":null,\"new\":5},\"situationdemande_demandeurur\":{\"old\":null,\"new\":null},\"situationDemandeur\":{\"old\":null,\"new\":\"Propri\\u00e9taire\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(203, NULL, NULL, 'ENTITY_CREATED', 'App\\Entity\\Request', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"typeDemande\":{\"old\":null,\"new\":\"Authentification\"},\"superficie\":{\"old\":null,\"new\":200},\"usagePrevu\":{\"old\":null,\"new\":\"Habitation\"},\"possedeAutreTerrain\":{\"old\":null,\"new\":true},\"statut\":{\"old\":null,\"new\":\"En attente\"},\"motif_refus\":{\"old\":null,\"new\":null},\"dateCreation\":{\"old\":null,\"new\":\"2025-12-05T21:32:53+01:00\"},\"dateModification\":{\"old\":null,\"new\":null},\"typeDocument\":{\"old\":null,\"new\":\"CNI\"},\"recto\":{\"old\":null,\"new\":\"\\/documents\\/cni-authentification-recto-nougatine77atfree.fr.jpg\"},\"verso\":{\"old\":null,\"new\":\"\\/documents\\/cni-authentification-verso-nougatine77atfree.fr.jpg\"},\"typeTitre\":{\"old\":null,\"new\":\"Transfert d\\u00e9finitif\"},\"terrainAKaolack\":{\"old\":null,\"new\":false},\"terrainAilleurs\":{\"old\":null,\"new\":true},\"decisionCommission\":{\"old\":null,\"new\":null},\"rapport\":{\"old\":null,\"new\":null},\"recommandation\":{\"old\":null,\"new\":null},\"prenom\":{\"old\":null,\"new\":null},\"nom\":{\"old\":null,\"new\":null},\"dateNaissance\":{\"old\":null,\"new\":null},\"lieuNaissance\":{\"old\":null,\"new\":null},\"adresse\":{\"old\":null,\"new\":null},\"profession\":{\"old\":null,\"new\":null},\"telephone\":{\"old\":null,\"new\":null},\"numeroElecteur\":{\"old\":null,\"new\":null},\"habitant\":{\"old\":null,\"new\":null},\"email\":{\"old\":null,\"new\":null},\"situationMatrimoniale\":{\"old\":null,\"new\":null},\"nombreEnfant\":{\"old\":null,\"new\":null},\"statutLogement\":{\"old\":null,\"new\":null},\"localite\":{\"old\":null,\"new\":\"NDORONG\"},\"numero\":{\"old\":null,\"new\":\"DP202512052132\"},\"quartier\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\Localite\",\"id\":\"11\"}},\"utilisateur\":{\"old\":null,\"new\":{\"_class\":\"App\\\\Entity\\\\User\",\"id\":\"\"}}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(204, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '74', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"activeted\":{\"old\":false,\"new\":true}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(205, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '74', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"reset_token\":{\"old\":null,\"new\":\"T_z_AkJXSgx_tkpWYthqZMwSmfvDwEHmcyHP87ze948\"},\"reset_token_expired_at\":{\"old\":null,\"new\":\"2025-12-11T11:37:40+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(206, NULL, NULL, 'ENTITY_UPDATED', 'App\\Entity\\User', '74', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"reset_token\":{\"old\":\"T_z_AkJXSgx_tkpWYthqZMwSmfvDwEHmcyHP87ze948\",\"new\":\"uCUW0bUKxDPQPU9nAT4SICMziSevfVmc1_pEhRGJTqI\"},\"reset_token_expired_at\":{\"old\":\"2025-12-11T11:37:40+01:00\",\"new\":\"2025-12-12T10:29:14+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(207, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"1aa4d54b99795c031645720352127423f1967597a819a5e1231d4b8af95e20d0826a98b5f0fb162d28280e60a77cbc129eda665d6ff638ff4ebaa321c3e7ab2e\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2026-02-05T10:29:06+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00'),
(208, NULL, NULL, 'ENTITY_CREATED', 'Gesdinet\\JWTRefreshTokenBundle\\Entity\\RefreshToken', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '{\"refreshToken\":{\"old\":null,\"new\":\"585fe3ad942ce9818a1aba3a3f01b974547e294c68c894eac90476e2601044dc0a6bc1cbeb0230c201e606f4bbd7381ad74ee324d6882ebf9836e7a1d6a7974e\"},\"username\":{\"old\":null,\"new\":\"sallass77@hotmail.fr\"},\"valid\":{\"old\":null,\"new\":\"2026-02-05T10:29:06+01:00\"}}', NULL, 'SUCCESS', NULL, '0000-00-00 00:00:00');

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_categories_terrains`
--

DROP TABLE IF EXISTS `gs_mairie_categories_terrains`;
CREATE TABLE IF NOT EXISTS `gs_mairie_categories_terrains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_configurations`
--

DROP TABLE IF EXISTS `gs_mairie_configurations`;
CREATE TABLE IF NOT EXISTS `gs_mairie_configurations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `valeur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cle` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_configurations`
--

INSERT INTO `gs_mairie_configurations` (`id`, `valeur`, `cle`) VALUES
(1, 'Mairie de Kaolack', 'titre'),
(2, 'Kaolack ', 'adresse'),
(3, '339009090', 'telephone'),
(4, 'www.kaolackcommune.sn', 'siteWeb'),
(5, 'support@kaolackcommune.sn', 'email'),
(6, 'SERIGNE MBOUP', 'nomMaire');

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_demande_terrains`
--

DROP TABLE IF EXISTS `gs_mairie_demande_terrains`;
CREATE TABLE IF NOT EXISTS `gs_mairie_demande_terrains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quartier_id` int DEFAULT NULL,
  `utilisateur_id` int DEFAULT NULL,
  `type_demande` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `superficie` double DEFAULT NULL,
  `usage_prevu` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `possede_autre_terrain` tinyint(1) DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `motif_refus` longtext COLLATE utf8mb4_unicode_ci,
  `date_creation` datetime DEFAULT NULL,
  `date_modification` datetime DEFAULT NULL,
  `type_document` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verso` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `type_titre` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `terrain_akaolack` tinyint(1) DEFAULT NULL,
  `terrain_ailleurs` tinyint(1) DEFAULT NULL,
  `decision_commission` longtext COLLATE utf8mb4_unicode_ci,
  `rapport` longtext COLLATE utf8mb4_unicode_ci,
  `recommandation` longtext COLLATE utf8mb4_unicode_ci,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `lieu_naissance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profession` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_electeur` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habitant` tinyint(1) DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situation_matrimoniale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_enfant` int DEFAULT NULL,
  `statut_logement` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `localite` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C21F8678DF1E57AB` (`quartier_id`),
  KEY `IDX_C21F8678FB88E14F` (`utilisateur_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_documents`
--

DROP TABLE IF EXISTS `gs_mairie_documents`;
CREATE TABLE IF NOT EXISTS `gs_mairie_documents` (
  `id` int NOT NULL AUTO_INCREMENT,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenu` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
  `date_creation` datetime NOT NULL,
  `is_generated` tinyint(1) NOT NULL,
  `fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_images_article`
--

DROP TABLE IF EXISTS `gs_mairie_images_article`;
CREATE TABLE IF NOT EXISTS `gs_mairie_images_article` (
  `id` int NOT NULL AUTO_INCREMENT,
  `article_id` int NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_BA063AA27294869C` (`article_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_localites`
--

DROP TABLE IF EXISTS `gs_mairie_localites`;
CREATE TABLE IF NOT EXISTS `gs_mairie_localites` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix` double DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_localites`
--

INSERT INTO `gs_mairie_localites` (`id`, `nom`, `prix`, `description`, `latitude`, `longitude`) VALUES
(1, 'SING SING', 100000000, NULL, -0.83444, 0),
(2, 'NIMZATT', 1000000, NULL, 14.155, 16.0855),
(3, 'SING SING', 10000000, NULL, 14.1633, 16.1257),
(4, 'KABATOKI', 100000000, NULL, 16.0753, 14.1666),
(5, 'LINDIANE', 100000000, NULL, 14.1871, 8),
(6, 'Bongrés Peulgua', 100000000, NULL, 14.1442, 16.0906),
(7, 'Bongrés', 10000000000000, NULL, 14.1442, 16.0906),
(8, 'NGANE ALASSANE', 10000000000, NULL, 14.1754, 16.075),
(9, 'NGANE SAER', 0, NULL, 14.1754, 16.075),
(10, 'Thioffack', 1000000, NULL, 14.1652, 16.0758),
(11, 'NDORONG', 0, NULL, 14.1652, 16.0758),
(12, 'Ndagane', 0, NULL, 14.1009, 16.0539),
(13, 'Fass Camp des Gardes ', 0, NULL, 14.0912, 16.0608),
(14, 'Léona', 1000000, NULL, 14.1754, 16.075),
(15, 'CITE SENGHOR', 10000, NULL, NULL, NULL),
(16, 'BONGRE EXTENSION', 1000, NULL, NULL, NULL),
(17, 'LYNDIANE NORD', NULL, NULL, NULL, NULL),
(18, 'MEDINA FASS 1', 1000, NULL, NULL, NULL),
(19, 'MEDINA FASS 2', 1000, NULL, NULL, NULL),
(20, 'SAMA MOUSSA', 1000, NULL, NULL, NULL),
(21, 'ABBATOIRS', 1000, NULL, NULL, NULL),
(22, 'FASS CHEIKH TIDIANE', 1000, NULL, NULL, NULL),
(23, 'CITE SENGHOR 2', 1000, NULL, NULL, NULL),
(24, 'SARA NIMZATT', 1000, NULL, NULL, NULL),
(25, 'LYNDIANE JARDIN', 1000, NULL, NULL, NULL),
(26, 'KOUNDAME', 1000, NULL, NULL, NULL),
(27, 'KASSAVILLE', 1000, NULL, NULL, NULL),
(28, 'DAROU SALAM', 1000, NULL, NULL, NULL),
(29, 'SING SING TF 2084', 1000, NULL, NULL, NULL),
(30, 'MEDINA MBABA', 1000, NULL, NULL, NULL),
(31, 'SING SING LOGEMENT SOCIAL', 1000, NULL, NULL, NULL),
(32, 'MEDINA', 1000, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_lotissements`
--

DROP TABLE IF EXISTS `gs_mairie_lotissements`;
CREATE TABLE IF NOT EXISTS `gs_mairie_lotissements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `localisation` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_creation` datetime NOT NULL,
  `localite_id` int DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_920C80D2924DD2B5` (`localite_id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_lotissements`
--

INSERT INTO `gs_mairie_lotissements` (`id`, `nom`, `localisation`, `description`, `statut`, `date_creation`, `localite_id`, `latitude`, `longitude`) VALUES
(1, 'SING SING TF2084', 'SING SING', 'Lotissement mairie ', 'en cours', '2025-09-19 12:34:57', 1, 14.553, -16.12323),
(2, 'NIMZATT EXTENSION', 'NIMZATT', 'Lotissement Mairie', 'achevé', '2025-09-19 14:25:07', 2, 14, 16),
(3, 'SING SING TF 912', 'SING SING', 'Lotissement Mairie', 'achevé', '2025-09-19 14:34:59', 3, 14.1633, 16.1257),
(4, 'KABATOKI KABATOKI', 'KABATOKI', 'Lotissement Sur le TF de la Marie', 'achevé', '2025-09-19 14:37:42', 4, 14.1666, 16.0753),
(5, 'KABATOKI PEULGHA', 'KABATOKI', 'Lotissement sur le TF 912 de la Mairie', 'achevé', '2025-09-19 14:39:09', 4, 16.0753, 14.1666),
(6, 'LYNDIANE SERERE', 'LYNDIANE', 'Lotissement sur le TF 912 de la Mairie', 'achevé', '2025-09-19 14:41:31', 5, 14.1871, 16.1602),
(7, 'KABATOKI TF 913', 'KABATOKI', 'Lotissement sur le TF 913 de la Mairie ', 'achevé', '2025-09-19 14:43:03', 4, 14.1666, 16.0753),
(8, 'Lotissement Gare Routiére', 'Bongrés Peulgha', 'Lotissement sur le TF 4617 de la Mairie', 'achevé', '2025-09-19 14:45:42', 6, 14.1442, 16.0906),
(9, 'Lotissement TF 4060', 'Bongrés', 'Lotissement sur le TF 4060 de la Mairie', 'achevé', '2025-09-19 14:48:05', 7, 14.1442, 16.0906),
(10, 'Lotissement 4062', 'Bongrés', 'Lotissement sur le TF 4062 de la Mairie\n', 'achevé', '2025-09-19 14:49:25', 7, 14.1442, 16.0906),
(11, 'Lotissement Ngane Alassane', 'Ngane Alassane', 'Lotissement sur le TF 6550 appartenant à l\'Etat', 'acheve', '2025-09-19 14:54:21', 8, 14.1754, 16.075),
(12, 'Lotissement Ngane Alassane Extension', 'Ngane Alassane', 'Lotissement sur le TF 6550 appartenant à l\'Etat', 'achevé', '2025-09-19 14:58:25', 8, 14.1754, 16.075),
(13, 'Lotissement Fass Camp de Garde', 'Fass Camp de Garde ', 'Lotissement sur le TF 6550 appartenant à l\'ETAT', 'achevé', '2025-09-19 15:06:54', 9, 14.1754, 16.075),
(14, 'Lotissement Thioffac', 'Thioffack', 'Lotissement sur le TF 6550 appartenant à l\'ETAT', 'achevé', '2025-09-19 15:09:22', 10, 14.1652, 16.0758),
(15, 'Lotissement  Darou Salam Ndagane', 'Ndagane', 'Lotissement sur le TF 6550 appartenant à l\'Etat', 'en cours', '2025-09-19 15:19:36', 12, 16.0539, 14.1009);

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_lots`
--

DROP TABLE IF EXISTS `gs_mairie_lots`;
CREATE TABLE IF NOT EXISTS `gs_mairie_lots` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lotissement_id` int DEFAULT NULL,
  `numero_lot` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `superficie` double DEFAULT NULL,
  `type_usage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prix` double DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_86DA0B6DF79944C3` (`lotissement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_parcelle`
--

DROP TABLE IF EXISTS `gs_mairie_parcelle`;
CREATE TABLE IF NOT EXISTS `gs_mairie_parcelle` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lotissement_id` int DEFAULT NULL,
  `numero` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `surface` double DEFAULT NULL,
  `statut` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `proprietaire_id` int DEFAULT NULL,
  `type_parcelle` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tf_de` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_EF66BB0CF79944C3` (`lotissement_id`),
  KEY `IDX_EF66BB0C76C50E4A` (`proprietaire_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_parcelle`
--

INSERT INTO `gs_mairie_parcelle` (`id`, `lotissement_id`, `numero`, `surface`, `statut`, `latitude`, `longitude`, `proprietaire_id`, `type_parcelle`, `tf_de`) VALUES
(1, 1, 'N°66', 280, 'EN_COURS', 14.556, -16.12322, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_plan_lotissements`
--

DROP TABLE IF EXISTS `gs_mairie_plan_lotissements`;
CREATE TABLE IF NOT EXISTS `gs_mairie_plan_lotissements` (
  `id` int NOT NULL AUTO_INCREMENT,
  `lotissement_id` int DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_creation` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_F9380669F79944C3` (`lotissement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_reset_password_requests`
--

DROP TABLE IF EXISTS `gs_mairie_reset_password_requests`;
CREATE TABLE IF NOT EXISTS `gs_mairie_reset_password_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `selector` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hashed_token` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requested_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  `expires_at` datetime NOT NULL COMMENT '(DC2Type:datetime_immutable)',
  PRIMARY KEY (`id`),
  KEY `IDX_11B9A5FAA76ED395` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_signatures`
--

DROP TABLE IF EXISTS `gs_mairie_signatures`;
CREATE TABLE IF NOT EXISTS `gs_mairie_signatures` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `date_signature` datetime DEFAULT NULL,
  `signature` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ordre` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_9A4313D4A76ED395` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_titre_fonciers`
--

DROP TABLE IF EXISTS `gs_mairie_titre_fonciers`;
CREATE TABLE IF NOT EXISTS `gs_mairie_titre_fonciers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `quartier_id` int DEFAULT NULL,
  `numero` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `superficie` double DEFAULT NULL,
  `titre_figure` longtext COLLATE utf8mb4_unicode_ci COMMENT '(DC2Type:json)',
  `etat_droit_reel` longtext COLLATE utf8mb4_unicode_ci,
  `type` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fichier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_CC59D394DF1E57AB` (`quartier_id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_titre_fonciers`
--

INSERT INTO `gs_mairie_titre_fonciers` (`id`, `quartier_id`, `numero`, `superficie`, `titre_figure`, `etat_droit_reel`, `type`, `fichier`) VALUES
(2, 14, 'TF 42', 2024, NULL, '', 'Titre foncier', '/uploads/titres/2/Titre foncier-20251031102130-81828104.png'),
(3, 14, 'TF 44', 2305, NULL, '', 'Titre foncier', '/uploads/titres/3/Titre foncier-20251031102057-8c235fa3.png'),
(4, 14, 'TF 78', 4275, NULL, '', 'Titre foncier', '/uploads/titres/4/Titre foncier-20251031102041-4e0bcb58.png'),
(5, 14, 'TF 219', 1315, NULL, '', 'Titre foncier', '/uploads/titres/5/Titre foncier-20251031102028-2958122b.png'),
(6, 14, 'TF 477', 1852, NULL, '', 'Titre foncier', '/uploads/titres/6/Titre foncier-20251031101953-b484cf4b.png'),
(7, 5, 'TF 912', 12593200, NULL, '', 'Titre foncier', '/uploads/titres/7/Titre foncier-20251031101936-a7f9fed1.png'),
(8, 5, 'TF 913', 7065000, NULL, '', 'Titre foncier', '/uploads/titres/8/Titre foncier-20251031101921-e81c757a.png'),
(9, 3, 'TF 2084', 564467, NULL, '', 'Titre foncier', '/uploads/titres/9/Titre foncier-20251031101906-643140c7.png'),
(10, 7, 'TF 2153', 2506, NULL, '', 'Titre foncier', '/uploads/titres/10/Titre foncier-20251031101853-7999b869.png'),
(11, 7, 'TF 2455', 239, NULL, '', 'Titre foncier', '/uploads/titres/11/Titre foncier-20251031101836-8d3fc357.png'),
(12, 7, 'TF 2599', 2115, NULL, '', 'Titre foncier', '/uploads/titres/12/Titre foncier-20251031101816-b010b23b.png'),
(13, 14, 'TF 2664', 11390, NULL, '', 'Titre foncier', '/uploads/titres/13/Titre foncier-20251031101757-57f65ef7.png'),
(14, 13, 'TF 2667', 15146, NULL, '', 'Titre foncier', '/uploads/titres/14/Titre foncier-20251031101732-044828ef.png'),
(15, 14, 'TF 2682', 29280, NULL, '', 'Titre foncier', '/uploads/titres/15/Titre foncier-20251031101712-00a63686.png'),
(16, 14, 'TF 2683', 4312, NULL, '', 'Titre foncier', '/uploads/titres/16/Titre foncier-20251031101649-92677a10.png'),
(17, 14, 'TF 2920', 300, NULL, '', 'Titre foncier', '/uploads/titres/17/Titre foncier-20251031101627-63fe267c.png'),
(18, NULL, 'TF 4019', 779, NULL, '', 'Titre foncier', '/uploads/titres/18/Titre foncier-20251031101612-1ef0881f.png'),
(19, 7, 'TF 4060', 19431, NULL, '', 'Titre foncier', '/uploads/titres/19/Titre foncier-20251031101555-b8c2b4db.png'),
(20, 7, 'TF 4062', 16678, NULL, '', 'Titre foncier', '/uploads/titres/20/Titre foncier-20251031101540-d94af4ec.png'),
(21, NULL, 'TF 4204', 625, NULL, '', 'Titre foncier', '/uploads/titres/21/Titre foncier-20251031101528-23a8eed7.png'),
(22, 14, 'TF 4235', 679, NULL, '', 'Titre foncier', '/uploads/titres/22/Titre foncier-20251031101503-7ca9bd55.png'),
(23, 7, 'TF 4617', 33565, NULL, '', 'Titre foncier', '/uploads/titres/23/Titre foncier-20251031101446-4006e240.png'),
(24, 1, 'TF 7244', 179973, NULL, '', 'Titre foncier', '/uploads/titres/24/Titre foncier-20251031101419-019f7e21.png'),
(25, 21, 'TF 2668', 1600, '[]', '', 'Titre foncier', '/uploads/titres/25/Titre foncier-20251031101356-d4970376.png'),
(26, 14, 'TF 2684', 9187, NULL, '', 'Titre foncier', '/uploads/titres/26/Titre foncier-20251031101330-92675d2e.png');

-- --------------------------------------------------------

--
-- Structure de la table `gs_mairie_users`
--

DROP TABLE IF EXISTS `gs_mairie_users`;
CREATE TABLE IF NOT EXISTS `gs_mairie_users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `roles` longtext COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '(DC2Type:json)',
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `avatar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enabled` tinyint(1) DEFAULT NULL,
  `activeted` tinyint(1) DEFAULT NULL,
  `token_activeted` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_claire` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `prenom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nom` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `date_naissance` date NOT NULL,
  `lieu_naissance` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `profession` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telephone` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `numero_electeur` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habitant` tinyint(1) DEFAULT NULL,
  `reset_token_expired_at` datetime DEFAULT NULL,
  `situation_matrimoniale` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_enfant` int DEFAULT NULL,
  `situation_demandeur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `situationdemande_demandeurur` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_5B4BED37F85E0677` (`username`),
  UNIQUE KEY `UNIQ_5B4BED37E7927C74` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=75 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `gs_mairie_users`
--

INSERT INTO `gs_mairie_users` (`id`, `username`, `roles`, `password`, `email`, `avatar`, `reset_token`, `enabled`, `activeted`, `token_activeted`, `password_claire`, `prenom`, `nom`, `date_naissance`, `lieu_naissance`, `adresse`, `profession`, `telephone`, `numero_electeur`, `habitant`, `reset_token_expired_at`, `situation_matrimoniale`, `nombre_enfant`, `situation_demandeur`, `situationdemande_demandeurur`) VALUES
(10, 'admin@admin.com', '[\"ROLE_ADMIN\",\"ROLE_SUPER_ADMIN\"]', '$2y$04$HzWVgJOieZciH0MorMWJrO0qhLJX9XeeRFC8VZIaH/8ni8Dyg2n/G', 'admin@admin.com', '10-admin@admin.com-profile.png', NULL, 1, 1, NULL, 'password', 'mansour', 'ba', '2025-02-03', 'dakar', 'dakar', 'dev', '784000998', '1234567890987', NULL, NULL, NULL, NULL, NULL, NULL),
(11, 'agent@yopmail.com', '[\"ROLE_ADMIN\"]', '$2y$04$qPxs7DFk9Y17qYM6y6s7T..AB75F.21qQ2Le/IWchoFfRb5Rzghoa', 'agent@yopmail.com', '11-agent@yopmail.com-profile.png', '428arvrBuoZYluBmJiyi05Pp1kSBPi7TS26nlRrh0y8', 1, 1, '1f930f7d1d9b953eb809a1b68404bad2ed134dfd9b4a0f678b5de68d74e75cd8', NULL, 'Ali', 'Diallo', '1990-05-15', 'Dakar', '123 Rue Exemple', 'Dev', '770123456', '1209128712764', NULL, '2025-03-21 13:41:41', NULL, NULL, NULL, NULL),
(54, 'ndeya91diop@gmail.com', '[\"ROLE_DEMANDEUR\"]', '$2y$10$LXave3dftFLihWt/NwSIsuZz9aNqfKeJbdcj60IL22vbFWNJwHBbm', 'ndeya91diop@gmail.com', NULL, NULL, 0, 1, 'fb38f824bc2268a3d15062ed610c446a4e81b181e9cf24c22ffe11573c46bfdc', 'password', 'Ndeye', 'Diop', '1991-10-10', 'Mbacké', 'Mairie de kaolack', 'Responsable Technique Agent Voyer', '777179452', '2101019911200', NULL, NULL, NULL, NULL, NULL, NULL),
(55, 'salioumbayegee24@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$04$JDskmWU19awWkcCsUdIdHOb8OhSVA0Qspp6YZy5aDfSp7IvmbwIOW', 'salioumbayegee24@gmail.com', NULL, NULL, 0, 1, '37ff71cddb8aaf7a1d3842cd69cdbc62936ff4e370b07b9a4d81e85bd2d19a0c', 'Tg17vjD2', 'Saliou', 'Mbaye', '2002-10-20', 'Kaoloack', 'Kaolack', 'Géomaticien', '778811025', '1548200206728', 0, NULL, NULL, NULL, NULL, NULL),
(56, 'sallass77@hotmail.fr', '[\"ROLE_ADMIN\"]', '$2y$04$6AUj956hkzgbuMUnlyHK1OCnfV8kgHYfYm6gcbQEETQB8YvzmBVp.', 'sallass77@hotmail.fr', NULL, NULL, 0, 1, '06c2f4a0f470a746443bfbcca1dace88b9b1b25195ba985505937d985a22abca', 'CEfDqH16', 'Ass', 'Sall', '1977-10-20', 'Kaoloack', 'Kaolack', 'Géometre', '776408154', '1212198604419', 0, NULL, NULL, NULL, NULL, NULL),
(57, 'barcheikh@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$04$HafjBUr0hHiXH3x/ebYL5.9ub8hpTJmMPtfz6Db0jFl8v8pVDr09i', 'barcheikh@gmail.com', NULL, NULL, 0, 1, '6996f1f32ea3d95cd5084b1bc48239041fd1d6d9cb0f15ca76df89b35c8f9c13', '5jUJYCnw', 'Cheikh', 'Bar', '2002-09-02', 'Dakar', 'Kaolack', 'Agent', '781250219', '1770197900792', 0, NULL, 'Marié(e)', 2, 'Locataire', NULL),
(58, 'mbaye.ngom@kaolackcommune.sn', '[\"ROLE_ADMIN\"]', '$2y$10$AIZnaGNBiOU.peJ3/9/fhORdlmkghiggWpXOunDaRAIJMAplq82gy', 'mbaye.ngom@kaolackcommune.sn', NULL, NULL, 0, 1, '84489edb7fce77ae6f09e5b962a56fdfe7fb6a529de02a54dacd3f1cd984cb61', '9bRVXpbl', 'Mbaye', 'Ngom', '2025-09-23', 'Kaolack', 'Kaolack ', 'Agent', '709009090', '1212121212121', 0, NULL, NULL, NULL, NULL, NULL),
(59, 'heleneboumy@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$10$DVyyUC9SZ9CQjGQcWGjDpulEyMZZ5cVDENio08O6nCKY7qWdz1Dz6', 'heleneboumy@gmail.com', NULL, NULL, 0, 1, '7fdd7f97db1d1c23405c8026610adb02444a7f798feee0c439f131340919c70f', 'lk7IH7B8', 'Helene Ndew', 'FAYE', '2000-01-01', 'Kaolack', 'Kaolack', 'Assistante', '782285373', '1223121212122', 0, NULL, NULL, NULL, NULL, NULL),
(60, 'lallamariamafall@gmail.com', '[\"ROLE_ADMIN\"]', '$2y$04$4al1ffa./x.2Kp/UbaNcFeZA0iI.iSLD1yxu.S9B2855xmMpt2jC.', 'lallamariamafall@gmail.com', NULL, NULL, 0, 1, '2cf350a1417e73767a0229a90934d4e172e72c45d294581dc98e5d22fa789fa8', 'Mariama@2025', 'Mariama', 'Fall', '1952-02-03', 'Tabangoye ', 'Kaolack', 'Agent', '777026150', '2548195200933', 0, NULL, NULL, NULL, NULL, NULL),
(66, 'thierno.seck@ccbm.sn', '[\"ROLE_SUPER_ADMIN\"]', '$2y$04$WbG8O.UWJ7MbuReSCn.R2e/Z4iR2NdxBH0ey9CzH.hROrImp3UKsm', 'thierno.seck@ccbm.sn', NULL, NULL, 1, 1, 'dc48d6afd1c9efd37f40e20b952a2f4d3b1291713c57046715050ddf84aab9c8', NULL, 'Thierno', 'Seck', '2000-01-01', 'Dakar', 'Dakar', 'DSI', '707761616', '1234567091N23', 0, NULL, NULL, NULL, NULL, NULL),
(68, 'khouma964@gmail.com', '[\"ROLE_AGENT\"]', '$2y$04$aAk4/YJDLeSOtEmTjfW8ZODjpcT0rt1yfNrhiJeUgToOqckuejo8m', 'khouma964@gmail.com', NULL, NULL, 1, 1, '012afe115c225601b87dd31aabc650ed7dd56dd10a4fb663b523d32d4fb88e1d', NULL, 'Agent', 'Khouma', '2000-10-10', 'Dakar', 'Dakar', 'ING', '784003456', '25481952000933', 1, NULL, NULL, NULL, NULL, NULL),
(69, 'serigne.mboup@ccbm.sn', '[\"ROLE_MAIRE\"]', '$2y$04$o/qj6VE.Xgj.EjkaTA9R/e6bsNp5fObartNYG8HCQFgIqd6Q7Jdka', 'serigne.mboup@ccbm.sn', '69-serigne.mboup@ccbm.sn-profile.png', NULL, 1, 1, 'd97fb5df7dfc4ce125f301c3dacabcb74fe15a20b2c664af148f167306e2387a', 'serigne6619', 'Serigne', 'Mboup', '2000-09-30', 'KAOLACK', 'Kaolack', 'Maire', '776388473', '12000000000SN', 0, NULL, 'Marié(e)', 0, 'Propriétaire', NULL),
(70, 'khoumatest@yopmail.com', '[\"ROLE_DEMANDEUR\"]', '$2y$10$PfvFXvUhstgYktS6VkgT8upZ9RSr.cqq.N8HKrA77QHF4RQREyABe', 'khoumatest@yopmail.com', NULL, NULL, 1, 0, '60dfb18fb69aba91ea9ea3683265eb545c645d81848cabdcf25e37dfcd54257a', 'password', 'Al husseinTest', 'KhoumaTest', '2000-10-10', 'Dakar', 'Dakar', 'PDG', '781001010', '2312091092993', NULL, NULL, 'Marié(e)', 0, 'Propriétaire', NULL),
(71, 'therese@yopmail.com', '[\"ROLE_DEMANDEUR\"]', '$2y$10$iGMxtbaAa6EYL0fRJdt6ieeMREyCgr4fawBO/FAr928kPfH6u5cAK', 'therese@yopmail.com', NULL, NULL, 1, 0, '8801bff55966630b4cfb954ca5004a5f6ece3efe4a80cd76b54aee426b708d61', 'password', 'THERESE', 'KANE', '2000-01-01', 'dakar', 'PARCELLES ASSAINIES', 'AGENT ADMINISTRATIF', '775490000', '121212121212212', 0, NULL, 'Célibataire', 0, 'Hébergé(e)', NULL),
(72, 'alhusseinkhouma0@gmail.com', '[\"ROLE_DEMANDEUR\"]', '$2y$10$CzO3X3Ioby8AaM4TVzIc6.tByQC0nFaa3I.M17j4Us24tzb4WOLSi', 'alhusseinkhouma0@gmail.com', NULL, NULL, 1, 0, '48fb86078e2a7ec8aedd1ed36f7b64fe93a94dba77bed071b07e774315569ce4', 'Password123!', 'Al hussein', 'Khouma', '1999-11-25', 'Dakar', 'Dakar', 'Informaticien', '784537547', '18701999010101', NULL, NULL, 'Marié(e)', 0, 'Hébergé(e)', NULL),
(73, 'coleamymbow@gmail.com', '[\"ROLE_DEMANDEUR\"]', '$2y$04$oxgWaMo2ZpgDhKZg4Q/A4OX8p/eg8zQp3xIQ1MFpRFY4ucDmHKJVW', 'coleamymbow@gmail.com', NULL, NULL, 1, 1, '3cc9277c0bc8706a72c42c525f4d7b26cdc6580fbfa9b670ec462fe7bc8b3502', NULL, 'Aminata', 'Mbow', '2007-07-11', 'Kaolack', 'kaolack', 'Etudiant', '787365355', NULL, 0, NULL, NULL, 0, NULL, NULL),
(74, 'nougatine77@free.fr', '[\"ROLE_DEMANDEUR\"]', '$2y$10$rxCJHqqbEGnDhlwNrmo02./cWt1meQomvBHJ2k5AlS1VWyMiSuI.q', 'nougatine77@free.fr', NULL, 'uCUW0bUKxDPQPU9nAT4SICMziSevfVmc1_pEhRGJTqI', 1, 1, '14af15b7aa070896d2a8daebca740b0e50865110172473e1dbb50c2ec40ea2b5', 'Password123!', 'AMSATOU', 'BADIANE', '1959-10-23', 'Kaolack ', 'Parcelle 3221 ndorong kaolack ', 'Retraitee', '786852367', '5910232F3112261', NULL, '2025-12-12 10:29:14', 'Divorcé(e)', 5, 'Propriétaire', NULL);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `gs_mairie_articles_terrains`
--
ALTER TABLE `gs_mairie_articles_terrains`
  ADD CONSTRAINT `FK_B571508360BB6FE6` FOREIGN KEY (`auteur_id`) REFERENCES `gs_mairie_users` (`id`),
  ADD CONSTRAINT `FK_B5715083BCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `gs_mairie_categories_terrains` (`id`);

--
-- Contraintes pour la table `gs_mairie_attribuation_historiques`
--
ALTER TABLE `gs_mairie_attribuation_historiques`
  ADD CONSTRAINT `FK_4CF43F71EEB69F7B` FOREIGN KEY (`attribution_id`) REFERENCES `gs_mairie_attribuation_parcelle` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `gs_mairie_attribuation_parcelle`
--
ALTER TABLE `gs_mairie_attribuation_parcelle`
  ADD CONSTRAINT `FK_FD50F1F94433ED66` FOREIGN KEY (`parcelle_id`) REFERENCES `gs_mairie_parcelle` (`id`);

--
-- Contraintes pour la table `gs_mairie_demande_terrains`
--
ALTER TABLE `gs_mairie_demande_terrains`
  ADD CONSTRAINT `FK_C21F8678DF1E57AB` FOREIGN KEY (`quartier_id`) REFERENCES `gs_mairie_localites` (`id`),
  ADD CONSTRAINT `FK_C21F8678FB88E14F` FOREIGN KEY (`utilisateur_id`) REFERENCES `gs_mairie_users` (`id`);

--
-- Contraintes pour la table `gs_mairie_images_article`
--
ALTER TABLE `gs_mairie_images_article`
  ADD CONSTRAINT `FK_BA063AA27294869C` FOREIGN KEY (`article_id`) REFERENCES `gs_mairie_articles_terrains` (`id`);

--
-- Contraintes pour la table `gs_mairie_lotissements`
--
ALTER TABLE `gs_mairie_lotissements`
  ADD CONSTRAINT `FK_920C80D2924DD2B5` FOREIGN KEY (`localite_id`) REFERENCES `gs_mairie_localites` (`id`);

--
-- Contraintes pour la table `gs_mairie_lots`
--
ALTER TABLE `gs_mairie_lots`
  ADD CONSTRAINT `FK_86DA0B6DF79944C3` FOREIGN KEY (`lotissement_id`) REFERENCES `gs_mairie_lotissements` (`id`);

--
-- Contraintes pour la table `gs_mairie_parcelle`
--
ALTER TABLE `gs_mairie_parcelle`
  ADD CONSTRAINT `FK_EF66BB0C76C50E4A` FOREIGN KEY (`proprietaire_id`) REFERENCES `gs_mairie_users` (`id`),
  ADD CONSTRAINT `FK_EF66BB0CF79944C3` FOREIGN KEY (`lotissement_id`) REFERENCES `gs_mairie_lotissements` (`id`);

--
-- Contraintes pour la table `gs_mairie_plan_lotissements`
--
ALTER TABLE `gs_mairie_plan_lotissements`
  ADD CONSTRAINT `FK_F9380669F79944C3` FOREIGN KEY (`lotissement_id`) REFERENCES `gs_mairie_lotissements` (`id`);

--
-- Contraintes pour la table `gs_mairie_reset_password_requests`
--
ALTER TABLE `gs_mairie_reset_password_requests`
  ADD CONSTRAINT `FK_11B9A5FAA76ED395` FOREIGN KEY (`user_id`) REFERENCES `gs_mairie_users` (`id`);

--
-- Contraintes pour la table `gs_mairie_signatures`
--
ALTER TABLE `gs_mairie_signatures`
  ADD CONSTRAINT `FK_9A4313D4A76ED395` FOREIGN KEY (`user_id`) REFERENCES `gs_mairie_users` (`id`);

--
-- Contraintes pour la table `gs_mairie_titre_fonciers`
--
ALTER TABLE `gs_mairie_titre_fonciers`
  ADD CONSTRAINT `FK_CC59D394DF1E57AB` FOREIGN KEY (`quartier_id`) REFERENCES `gs_mairie_localites` (`id`);
SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
