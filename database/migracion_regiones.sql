-- ============================================================
-- MIGRACIÓN: Añadir regiones de color a la tabla dinosaurios
-- Ejecutar en phpMyAdmin sobre la BD ark_hub
-- ============================================================

ALTER TABLE `dinosaurios`
  ADD COLUMN `region_0_nombre`  varchar(60)  DEFAULT NULL AFTER `ayuda_cria_descripcion`,
  ADD COLUMN `region_0_colores` varchar(255) DEFAULT NULL AFTER `region_0_nombre`,
  ADD COLUMN `region_1_nombre`  varchar(60)  DEFAULT NULL AFTER `region_0_colores`,
  ADD COLUMN `region_1_colores` varchar(255) DEFAULT NULL AFTER `region_1_nombre`,
  ADD COLUMN `region_2_nombre`  varchar(60)  DEFAULT NULL AFTER `region_1_colores`,
  ADD COLUMN `region_2_colores` varchar(255) DEFAULT NULL AFTER `region_2_nombre`,
  ADD COLUMN `region_3_nombre`  varchar(60)  DEFAULT NULL AFTER `region_2_colores`,
  ADD COLUMN `region_3_colores` varchar(255) DEFAULT NULL AFTER `region_3_nombre`,
  ADD COLUMN `region_4_nombre`  varchar(60)  DEFAULT NULL AFTER `region_3_colores`,
  ADD COLUMN `region_4_colores` varchar(255) DEFAULT NULL AFTER `region_4_nombre`,
  ADD COLUMN `region_5_nombre`  varchar(60)  DEFAULT NULL AFTER `region_4_colores`,
  ADD COLUMN `region_5_colores` varchar(255) DEFAULT NULL AFTER `region_5_nombre`;
