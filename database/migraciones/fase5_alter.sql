-- fase5_alter.sql
-- ---------------------------------------------------------------------------
-- SOLO para una base de datos que YA EXISTE (creada antes de la Fase 5).
-- Ejecutar UNA vez desde la pestaña "SQL" de phpMyAdmin (base "fixnow").
--
-- Si creás la base desde cero (docker compose down -v), NO hace falta:
-- 01_init.sql ya trae estas columnas.
--
-- Esta carpeta (migraciones/) es una subcarpeta a propósito: Docker solo
-- ejecuta automáticamente los .sql que están directamente en database/.
-- ---------------------------------------------------------------------------
ALTER TABLE SOLICITUD
    ADD COLUMN posponer_cliente_hasta    DATETIME NULL,
    ADD COLUMN posponer_trabajador_hasta DATETIME NULL;
