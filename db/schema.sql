-- MySQL dump 10.13  Distrib 5.6.39, for Linux (x86_64)
--
-- Host: localhost    Database: exam_prep
-- ------------------------------------------------------
-- Server version	5.6.39

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `answer`
--

DROP TABLE IF EXISTS `answer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answer` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) NOT NULL,
  `answer_text` varchar(1000) NOT NULL,
  `correct` tinyint(4) DEFAULT '0',
  `audio_hash` varchar(32) DEFAULT NULL,
  `audio_file` varchar(100) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `modified` timestamp NULL DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_question_answer_question1_idx` (`question_id`),
  KEY `fk_answer_created_by_user_idx` (`created_by`),
  KEY `fk_answer_modified_by_user_idx` (`modified_by`),
  CONSTRAINT `fk_answer_created_by_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_answer_modified_by_user` FOREIGN KEY (`modified_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_question_answer_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=215680 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`hondros`@`localhost`*/ /*!50003 TRIGGER `answer_audit` AFTER UPDATE ON answer
  FOR EACH ROW
  BEGIN
    SET @now = NOW();

    IF NOT (NEW.answer_text <=> OLD.answer_text) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'answer_text', OLD.answer_text, NEW.answer_text, @now);
    END IF;

    IF NOT (NEW.correct <=> OLD.correct) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'correct', OLD.correct, NEW.correct, @now);
    END IF;

    IF NOT (NEW.audio_hash <=> OLD.audio_hash) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'audio_hash', OLD.audio_hash, NEW.audio_hash, @now);
    END IF;

    IF NOT (NEW.audio_file <=> OLD.audio_file) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'audio_file', OLD.audio_file, NEW.audio_file, @now);
    END IF;

    IF NOT (NEW.modified_by <=> OLD.modified_by) THEN
        INSERT INTO `answer_audit` (`question_id`, `answer_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.question_id, NEW.id, 'modified_by', OLD.modified_by, NEW.modified_by, @now);
    END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `answer_audit`
--

DROP TABLE IF EXISTS `answer_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answer_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT NULL,
  `answer_id` int(11) DEFAULT NULL,
  `column_name` varchar(30) DEFAULT NULL,
  `before_value` text,
  `after_value` text,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_answer_audit_answer1_idx` (`answer_id`),
  CONSTRAINT `fk_answer_audit_question1` FOREIGN KEY (`answer_id`) REFERENCES `answer` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=939 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_attempt`
--

DROP TABLE IF EXISTS `assessment_attempt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_count` int(11) NOT NULL,
  `correct` int(11) DEFAULT '0',
  `incorrect` int(11) DEFAULT '0',
  `score` int(11) DEFAULT NULL,
  `bookmarked` int(11) DEFAULT '0',
  `unbookmarked` int(11) DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  `completed` timestamp NULL DEFAULT NULL,
  `total_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_exam_attempt_enrollment1_idx` (`enrollment_id`),
  CONSTRAINT `fk_exam_attempt_enrollment1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=446079 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assessment_attempt_question`
--

DROP TABLE IF EXISTS `assessment_attempt_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assessment_attempt_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `assessment_attempt_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `view` smallint(6) DEFAULT NULL COMMENT 'flash cards, matching, fill in the blanks, multiple choice',
  `viewed` tinyint(4) DEFAULT '0',
  `answered` tinyint(4) DEFAULT '0',
  `correct` tinyint(4) DEFAULT '0',
  `bookmarked` tinyint(4) DEFAULT '0',
  `answer` varchar(50) DEFAULT NULL COMMENT 'the answer submitted - could be a question_answer_id or any string submitted by the user. Don''t need any linking for this here, it''s all UI driven.',
  `sort` int(11) NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_assessment_attempt_question_assessment_attempt1_idx` (`assessment_attempt_id`),
  KEY `fk_assessment_attempt_question_question1_idx` (`question_id`),
  KEY `fk_assessment_attempt_question_module1_idx` (`module_id`),
  CONSTRAINT `fk_assessment_attempt_question_assessment_attempt1` FOREIGN KEY (`assessment_attempt_id`) REFERENCES `assessment_attempt` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_attempt_question_module1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_assessment_attempt_question_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45937491 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `change_log`
--

DROP TABLE IF EXISTS `change_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `change_log` (
  `id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `change_number` smallint(6) DEFAULT NULL,
  `delta_set` varchar(50) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `applied` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `config`
--

DROP TABLE IF EXISTS `config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `enrollment`
--

DROP TABLE IF EXISTS `enrollment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `enrollment` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) NOT NULL,
  `organization_id` int(11) NOT NULL,
  `external_order_id` varchar(50) DEFAULT NULL,
  `status` smallint(6) NOT NULL DEFAULT '1' COMMENT '0-inactive\n1-active',
  `type` smallint(6) NOT NULL DEFAULT '0' COMMENT '0-regular\n1-trial',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `started` timestamp NULL DEFAULT NULL,
  `modified` timestamp NULL DEFAULT NULL,
  `expiration` timestamp NULL DEFAULT NULL,
  `converted` timestamp NULL DEFAULT NULL,
  `total_time` int(11) DEFAULT '0',
  `score` int(11) DEFAULT '0',
  `show_preassessment` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_exam` (`user_id`,`exam_id`),
  KEY `fk_enrollment_user_idx` (`user_id`),
  KEY `fk_enrollment_exam_idx` (`exam_id`),
  KEY `fk_enrollment_organization_idx` (`organization_id`),
  CONSTRAINT `fk_enrollment_exam` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollment_organization` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_enrollment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=96967 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam`
--

DROP TABLE IF EXISTS `exam`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `industry_id` int(11) NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `exam_time` int(11) DEFAULT NULL COMMENT 'Number of seconds available during an exam attempt.',
  `access_time` int(11) DEFAULT NULL COMMENT 'Days of access from enrollment',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  KEY `fk_exam_industry_idx` (`industry_id`),
  KEY `fk_exam_state_idx` (`state_id`),
  CONSTRAINT `fk_exam_industry` FOREIGN KEY (`industry_id`) REFERENCES `industry` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_exam_state` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `exam_module`
--

DROP TABLE IF EXISTS `exam_module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `exam_module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `exam_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `preassessment_questions` int(11) NOT NULL,
  `practice_questions` int(11) NOT NULL,
  `exam_questions` int(11) NOT NULL,
  `sort` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_exam_has_module_module1_idx` (`module_id`),
  KEY `fk_exam_has_module_exam1_idx` (`exam_id`),
  CONSTRAINT `fk_exam_has_module_exam1` FOREIGN KEY (`exam_id`) REFERENCES `exam` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_exam_has_module_module1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1039 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `hcob_user`
--

DROP TABLE IF EXISTS `hcob_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `hcob_user` (
  `organization_id` int(11) NOT NULL,
  `school` varchar(20) NOT NULL,
  `id` int(11) NOT NULL,
  `realed_id` varchar(20) NOT NULL,
  `last_name` varchar(60) NOT NULL,
  `first_name` varchar(60) NOT NULL,
  `email` varchar(150) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `industry`
--

DROP TABLE IF EXISTS `industry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `industry` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module`
--

DROP TABLE IF EXISTS `module`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(10) NOT NULL,
  `name` varchar(100) NOT NULL,
  `industry_id` int(11) NOT NULL,
  `state_id` int(11) DEFAULT NULL,
  `preassessment_bank_id` int(11) DEFAULT NULL,
  `study_bank_id` int(11) DEFAULT NULL,
  `practice_bank_id` int(11) DEFAULT NULL,
  `exam_bank_id` int(11) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  `status` enum('new','importing','active') NOT NULL DEFAULT 'new',
  PRIMARY KEY (`id`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  KEY `fk_module_state1_idx` (`state_id`),
  KEY `fk_module_industry1_idx` (`industry_id`),
  KEY `fk_module_question_bank2_idx` (`study_bank_id`),
  KEY `fk_module_question_bank3_idx` (`practice_bank_id`),
  KEY `fk_module_question_bank4_idx` (`exam_bank_id`),
  KEY `fk_module_question_bank1_idx` (`preassessment_bank_id`),
  CONSTRAINT `fk_module_industry1` FOREIGN KEY (`industry_id`) REFERENCES `industry` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `fk_module_question_bank1` FOREIGN KEY (`preassessment_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_question_bank2` FOREIGN KEY (`study_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_question_bank3` FOREIGN KEY (`practice_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_question_bank4` FOREIGN KEY (`exam_bank_id`) REFERENCES `question_bank` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_state1` FOREIGN KEY (`state_id`) REFERENCES `state` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=400 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_attempt`
--

DROP TABLE IF EXISTS `module_attempt`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_attempt` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_count` int(11) NOT NULL,
  `correct` int(11) DEFAULT '0',
  `incorrect` int(11) DEFAULT '0',
  `score` int(11) DEFAULT NULL,
  `bookmarked` int(11) DEFAULT '0',
  `unbookmarked` int(11) DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  `completed` timestamp NULL DEFAULT NULL,
  `total_time` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_module_vocabulary_attempt_module1_idx` (`module_id`),
  KEY `fk_module_vocabulary_attempt_enrollment1_idx` (`enrollment_id`),
  CONSTRAINT `fk_module_vocabulary_attempt_enrollment1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_vocabulary_attempt_module1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5079173 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_attempt_question`
--

DROP TABLE IF EXISTS `module_attempt_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_attempt_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module_attempt_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `view` varchar(45) DEFAULT NULL COMMENT 'flash cards, matching, fill in the blanks, multiple choice',
  `viewed` tinyint(4) DEFAULT '0',
  `answered` tinyint(4) DEFAULT '0',
  `correct` tinyint(4) DEFAULT '0',
  `bookmarked` tinyint(4) DEFAULT '0',
  `answer` varchar(255) DEFAULT NULL COMMENT 'the answer submitted - could be a question_answer_id or any string submitted by the user. Don''t need any linking for this here, it''s all UI driven.',
  `sort` int(11) NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_study_attempt_question_question1_idx` (`question_id`),
  KEY `fk_study_attempt_question_module_attempt1_idx` (`module_attempt_id`),
  CONSTRAINT `fk_study_attempt_question_module_attempt1` FOREIGN KEY (`module_attempt_id`) REFERENCES `module_attempt` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_study_attempt_question_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=102999847 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `module_question`
--

DROP TABLE IF EXISTS `module_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `module_question` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `module_id` int(11) NOT NULL,
  `type` enum('study','practice','exam','preassessment') NOT NULL,
  `question_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `module_id_type_question_id_UNIQUE` (`module_id`,`type`,`question_id`),
  KEY `fk_module_question_module1_idx` (`module_id`),
  KEY `fk_module_question_question1_idx` (`question_id`),
  CONSTRAINT `fk_module_question_module1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_module_question_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `organization`
--

DROP TABLE IF EXISTS `organization`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `organization` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL COMMENT 'To allow parent/child relationship of organizations',
  `name` varchar(150) NOT NULL,
  `url` varchar(1000) DEFAULT NULL,
  `redirect_url` varchar(1000) DEFAULT NULL,
  `credits` int(11) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  KEY `fk_organization_organization_idx` (`parent_id`),
  CONSTRAINT `fk_organization_organization` FOREIGN KEY (`parent_id`) REFERENCES `organization` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1095 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `progress`
--

DROP TABLE IF EXISTS `progress`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `progress` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `enrollment_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `type` varchar(20) NOT NULL,
  `question_count` int(11) NOT NULL,
  `attempts` int(11) DEFAULT '0',
  `correct` int(11) DEFAULT '0',
  `incorrect` int(11) DEFAULT '0',
  `bookmarked` int(11) DEFAULT '0',
  `score` int(11) DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_enrollment_module_type` (`enrollment_id`,`module_id`,`type`),
  KEY `fk_progress_enrollment1_idx` (`enrollment_id`),
  KEY `fk_progress_module1_idx` (`module_id`),
  CONSTRAINT `fk_progress_enrollment1` FOREIGN KEY (`enrollment_id`) REFERENCES `enrollment` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_module1` FOREIGN KEY (`module_id`) REFERENCES `module` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1978057 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `progress_question`
--

DROP TABLE IF EXISTS `progress_question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `progress_question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `progress_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `viewed` int(11) DEFAULT '0',
  `answered` tinyint(4) DEFAULT '0',
  `correct` tinyint(4) DEFAULT '0',
  `bookmarked` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `ux_progress_question_progress_question` (`progress_id`,`question_id`),
  KEY `fk_progress_question_progress1_idx` (`progress_id`),
  KEY `fk_progress_question_question1_idx` (`question_id`),
  CONSTRAINT `fk_progress_question_progress1` FOREIGN KEY (`progress_id`) REFERENCES `progress` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_progress_question_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=88324445 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question`
--

DROP TABLE IF EXISTS `question`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_bank_id` int(11) DEFAULT NULL,
  `type` varchar(20) CHARACTER SET armscii8 NOT NULL COMMENT 'vocabulary or multiple choice',
  `question_text` varchar(2500) CHARACTER SET armscii8 NOT NULL,
  `feedback` varchar(2000) CHARACTER SET armscii8 DEFAULT NULL,
  `techniques` varchar(500) CHARACTER SET armscii8 DEFAULT NULL COMMENT 'Helpful techniques on how to approach a question',
  `audio_hash` varchar(32) CHARACTER SET armscii8 DEFAULT NULL,
  `audio_file` varchar(100) CHARACTER SET armscii8 DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) DEFAULT NULL,
  `modified` timestamp NULL DEFAULT NULL,
  `modified_by` int(11) DEFAULT NULL,
  `active` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `question_type_idx` (`type`),
  KEY `fk_question_question_bank1_idx` (`question_bank_id`),
  KEY `fk_question_created_by_user_idx` (`created_by`),
  KEY `fk_question_modified_by_user_idx` (`modified_by`),
  CONSTRAINT `fk_question_created_by_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_question_modified_by_user` FOREIGN KEY (`modified_by`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=58591 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`hondros`@`localhost`*/ /*!50003 TRIGGER `question_audit` AFTER UPDATE ON question
  FOR EACH ROW
  BEGIN
    SET @now = NOW();

    IF NOT (NEW.question_text <=> OLD.question_text) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'question_text', OLD.question_text, NEW.question_text, @now);
    END IF;

    IF NOT (NEW.feedback <=> OLD.feedback) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'feedback', OLD.feedback, NEW.feedback, @now);
    END IF;

    IF NOT (NEW.techniques <=> OLD.techniques) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'techniques', OLD.techniques, NEW.techniques, @now);
    END IF;

    IF NOT (NEW.active <=> OLD.active) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'active', OLD.active, NEW.active, @now);
    END IF;

    IF NOT (NEW.audio_hash <=> OLD.audio_hash) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'audio_hash', OLD.audio_hash, NEW.audio_hash, @now);
    END IF;

    IF NOT (NEW.audio_file <=> OLD.audio_file) THEN
        INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
        VALUES (NEW.id, 'audio_file', OLD.audio_file, NEW.audio_file, @now);
    END IF;

    IF NOT (NEW.modified_by <=> OLD.modified_by) THEN
            INSERT INTO `question_audit` (`question_id`, `column_name`, `before_value`, `after_value`, `modified`)
            VALUES (NEW.id, 'modified_by', OLD.modified_by, NEW.modified_by, @now);
    END IF;
  END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `question_audit`
--

DROP TABLE IF EXISTS `question_audit`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `question_id` int(11) DEFAULT NULL,
  `column_name` varchar(30) DEFAULT NULL,
  `before_value` text,
  `after_value` text,
  `modified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_question_audit_question1_idx` (`question_id`),
  CONSTRAINT `fk_question_audit_question1` FOREIGN KEY (`question_id`) REFERENCES `question` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1363 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `question_bank`
--

DROP TABLE IF EXISTS `question_bank`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `question_bank` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(45) DEFAULT NULL,
  `question_count` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1199 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `state`
--

DROP TABLE IF EXISTS `state`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `state` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `code` varchar(2) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name_UNIQUE` (`name`),
  UNIQUE KEY `code_UNIQUE` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=58 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `statistics_daily`
--

DROP TABLE IF EXISTS `statistics_daily`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statistics_daily` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `users` int(11) NOT NULL DEFAULT '0',
  `exams` int(11) NOT NULL DEFAULT '0',
  `modules` int(11) NOT NULL DEFAULT '0',
  `questions` int(11) NOT NULL DEFAULT '0',
  `answers` int(11) NOT NULL DEFAULT '0',
  `bookmarks` int(11) NOT NULL DEFAULT '0',
  `modules_accessed` int(11) NOT NULL DEFAULT '0',
  `preassessment_attempts` int(11) NOT NULL DEFAULT '0',
  `exam_attempts` int(11) NOT NULL DEFAULT '0',
  `study_attempts` int(11) NOT NULL DEFAULT '0',
  `review_attempts` int(11) NOT NULL DEFAULT '0',
  `questions_answered` int(11) NOT NULL DEFAULT '0',
  `total_time` int(11) NOT NULL DEFAULT '0',
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tag`
--

DROP TABLE IF EXISTS `tag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tag` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `template`
--

DROP TABLE IF EXISTS `template`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `template` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(100) NOT NULL,
  `token` varchar(32) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` timestamp NULL DEFAULT NULL,
  `last_login` timestamp NULL DEFAULT NULL,
  `status` smallint(6) NOT NULL,
  `role` enum('admin','content','member','guest') NOT NULL DEFAULT 'member',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email_UNIQUE` (`email`),
  UNIQUE KEY `token_UNIQUE` (`token`)
) ENGINE=InnoDB AUTO_INCREMENT=90640 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_access_control`
--

DROP TABLE IF EXISTS `user_access_control`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_access_control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `ip4` int(10) unsigned DEFAULT NULL,
  `user_agent` varchar(45) DEFAULT NULL,
  `os` varchar(45) DEFAULT NULL,
  `timezone` varchar(45) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_lastaccess_user1_idx` (`user_id`),
  CONSTRAINT `fk_user_lastaccess_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_log`
--

DROP TABLE IF EXISTS `user_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `exam_id` int(11) DEFAULT NULL,
  `exam_module_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL,
  `info` varchar(2000) DEFAULT NULL COMMENT 'json string',
  `version` varchar(10) DEFAULT NULL,
  `created` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_user_log_user1_idx` (`user_id`),
  CONSTRAINT `fk_user_log_user1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=24332172 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-03-25 15:33:51
