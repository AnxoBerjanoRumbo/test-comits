-- ============================================================
-- MIGRACIÓN: Crear tabla de favoritos
-- Ejecutar en phpMyAdmin sobre la BD ark_hub
-- ============================================================

CREATE TABLE IF NOT EXISTS `favoritos` (
  `id`         int(11)   NOT NULL AUTO_INCREMENT,
  `usuario_id` int(11)   NOT NULL,
  `dino_id`    int(11)   NOT NULL,
  `fecha`      timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_dino` (`usuario_id`, `dino_id`),
  KEY `idx_fav_usuario` (`usuario_id`),
  CONSTRAINT `fk_fav_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_fav_dino`    FOREIGN KEY (`dino_id`)    REFERENCES `dinosaurios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;
