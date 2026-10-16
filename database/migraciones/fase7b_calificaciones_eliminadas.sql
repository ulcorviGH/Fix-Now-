-- fase7b_calificaciones_eliminadas.sql
-- ---------------------------------------------------------------------------
-- SOLO para una base de datos que YA EXISTE (con fase7_alter.sql ya aplicado).
-- Ejecutar UNA vez desde la pestaña "SQL" de phpMyAdmin (base "fixnow").
-- Si creás la base desde cero, 01_init.sql ya trae la columna.
--
-- Qué agrega: SOLICITUD.calificaciones_eliminadas, un contador de cuántas
-- calificaciones de ese trabajo eliminó el Administrador (CU-14). Permite la regla:
--   1ra baja -> el cliente puede volver a calificar.
--   2da baja (con el mismo trabajador) -> ya no puede calificarlo más.
-- ---------------------------------------------------------------------------
ALTER TABLE SOLICITUD
    ADD COLUMN calificaciones_eliminadas TINYINT UNSIGNED NOT NULL DEFAULT 0;
