/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19-12.2.2-MariaDB, for osx10.21 (arm64)
--
-- Host: localhost    Database: grova
-- ------------------------------------------------------
-- Server version	12.2.2-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*M!100616 SET @OLD_NOTE_VERBOSITY=@@NOTE_VERBOSITY, NOTE_VERBOSITY=0 */;

--
-- Table structure for table `work_client`
--

DROP TABLE IF EXISTS `work_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_client` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `cif_nif` varchar(30) DEFAULT NULL,
  `direccion` varchar(255) DEFAULT NULL,
  `emails_factura` varchar(255) NOT NULL,
  `salario_base` decimal(8,2) NOT NULL,
  `bonus_dia` decimal(6,2) NOT NULL,
  `hora_limite_bonus` varchar(5) NOT NULL,
  `banc_nombre` varchar(100) DEFAULT NULL,
  `banc_direccion` varchar(100) DEFAULT NULL,
  `banc_swift` varchar(20) DEFAULT NULL,
  `banc_cuenta` varchar(50) DEFAULT NULL,
  `banc_titular` varchar(100) DEFAULT NULL,
  `recargo_hnl` decimal(8,2) DEFAULT NULL,
  `activo` tinyint(4) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_client`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_client` WRITE;
/*!40000 ALTER TABLE `work_client` DISABLE KEYS */;
INSERT INTO `work_client` VALUES
(1,1,'TASARAUTO GLOBAL SERVICES SL','B87734273','C/ Puerto de Canencia nº9 (Parcela), CP 28729 Venturada (Madrid)','sonia.puente@tester.es,manuelcuesta@tester.es',1100.00,12.50,'08:00','Banco Atlántida','Bulevar Centroamérica, Plaza Banca Atlántida frente  al INPREMA, Tegucigalpa, Honduras.','ATTDHNTE','070120152577','Christian David Solorzano Girón',737.45,1,'2026-05-09 01:19:27');
/*!40000 ALTER TABLE `work_client` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_day`
--

DROP TABLE IF EXISTS `work_day`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_day` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `hora_entrada` varchar(5) DEFAULT NULL,
  `es_feriado` tinyint(4) NOT NULL,
  `es_vacacion` tinyint(4) NOT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `client_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_9FCE7E0C19EB6921` (`client_id`),
  KEY `idx_work_day_fecha` (`fecha`),
  CONSTRAINT `FK_9FCE7E0C19EB6921` FOREIGN KEY (`client_id`) REFERENCES `work_client` (`id`),
  CONSTRAINT `FK_work_day_client` FOREIGN KEY (`client_id`) REFERENCES `work_client` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=96 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_day`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_day` WRITE;
/*!40000 ALTER TABLE `work_day` DISABLE KEYS */;
INSERT INTO `work_day` VALUES
(1,1,'2026-05-04','07:50',0,0,NULL,'2026-05-10 12:02:54',1),
(2,1,'2026-05-05','07:55',0,0,NULL,'2026-05-10 12:03:06',1),
(3,1,'2026-05-06','04:00',0,0,NULL,'2026-05-10 12:03:27',1),
(4,1,'2026-05-07','07:48',0,0,NULL,'2026-05-10 12:03:39',1),
(5,1,'2026-05-08','04:00',0,0,NULL,'2026-05-10 12:03:52',1),
(6,1,'2026-05-11','04:00',0,0,NULL,'2026-05-11 11:51:01',1),
(7,1,'2026-01-02','07:45',0,0,NULL,'2026-05-11 10:37:12',1),
(8,1,'2026-01-05','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(9,1,'2026-01-07','07:30',0,0,NULL,'2026-05-11 10:37:12',1),
(10,1,'2026-01-08','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(11,1,'2026-01-09','07:50',0,0,NULL,'2026-05-11 10:37:12',1),
(12,1,'2026-01-12','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(13,1,'2026-01-13','07:40',0,0,NULL,'2026-05-11 10:37:12',1),
(14,1,'2026-01-14','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(15,1,'2026-01-15','07:35',0,0,NULL,'2026-05-11 10:37:12',1),
(16,1,'2026-01-16','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(17,1,'2026-01-19','07:45',0,0,NULL,'2026-05-11 10:37:12',1),
(18,1,'2026-01-20','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(19,1,'2026-01-21','07:48',0,0,NULL,'2026-05-11 10:37:12',1),
(20,1,'2026-01-22','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(21,1,'2026-01-23','07:30',0,0,NULL,'2026-05-11 10:37:12',1),
(22,1,'2026-01-26','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(23,1,'2026-01-27','07:52',0,0,NULL,'2026-05-11 10:37:12',1),
(24,1,'2026-01-28','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(25,1,'2026-01-29','07:44',0,0,NULL,'2026-05-11 10:37:12',1),
(26,1,'2026-01-30','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(27,1,'2026-02-02','07:45',0,0,NULL,'2026-05-11 10:37:12',1),
(28,1,'2026-02-04','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(29,1,'2026-02-05','07:38',0,0,NULL,'2026-05-11 10:37:12',1),
(30,1,'2026-02-06','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(31,1,'2026-02-09','07:50',0,0,NULL,'2026-05-11 10:37:12',1),
(32,1,'2026-02-10','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(33,1,'2026-02-11','07:30',0,0,NULL,'2026-05-11 10:37:12',1),
(34,1,'2026-02-12','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(35,1,'2026-02-13','07:42',0,0,NULL,'2026-05-11 10:37:12',1),
(36,1,'2026-02-16','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(37,1,'2026-02-17','07:47',0,0,NULL,'2026-05-11 10:37:12',1),
(38,1,'2026-02-18','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(39,1,'2026-02-19','07:33',0,0,NULL,'2026-05-11 10:37:12',1),
(40,1,'2026-02-20','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(41,1,'2026-02-23','07:55',0,0,NULL,'2026-05-11 10:37:12',1),
(42,1,'2026-02-24','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(43,1,'2026-02-25','07:41',0,0,NULL,'2026-05-11 10:37:12',1),
(44,1,'2026-02-26','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(45,1,'2026-02-27','07:49',0,0,NULL,'2026-05-11 10:37:12',1),
(46,1,'2026-03-02','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(47,1,'2026-03-03','07:43',0,0,NULL,'2026-05-11 10:37:12',1),
(48,1,'2026-03-04','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(49,1,'2026-03-05','07:36',0,0,NULL,'2026-05-11 10:37:12',1),
(50,1,'2026-03-06','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(51,1,'2026-03-09','07:51',0,0,NULL,'2026-05-11 10:37:12',1),
(52,1,'2026-03-10','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(53,1,'2026-03-11','07:29',0,0,NULL,'2026-05-11 10:37:12',1),
(54,1,'2026-03-12','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(55,1,'2026-03-13','07:46',0,0,NULL,'2026-05-11 10:37:12',1),
(56,1,'2026-03-16','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(57,1,'2026-03-17','07:53',0,0,NULL,'2026-05-11 10:37:12',1),
(58,1,'2026-03-18','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(59,1,'2026-03-19','07:37',0,0,NULL,'2026-05-11 10:37:12',1),
(60,1,'2026-03-20','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(61,1,'2026-03-23','07:44',0,0,NULL,'2026-05-11 10:37:12',1),
(62,1,'2026-03-24','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(63,1,'2026-03-25','07:31',0,0,NULL,'2026-05-11 10:37:12',1),
(64,1,'2026-03-26','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(65,1,'2026-03-27','07:57',0,0,NULL,'2026-05-11 10:37:12',1),
(66,1,'2026-03-30','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(67,1,'2026-03-31','07:48',0,0,NULL,'2026-05-11 10:37:12',1),
(68,1,'2026-04-01','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(69,1,'2026-04-06','07:45',0,0,NULL,'2026-05-11 10:37:12',1),
(70,1,'2026-04-07','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(71,1,'2026-04-08','07:39',0,0,NULL,'2026-05-11 10:37:12',1),
(72,1,'2026-04-09','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(73,1,'2026-04-10','07:54',0,0,NULL,'2026-05-11 10:37:12',1),
(74,1,'2026-04-13','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(75,1,'2026-04-15','07:32',0,0,NULL,'2026-05-11 10:37:12',1),
(76,1,'2026-04-16','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(77,1,'2026-04-17','07:47',0,0,NULL,'2026-05-11 10:37:12',1),
(78,1,'2026-04-20','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(79,1,'2026-04-21','07:40',0,0,NULL,'2026-05-11 10:37:12',1),
(80,1,'2026-04-22','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(81,1,'2026-04-23','07:56',0,0,NULL,'2026-05-11 10:37:12',1),
(82,1,'2026-04-24','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(83,1,'2026-04-27','07:35',0,0,NULL,'2026-05-11 10:37:12',1),
(84,1,'2026-04-28','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(85,1,'2026-04-29','07:50',0,0,NULL,'2026-05-11 10:37:12',1),
(86,1,'2026-04-30','04:00',0,0,NULL,'2026-05-11 10:37:12',1),
(87,1,'2026-01-06','07:30',0,0,'Feriado trabajado','2026-05-11 10:57:47',1),
(88,1,'2026-02-03','04:00',0,0,'Feriado trabajado','2026-05-11 18:28:22',1),
(89,1,'2026-05-12','04:00',0,0,NULL,'2026-05-12 19:54:55',1),
(90,1,'2026-05-13','04:00',0,0,NULL,'2026-05-14 05:00:41',1),
(91,1,'2026-05-14','04:00',0,0,NULL,'2026-05-14 06:47:58',1),
(92,1,'2026-05-15','04:00',0,0,NULL,'2026-05-15 06:45:22',1),
(93,1,'2026-05-18','04:00',0,0,NULL,'2026-05-18 06:37:09',1),
(94,1,'2026-05-19','04:00',0,0,NULL,'2026-05-19 05:47:40',1),
(95,1,'2026-05-20','04:00',0,0,NULL,'2026-05-20 05:22:17',1);
/*!40000 ALTER TABLE `work_day` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_invoice`
--

DROP TABLE IF EXISTS `work_invoice`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_invoice` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `mes` int(11) NOT NULL,
  `dias_trabajados` int(11) NOT NULL,
  `dias_bonus` int(11) NOT NULL,
  `salario_base` decimal(8,2) NOT NULL,
  `monto_bonus` decimal(8,2) NOT NULL,
  `total` decimal(8,2) NOT NULL,
  `numero` int(11) DEFAULT NULL,
  `estado` varchar(20) NOT NULL,
  `enviada_at` datetime DEFAULT NULL,
  `pagada_at` datetime DEFAULT NULL,
  `comision_banco_hnl` decimal(10,2) DEFAULT NULL,
  `recibo_swift` int(11) DEFAULT NULL,
  `tasa_emision_eur_l` decimal(14,5) DEFAULT NULL,
  `tasa_emision_usd_l` decimal(14,5) DEFAULT NULL,
  `tasa_emision_fecha` varchar(16) DEFAULT NULL,
  `tasa_emision_source` varchar(64) DEFAULT NULL,
  `tasa_pago_eur_l` decimal(14,5) DEFAULT NULL,
  `tasa_pago_usd_l` decimal(14,5) DEFAULT NULL,
  `tasa_pago_fecha` varchar(16) DEFAULT NULL,
  `tasa_pago_source` varchar(64) DEFAULT NULL,
  `payment_proof_stored_filename` varchar(180) DEFAULT NULL,
  `payment_proof_original_name` varchar(255) DEFAULT NULL,
  `payment_proof_mime` varchar(127) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `client_id` int(11) NOT NULL,
  `lempiras_recibidos` decimal(12,2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `UNIQ_45AEAF669033212A19EB692189A505776EC83E05` (`tenant_id`,`client_id`,`anio`,`mes`),
  KEY `IDX_45AEAF6619EB6921` (`client_id`),
  CONSTRAINT `FK_45AEAF6619EB6921` FOREIGN KEY (`client_id`) REFERENCES `work_client` (`id`),
  CONSTRAINT `FK_work_invoice_client` FOREIGN KEY (`client_id`) REFERENCES `work_client` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_invoice`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_invoice` WRITE;
/*!40000 ALTER TABLE `work_invoice` DISABLE KEYS */;
INSERT INTO `work_invoice` VALUES
(1,1,2026,1,21,21,1100.00,262.50,1362.50,28,'enviada','2026-01-30 09:00:00','2026-02-02 12:00:00',746.53,28,29.28020,26.62325,'30/01/2026','inferred_grid_equiv_20260518',29.28020,26.62325,'02/02/2026','copy_emission_after_grid_infer','65875abec34ad41e88a18f482ccdcd3b.pdf','PAGO DAVID SOLORZANO ENERO 2026.pdf','application/pdf','2026-01-30 09:00:00',1,NULL),
(2,1,2026,2,20,20,1100.00,250.00,1350.00,29,'enviada','2026-02-27 09:00:00','2026-03-02 12:00:00',743.07,29,29.28020,26.62325,'27/02/2026','inferred_grid_equiv_20260518',29.28020,26.62325,'02/03/2026','copy_emission_after_grid_infer',NULL,NULL,NULL,'2026-02-27 09:00:00',1,NULL),
(3,1,2026,3,22,22,1100.00,275.00,1375.00,30,'enviada','2026-03-31 09:00:00','2026-03-31 12:00:00',734.94,30,29.28020,26.62325,'31/03/2026','inferred_grid_equiv_20260518',29.28020,26.62325,'31/03/2026','copy_emission_after_grid_infer','0d0d9986dd15760c188a96cd5edd73a4.pdf','PAGO CHRISTIAN DAVID SOLORZANO MARZO 2026.pdf','application/pdf','2026-03-31 09:00:00',1,NULL),
(4,1,2026,4,19,19,1100.00,237.50,1337.50,31,'enviada','2026-04-30 09:00:00','2026-05-09 12:00:00',737.45,31,29.28020,26.62325,'30/04/2026','inferred_grid_equiv_20260518',29.28020,26.62325,'09/05/2026','copy_emission_after_grid_infer','af8b1d6117b27d2009bc796e99379b41.pdf','PAGO CHRISTIAN DAVID ABRIL 2026.pdf','application/pdf','2026-04-30 09:00:00',1,NULL);
/*!40000 ALTER TABLE `work_invoice` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_invoice_bonus_line`
--

DROP TABLE IF EXISTS `work_invoice_bonus_line`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_invoice_bonus_line` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `importe_eur` decimal(10,2) NOT NULL,
  `concepto` varchar(255) DEFAULT NULL,
  `invoice_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `IDX_C7B49B7C2989F1FD` (`invoice_id`),
  CONSTRAINT `FK_C7B49B7C2989F1FD` FOREIGN KEY (`invoice_id`) REFERENCES `work_invoice` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_work_invoice_bonus_line_invoice` FOREIGN KEY (`invoice_id`) REFERENCES `work_invoice` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_invoice_bonus_line`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_invoice_bonus_line` WRITE;
/*!40000 ALTER TABLE `work_invoice_bonus_line` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_invoice_bonus_line` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_vacation`
--

DROP TABLE IF EXISTS `work_vacation`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_vacation` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `dias` int(11) NOT NULL,
  `semestre` int(11) NOT NULL,
  `anio` int(11) NOT NULL,
  `notas` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_vacation`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_vacation` WRITE;
/*!40000 ALTER TABLE `work_vacation` DISABLE KEYS */;
/*!40000 ALTER TABLE `work_vacation` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

--
-- Table structure for table `work_holiday`
--

DROP TABLE IF EXISTS `work_holiday`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `work_holiday` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `anio` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_work_holiday_tenant_fecha` (`tenant_id`,`fecha`),
  KEY `idx_work_holiday_anio` (`anio`)
) ENGINE=InnoDB AUTO_INCREMENT=15 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `work_holiday`
--

SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, @@AUTOCOMMIT=0;
LOCK TABLES `work_holiday` WRITE;
/*!40000 ALTER TABLE `work_holiday` DISABLE KEYS */;
INSERT INTO `work_holiday` VALUES
(1,1,'2026-01-01','Año Nuevo',2026),
(2,1,'2026-01-06','Día de los Reyes Magos',2026),
(3,1,'2026-02-03','Nuestra Señora de Suyapa',2026),
(4,1,'2026-04-02','Jueves Santo',2026),
(5,1,'2026-04-03','Viernes Santo',2026),
(6,1,'2026-04-14','Día de las Américas',2026),
(7,1,'2026-05-01','Día del Trabajo',2026),
(8,1,'2026-09-15','Día de la Independencia',2026),
(9,1,'2026-10-01','Día de Choluteca',2026),
(10,1,'2026-10-07','Día del Soldado',2026),
(11,1,'2026-10-08','Día de la Raza',2026),
(12,1,'2026-10-09','Día de las Fuerzas Armadas',2026),
(13,1,'2026-11-01','Día de Todos los Santos',2026),
(14,1,'2026-12-25','Navidad',2026);
/*!40000 ALTER TABLE `work_holiday` ENABLE KEYS */;
UNLOCK TABLES;
COMMIT;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*M!100616 SET NOTE_VERBOSITY=@OLD_NOTE_VERBOSITY */;

-- Dump completed on 2026-05-21  1:02:36
