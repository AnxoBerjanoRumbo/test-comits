-- ============================================================
-- MIGRACIÓN: Añadir índices para mejorar rendimiento
-- Ejecutar en phpMyAdmin sobre la BD ark_hub
-- ============================================================

-- Búsqueda de criaturas por nombre (buscador principal)
ALTER TABLE `dinosaurios` ADD INDEX `idx_nombre` (`nombre`);

-- Filtros de rol en el buscador
ALTER TABLE `dinosaurios` ADD INDEX `idx_roles` (`es_tanque`, `es_buff`, `es_recolector`, `es_montura`, `es_volador`, `es_acuatico`, `es_subterraneo`);

-- Filtro por dieta
ALTER TABLE `dinosaurios` ADD INDEX `idx_dieta` (`dieta`);

-- Filtros de stats (búsqueda por rango)
ALTER TABLE `dinosaurios` ADD INDEX `idx_stat_health` (`stat_health`);
ALTER TABLE `dinosaurios` ADD INDEX `idx_stat_weight` (`stat_weight`);
ALTER TABLE `dinosaurios` ADD INDEX `idx_stat_melee`  (`stat_melee`);

-- Notificaciones: consulta frecuente por usuario + leída
-- (ya existe idx_usuario_leida en el SQL principal)

-- Comentarios: consulta por dino_id (paginación de comentarios)
ALTER TABLE `comentarios` ADD INDEX `idx_dino_id` (`dino_id`);
ALTER TABLE `comentarios` ADD INDEX `idx_usuario_id` (`usuario_id`);

-- Admin logs: consulta por usuario y fecha
ALTER TABLE `admin_logs` ADD INDEX `idx_id_usuario` (`id_usuario`);
ALTER TABLE `admin_logs` ADD INDEX `idx_fecha` (`fecha`);

-- Usuarios: búsqueda por nick y email (login, registro)
ALTER TABLE `usuarios` ADD INDEX `idx_nick` (`nick`);
ALTER TABLE `usuarios` ADD INDEX `idx_email` (`email`);
-- Nota: nick y email ya tienen UNIQUE KEY que actúa como índice,
-- estas líneas son por si se eliminaron en alguna migración.
-- Si da error "Duplicate key name", ignorar.
