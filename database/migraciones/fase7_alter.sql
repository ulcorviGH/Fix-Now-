-- fase7_alter.sql
-- ---------------------------------------------------------------------------
-- SOLO para una base de datos que YA EXISTE (creada antes de la Fase 7).
-- Ejecutar UNA vez desde la pestaña "SQL" de phpMyAdmin (base "fixnow").
--
-- Si creás la base desde cero (docker compose down -v), NO hace falta:
-- 01_init.sql ya trae estas columnas.
--
-- Qué agrega y para qué:
--   USUARIO.intentos_fallidos / bloqueado_hasta -> bloqueo temporal de login (CU-02 exc. 3.2)
--   USUARIO.ultimo_acceso                       -> "usuarios activos último mes" (CU-16)
--   TRABAJADOR.motivo_rechazo                   -> motivo al rechazar una cuenta (CU-18)
--   SERVICIO.motivo_rechazo / IMAGEN.motivo_rechazo -> motivo al rechazar contenido (CU-19)
--   CALIFICACION.motivo_reporte                 -> motivo al reportar una reseña (CU-14)
-- ---------------------------------------------------------------------------
ALTER TABLE USUARIO
    ADD COLUMN intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN bloqueado_hasta   DATETIME NULL,
    ADD COLUMN ultimo_acceso     DATETIME NULL;

ALTER TABLE TRABAJADOR   ADD COLUMN motivo_rechazo VARCHAR(255) NULL;
ALTER TABLE SERVICIO     ADD COLUMN motivo_rechazo VARCHAR(255) NULL;
ALTER TABLE IMAGEN       ADD COLUMN motivo_rechazo VARCHAR(255) NULL;
ALTER TABLE CALIFICACION ADD COLUMN motivo_reporte VARCHAR(255) NULL;
