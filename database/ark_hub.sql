-- ============================================================
-- ARK SURVIVAL HUB - Script de Base de Datos para Profesores
-- Versión limpia: Incluye Superadmin, todos los Mapas y 3 Dinos de ejemplo.
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
(1, 'Ragnarok', 'Mapa que combina biomas de The Island y Scorched Earth con zonas únicas.'),
(2, 'Aberration', 'Un mapa subterráneo con mutaciones y sin voladores.'),
(3, 'Extinction', 'La Tierra devastada llena de elementos y titanes.'),
(4, 'Genesis', 'Simulación con diversos biomas extremos y misiones.'),
(5, 'The Island', 'El mapa original de ARK, una isla tropical con diversos biomas.'),
(6, 'Scorched Earth', 'Un mapa desértico extremo con tormentas de arena y calor sofocante.'),
(7, 'Valguero', 'Un mapa de hermosos paisajes y zonas subterráneas.'),
(8, 'Crystal Isles', 'Un mapa de fantasía con cristales gigantes y biomas exóticos.'),
(9, 'Genesis Part 2', 'Un mapa futurista dividido en dos zonas masivas.'),
(10, 'Lost Island', 'Un gran mapa con ruinas y especies únicas.'),
(11, 'Fjordur', 'Un mapa nórdico con reinos helados y mitología.');

-- 2. Tabla: usuarios
DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `rol` varchar(20) DEFAULT 'usuario',
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Insertar Superadmin (Anxo)
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
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

INSERT INTO `dinosaurios` (`id`, `nombre`, `especie`, `dieta`, `descripcion`) VALUES
(1, 'Tyrannosaurus Rex', 'T. Dominum', 'Carnívoro', 'El rey de los dinosaurios, una fuerza imparable en el campo de batalla.'),
(2, 'Velociraptor', 'V. Primus', 'Carnívoro', 'Rápido, letal y extremadamente inteligente. Caza en manadas.'),
(3, 'Stegosaurus', 'S. Regium', 'Herbívoro', 'Un tanque blindado con placas defensivas y una cola devastadora.');

-- 4. Tabla: dino_mapas
DROP TABLE IF EXISTS `dino_mapas`;
CREATE TABLE `dino_mapas` (
  `dino_id` int(11) NOT NULL,
  `mapa_id` int(11) NOT NULL,
  PRIMARY KEY (`dino_id`,`mapa_id`),
  CONSTRAINT `dino_mapas_ibfk_1` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dino_mapas_ibfk_2` FOREIGN KEY (`mapa_id`) REFERENCES `mapas` (`id`) ON DELETE CASCADE
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
  PRIMARY KEY (`id`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- 6. Tabla: emails_bloqueados
DROP TABLE IF EXISTS `emails_bloqueados`;
CREATE TABLE `emails_bloqueados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha_bloqueo` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
