SET NAMES utf8mb4;
USE fixnow;
-- crear_admin_prueba.sql
-- ---------------------------------------------------------------------------
-- Ejecutar UNA vez, a mano, desde la pestaña "SQL" de phpMyAdmin
-- (elegí la base "fixnow" primero, arriba a la izquierda).
--
-- Crea un usuario Administrador de prueba:
--   Mail:        admin@fixnow.com
--   Contraseña:  admin123
--
-- La contraseña de abajo YA está hasheada con password_hash() de PHP
-- (bcrypt). Nunca se escribe una contraseña en texto plano en la base.
-- ---------------------------------------------------------------------------

INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES (
    'Admin',
    'FixNow',
    'admin@fixnow.com',
    '$2y$10$GBZsxlXfigDOIj/f3b9NTuEoYSUwWAUPBibJZdQqWtJAcI4ALHmSC',
    'admin',
    1,              -- San Carlos Centro (id_localidad = 1, ver database/init.sql)
    'activo'
);

INSERT INTO ADMINISTRADOR (id_usuario)
VALUES (LAST_INSERT_ID());
