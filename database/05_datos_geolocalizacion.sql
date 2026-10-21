-- database/05_datos_geolocalizacion.sql
-- ---------------------------------------------------------------------------
-- Fase 8b - Coordenadas SIMULADAS para los trabajadores de prueba, así la
-- búsqueda por radio (slider de km) se puede demostrar sin cargar datos a mano.
-- Docker lo ejecuta solo, después de 01..04, SOLO al crear la base desde cero.
-- (Si ya tenés una base creada, usá database/migraciones/migracion_geolocalizacion.sql)
-- Referencia: San Carlos Centro (-31.6667, -61.1167), Esperanza (-31.4500, -60.9333),
--             Santa Fe Capital (-31.6333, -60.7000).
-- ---------------------------------------------------------------------------

USE fixnow;

UPDATE USUARIO SET latitud = -31.672000, longitud = -61.110000 WHERE mail = 'trabajador@fixnow.com'; -- Carlos: ~1 km del centro de San Carlos
UPDATE USUARIO SET latitud = -31.448000, longitud = -60.940000 WHERE mail = 'rocio@fixnow.com';      -- Rocío: Esperanza, ~30 km
UPDATE USUARIO SET latitud = -31.680000, longitud = -61.100000 WHERE mail = 'nicolas@fixnow.com';    -- Nicolás: San Carlos Sud, ~3 km
UPDATE USUARIO SET latitud = -31.640000, longitud = -60.710000 WHERE mail = 'ariana@fixnow.com';     -- Ariana: Santa Fe Capital, ~50 km
