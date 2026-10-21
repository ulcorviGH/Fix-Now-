-- database/migraciones/migracion_geolocalizacion.sql
-- ---------------------------------------------------------------------------
-- Fase 8b - Geolocalización por radio. Para una base de datos YA CREADA:
-- ejecutarlo en phpMyAdmin (pestaña SQL, con la base "fixnow" seleccionada).
-- Es seguro correrlo más de una vez: solo agrega las columnas si no existen.
-- En una base NUEVA no hace falta: 01_init.sql y 05_datos_geolocalizacion.sql ya lo incluyen.
-- ---------------------------------------------------------------------------

-- 1) Columna latitud en USUARIO (si no existe)
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'USUARIO' AND COLUMN_NAME = 'latitud');
SET @sentencia := IF(@existe = 0,
    'ALTER TABLE USUARIO ADD COLUMN latitud DECIMAL(9,6) NULL',
    'SELECT ''latitud ya existe'' AS aviso');
PREPARE paso FROM @sentencia; EXECUTE paso; DEALLOCATE PREPARE paso;

-- 2) Columna longitud en USUARIO (si no existe)
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'USUARIO' AND COLUMN_NAME = 'longitud');
SET @sentencia := IF(@existe = 0,
    'ALTER TABLE USUARIO ADD COLUMN longitud DECIMAL(9,6) NULL',
    'SELECT ''longitud ya existe'' AS aviso');
PREPARE paso FROM @sentencia; EXECUTE paso; DEALLOCATE PREPARE paso;

-- 3) Coordenadas simuladas para los trabajadores de prueba
UPDATE USUARIO SET latitud = -31.672000, longitud = -61.110000 WHERE mail = 'trabajador@fixnow.com'; -- Carlos: ~1 km del centro de San Carlos
UPDATE USUARIO SET latitud = -31.448000, longitud = -60.940000 WHERE mail = 'rocio@fixnow.com';      -- Rocío: Esperanza, ~30 km
UPDATE USUARIO SET latitud = -31.680000, longitud = -61.100000 WHERE mail = 'nicolas@fixnow.com';    -- Nicolás: San Carlos Sud, ~3 km
UPDATE USUARIO SET latitud = -31.640000, longitud = -60.710000 WHERE mail = 'ariana@fixnow.com';     -- Ariana: Santa Fe Capital, ~50 km
