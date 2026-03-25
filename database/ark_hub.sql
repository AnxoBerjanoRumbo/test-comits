-- ============================================================
-- ARK SURVIVAL HUB - Script de Base de Datos
-- Importar en phpMyAdmin sobre la base de datos: ark_hub
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Tabla: emails_bloqueados
CREATE TABLE IF NOT EXISTS `emails_bloqueados` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(150) NOT NULL,
  `motivo` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: mapas
CREATE TABLE IF NOT EXISTS `mapas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `mapas` (`id`, `nombre`) VALUES
(1, 'The Island'),
(2, 'The Center'),
(3, 'Scorched Earth'),
(4, 'Ragnarok'),
(5, 'Aberration'),
(6, 'Extinction'),
(7, 'Valguero'),
(8, 'Genesis Part 1'),
(9, 'Crystal Isles'),
(10, 'Genesis Part 2'),
(11, 'Lost Island'),
(12, 'Fjordur');

-- Tabla: usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('usuario','admin','superadmin') NOT NULL DEFAULT 'usuario',
  `foto_perfil` varchar(255) DEFAULT 'default.png',
  `descripcion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp(),
  `baneado_hasta` datetime DEFAULT NULL,
  `motivo_ban` text DEFAULT NULL,
  `ban_permanente` tinyint(1) NOT NULL DEFAULT 0,
  `recuperar_token` varchar(100) DEFAULT NULL,
  `recuperar_expira` datetime DEFAULT NULL,
  `p_insertar_dino` tinyint(1) NOT NULL DEFAULT 0,
  `p_eliminar_dino` tinyint(1) NOT NULL DEFAULT 0,
  `p_moderar` tinyint(1) NOT NULL DEFAULT 0,
  `permiso_insertar_dino` tinyint(1) NOT NULL DEFAULT 0,
  `permiso_eliminar_comentario` tinyint(1) NOT NULL DEFAULT 0,
  `intentos_fallidos` int(11) NOT NULL DEFAULT 0,
  `ultimo_fallo` datetime DEFAULT NULL,
  `verificado` tinyint(1) NOT NULL DEFAULT 0,
  `codigo_verificacion` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nick` (`nick`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario Superadmin inicial (contraseña: Admin1234)
INSERT IGNORE INTO `usuarios` (`nick`, `email`, `password`, `rol`, `verificado`) VALUES
('superadmin', 'superadmin@arkhub.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'superadmin', 1);

-- Tabla: dinosaurios
CREATE TABLE IF NOT EXISTS `dinosaurios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `dieta` varchar(50) DEFAULT NULL,
  `peligrosidad` varchar(50) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL,
  `como_domar` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: dino_mapas
CREATE TABLE IF NOT EXISTS `dino_mapas` (
  `dino_id` int(11) NOT NULL,
  `mapa_id` int(11) NOT NULL,
  PRIMARY KEY (`dino_id`,`mapa_id`),
  KEY `mapa_id` (`mapa_id`),
  CONSTRAINT `dino_mapas_ibfk_1` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `dino_mapas_ibfk_2` FOREIGN KEY (`mapa_id`) REFERENCES `mapas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla: comentarios
CREATE TABLE IF NOT EXISTS `comentarios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `texto` text NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `dino_id` int(11) NOT NULL,
  `respuesta_a` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `dino_id` (`dino_id`),
  KEY `respuesta_a` (`respuesta_a`),
  CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`dino_id`) REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `comentarios_ibfk_3` FOREIGN KEY (`respuesta_a`) REFERENCES `comentarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
