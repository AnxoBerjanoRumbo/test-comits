-- ============================================================
-- ARK SURVIVAL HUB - Script de Base de Datos PROFESIONAL
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Tabla: mapas
DROP TABLE IF EXISTS `mapas`;
CREATE TABLE `mapas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre_mapa` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `mapas` (`id`, `nombre_mapa`, `descripcion`) VALUES
(1, 'Ragnarok', 'Extenso mapa con biomas de volcanes, nieve y desiertos.'),
(2, 'Aberration', 'Sistema de cuevas subterráneas con radiación y mutaciones.'),
(3, 'Extinction', 'La Tierra devastada por el Elemento y Titanes.'),
(4, 'Genesis', 'Simulación con 5 biomas extremos y IA náufraga.'),
(5, 'The Island', 'La isla ARK clásica, corazón de la civilización.'),
(6, 'Scorched Earth', 'Desierto implacable con tormentas eléctricas.'),
(7, 'Valguero', 'Mapa con castillos, acantilados y una red de cuevas masiva.'),
(8, 'Crystal Isles', 'Paisajes exóticos con cristales de colores y biomas únicos.'),
(9, 'Genesis Part 2', 'Nave colonial masiva dividida en dos sectores bióticos.'),
(10, 'Lost Island', 'Mapa masivo con ruinas y especies exclusivas.'),
(11, 'Fjordur', 'Inspiración nórdica con reinos helados y mitología.');

-- 2. Tabla: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('usuario', 'admin', 'superadmin') DEFAULT 'usuario',
  `foto_perfil` varchar(255) DEFAULT 'default.png',
  `permiso_insertar_dino` tinyint(1) DEFAULT 1,
  `permiso_eliminar_comentario` tinyint(1) DEFAULT 1,
  `permiso_moderar_usuarios` tinyint(1) DEFAULT 0,
  `recuperar_token` varchar(100) DEFAULT NULL,
  `recuperar_expira` datetime DEFAULT NULL,
  `baneado_hasta` datetime DEFAULT NULL,
  `motivo_ban` text DEFAULT NULL,
  `ban_permanente` tinyint(1) DEFAULT 0,
  `intentos_fallidos` int(11) DEFAULT 0,
  `ultimo_fallo` datetime DEFAULT NULL,
  `verificado` tinyint(1) DEFAULT 0,
  `codigo_verificacion` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nick` (`nick`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar Superadmin Inicial (Nick: Anxo | Pass: Tu contraseña real)
INSERT INTO `usuarios` (`nick`, `email`, `password`, `rol`, `verificado`) VALUES
('Anxo', 'admin@arkhub.com', '$2y$10$NbR8VWcRc1lka3Z8pc.3jOD/nof.OSTVxcKgDuLAMjyrDF70nCN4m', 'superadmin', 1);

-- 3. Tabla: dinosaurios
DROP TABLE IF EXISTS `dinosaurios`;
CREATE TABLE `dinosaurios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `especie` varchar(100) DEFAULT NULL,
  `dieta` varchar(50) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT 'default_dino.jpg',
  `audio_url` varchar(500) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `stat_health` int(11) DEFAULT 0,
  `stat_stamina` int(11) DEFAULT 0,
  `stat_oxygen` int(11) DEFAULT 0,
  `stat_food` int(11) DEFAULT 0,
  `stat_weight` int(11) DEFAULT 0,
  `stat_melee` int(11) DEFAULT 0,
  `stat_speed` int(11) DEFAULT 0,
  `stat_torpidity` int(11) DEFAULT 0,
  `iw_health` decimal(5,3) DEFAULT 0.200,
  `iw_stamina` decimal(5,3) DEFAULT 0.100,
  `iw_oxygen` decimal(5,3) DEFAULT 0.100,
  `iw_food` decimal(5,3) DEFAULT 0.150,
  `iw_weight` decimal(5,3) DEFAULT 0.020,
  `iw_melee` decimal(5,3) DEFAULT 0.050,
  `iw_speed` decimal(5,3) DEFAULT 0.000,
  `iw_torpidity` decimal(5,3) DEFAULT 0.060,
  `es_tanque` tinyint(1) DEFAULT 0,
  `es_buff` tinyint(1) DEFAULT 0,
  `es_recolector` tinyint(1) DEFAULT 0,
  `es_montura` tinyint(1) DEFAULT 0,
  `es_volador` tinyint(1) DEFAULT 0,
  `es_acuatico` tinyint(1) DEFAULT 0,
  `es_subterraneo` tinyint(1) DEFAULT 0,
  `buff_descripcion` text DEFAULT NULL,
  `buff_damage` decimal(5,2) DEFAULT 0.00,
  `buff_armor` decimal(5,2) DEFAULT 0.00,
  `buff_speed` decimal(5,2) DEFAULT 0.00,
  `buff_otro` text DEFAULT NULL,
  `tiene_formas` tinyint(1) DEFAULT 0,
  `formas_descripcion` text DEFAULT NULL,
  `recolecta_carne` tinyint(1) DEFAULT 0,
  `recolecta_pescado` tinyint(1) DEFAULT 0,
  `recolecta_madera` tinyint(1) DEFAULT 0,
  `recolecta_piedra` tinyint(1) DEFAULT 0,
  `recolecta_metal` tinyint(1) DEFAULT 0,
  `recolecta_bayas` tinyint(1) DEFAULT 0,
  `recolecta_paja` tinyint(1) DEFAULT 0,
  `recolecta_fibra` tinyint(1) DEFAULT 0,
  `recolecta_texugo` tinyint(1) DEFAULT 0,
  `domable` tinyint(1) DEFAULT 1,
  `metodo_domado` varchar(50) DEFAULT NULL,
  `comida_favorita` varchar(50) DEFAULT NULL,
  `nivel_max_salvaje` int(11) DEFAULT 150,
  `tiempo_incubacion` int(11) DEFAULT 0,
  `tiempo_madurez` int(11) DEFAULT 0,
  `ayuda_cria` tinyint(1) DEFAULT 0,
  `ayuda_cria_descripcion` text DEFAULT NULL,
  `region_0_nombre` varchar(60) DEFAULT NULL,
  `region_0_colores` varchar(255) DEFAULT NULL,
  `region_1_nombre` varchar(60) DEFAULT NULL,
  `region_1_colores` varchar(255) DEFAULT NULL,
  `region_2_nombre` varchar(60) DEFAULT NULL,
  `region_2_colores` varchar(255) DEFAULT NULL,
  `region_3_nombre` varchar(60) DEFAULT NULL,
  `region_3_colores` varchar(255) DEFAULT NULL,
  `region_4_nombre` varchar(60) DEFAULT NULL,
  `region_4_colores` varchar(255) DEFAULT NULL,
  `region_5_nombre` varchar(60) DEFAULT NULL,
  `region_5_colores` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre`),
  KEY `idx_roles` (`es_tanque`,`es_buff`,`es_recolector`,`es_montura`,`es_volador`,`es_acuatico`,`es_subterraneo`),
  KEY `idx_dieta` (`dieta`),
  KEY `idx_stat_health` (`stat_health`),
  KEY `idx_stat_weight` (`stat_weight`),
  KEY `idx_stat_melee` (`stat_melee`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 4. Tabla: dino_mapas
DROP TABLE IF EXISTS `dino_mapas`;
CREATE TABLE `dino_mapas` (
  `dino_id` int(11) NOT NULL,
  `mapa_id` int(11) NOT NULL,
  PRIMARY KEY (`dino_id`,`mapa_id`),
  CONSTRAINT `fk_dino` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_mapa` FOREIGN KEY (`mapa_id`) REFERENCES `mapas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `dino_mapas` (`dino_id`, `mapa_id`) VALUES (1,1), (1,5), (2,1), (2,5), (3,5), (3,7);

-- 5. Tabla: comentarios
DROP TABLE IF EXISTS `comentarios`;
CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `texto` text NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `dino_id` int(11) DEFAULT NULL,
  `respuesta_a` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_comentario_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentario_dino` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_comentario_reply` FOREIGN KEY (`respuesta_a`) REFERENCES `comentarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 6. Tabla: emails_bloqueados
DROP TABLE IF EXISTS `emails_bloqueados`;
CREATE TABLE `emails_bloqueados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_bloqueo` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Tabla: musica
DROP TABLE IF EXISTS `musica`;
CREATE TABLE `musica` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `archivo` varchar(255) NOT NULL,
  `title` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `musica` (`archivo`, `title`) VALUES
('ARK - Main Theme [rYyi_vvH2F8].mp3', 'Main Theme'),
('ARK - Scorched Earth Theme.mp3', 'Scorched Earth'),
('ARK - Aberration Theme.mp3', 'Aberration'),
('ARK - Extinction Theme.mp3', 'Extinction'),
('ARK - Genesis (Part 1) Theme.mp3', 'Genesis (Part 1)'),
('ARK - Genesis (Part 2) Theme.mp3', 'Genesis (Part 2)'),
('ARK - Loading Screen.mp3', 'Loading Screen'),
('ARK - Character Creation & Respawn Screen [KTK-zzK1fxg].mp3', 'Character Creation'),
('ARK - Boss Battle Theme - Broodmother Lysrix [aM6a-2cfl9E].mp3', 'Boss: Broodmother'),
('ARK - Battle Theme - Scorched Earth Night [iqmHMq6qnnE].mp3', 'Battle: Scorched Night'),
('ARK - Winter Wonderland Theme.mp3', 'Winter Wonderland');

SET FOREIGN_KEY_CHECKS = 1;
