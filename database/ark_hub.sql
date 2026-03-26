-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: ark_hub
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `comentarios`
--

DROP TABLE IF EXISTS `comentarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `texto` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `dino_id` int(11) DEFAULT NULL,
  `respuesta_a` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `dino_id` (`dino_id`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `comentarios`
--

LOCK TABLES `comentarios` WRITE;
/*!40000 ALTER TABLE `comentarios` DISABLE KEYS */;
INSERT INTO `comentarios` VALUES (4,'Eres un maleante',16,24,NULL),(6,'a callar',1,24,4);
/*!40000 ALTER TABLE `comentarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dino_mapas`
--

DROP TABLE IF EXISTS `dino_mapas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dino_mapas` (
  `dino_id` int(11) NOT NULL,
  `mapa_id` int(11) NOT NULL,
  PRIMARY KEY (`dino_id`,`mapa_id`),
  KEY `mapa_id` (`mapa_id`),
  CONSTRAINT `dino_mapas_ibfk_1` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`),
  CONSTRAINT `dino_mapas_ibfk_2` FOREIGN KEY (`mapa_id`) REFERENCES `mapas` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dino_mapas`
--

LOCK TABLES `dino_mapas` WRITE;
/*!40000 ALTER TABLE `dino_mapas` DISABLE KEYS */;
INSERT INTO `dino_mapas` VALUES (24,1),(25,2),(26,2);
/*!40000 ALTER TABLE `dino_mapas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dinosaurios`
--

DROP TABLE IF EXISTS `dinosaurios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dinosaurios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(100) DEFAULT NULL,
  `dieta` varchar(50) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT 'default_dino.jpg',
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `dinosaurios`
--

LOCK TABLES `dinosaurios` WRITE;
/*!40000 ALTER TABLE `dinosaurios` DISABLE KEYS */;
INSERT INTO `dinosaurios` VALUES (24,'raptor','raptor','Carnívoro','https://res.cloudinary.com/dzqjnmtcp/image/upload/v1773740744/dinos/qc6bpheg9lu781h273h0.jpg','wwwwwww'),(25,'raptor1','raptor','Carnívoro','https://res.cloudinary.com/dzqjnmtcp/image/upload/v1773821187/dinos/ipdcukgn7fagbovfgtak.jpg','qqqqqqqqqqqq'),(26,'raptor2','raptor','Carnívoro','https://res.cloudinary.com/dzqjnmtcp/image/upload/v1773821213/dinos/bawlkapa7a23xsjmq9b9.jpg','333333333333333333333333333333333');
/*!40000 ALTER TABLE `dinosaurios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `emails_bloqueados`
--

DROP TABLE IF EXISTS `emails_bloqueados`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `emails_bloqueados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_bloqueo` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `emails_bloqueados`
--

LOCK TABLES `emails_bloqueados` WRITE;
/*!40000 ALTER TABLE `emails_bloqueados` DISABLE KEYS */;
/*!40000 ALTER TABLE `emails_bloqueados` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mapas`
--

DROP TABLE IF EXISTS `mapas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mapas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_mapa` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `mapas`
--

LOCK TABLES `mapas` WRITE;
/*!40000 ALTER TABLE `mapas` DISABLE KEYS */;
INSERT INTO `mapas` VALUES (1,'Ragnarok','Mapa que combina biomas de The Island y Scorched Earth con zonas únicas.'),(2,'Aberration','Un mapa subterráneo con mutaciones y sin voladores.'),(3,'Extinction','La Tierra devastada llena de elementos y titanes.'),(4,'Genesis','Simulación con diversos biomas extremos y misiones.');
/*!40000 ALTER TABLE `mapas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'usuario',
  `foto_perfil` varchar(255) DEFAULT 'default.png',
  `permiso_insertar_dino` tinyint(1) DEFAULT 1,
  `permiso_eliminar_comentario` tinyint(1) DEFAULT 1,
  `recuperar_token` varchar(100) DEFAULT NULL,
  `recuperar_expira` datetime DEFAULT NULL,
  `permiso_moderar_usuarios` tinyint(1) DEFAULT 0,
  `baneado_hasta` datetime DEFAULT NULL,
  `motivo_ban` text DEFAULT NULL,
  `ban_permanente` tinyint(1) DEFAULT 0,
  `intentos_fallidos` int(11) DEFAULT 0,
  `ultimo_fallo` datetime DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `codigo_verificacion` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Anxo',NULL,'$2y$10$NbR8VWcRc1lka3Z8pc.3jOD/nof.OSTVxcKgDuLAMjyrDF70nCN4m','superadmin','https://res.cloudinary.com/dzqjnmtcp/image/upload/v1773740746/perfiles/tgaqxbw28kbz69lqno0m.jpg',1,1,NULL,NULL,0,NULL,NULL,0,0,NULL,1,NULL),(10,'juankiller69',NULL,'$2y$10$u1B.bljX7b7in3NK8PRXwOker1SGuiRbA8ChFW27m58i5e1fkmiFS','usuario','https://res.cloudinary.com/dzqjnmtcp/image/upload/v1773740747/perfiles/vjvzuupza2ghwv04ydym.jpg',1,1,NULL,NULL,0,NULL,NULL,0,0,NULL,1,NULL),(14,'admin0','anxoberjano+3@gmail.com','$2y$10$4obClLCfnlSwdGqIdWeW1.fKQuxsNVOmoaWpDOErQrmwMqe0LMJHi','admin','default.png',1,1,NULL,NULL,0,NULL,NULL,0,0,NULL,1,NULL),(15,'admin1','anxoberjano+4@gmail.com','$2y$10$upMaElw1HI5.JsOnGHNYUe2FVj1FJd48fGVqMVTMZgWi1EBydyrry','admin','default.png',1,1,NULL,NULL,1,NULL,NULL,0,0,NULL,1,NULL),(16,'testeo1','test@gmail.com','$2y$10$L/vVmJdP76heyOYDNlr..ukosEgiG7YNYD8wAOF1UBjhNHyPkX9Hu','usuario','default.png',1,1,NULL,NULL,0,NULL,NULL,0,0,NULL,1,NULL);
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-18 10:53:39
