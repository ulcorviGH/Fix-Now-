SET NAMES utf8mb4;
USE fixnow;
-- crear_datos_prueba.sql
-- ---------------------------------------------------------------------------
-- Ejecutar UNA sola vez, a mano, desde la pestaña "SQL" de phpMyAdmin
-- (con la base "fixnow" seleccionada a la izquierda).
--
-- Crea datos de ejemplo para poder probar la Fase 4 sin esperar al panel
-- del Administrador (que se construye más adelante):
--
--   TRABAJADOR (ya habilitado):  trabajador@fixnow.com / trabajador123
--       Carlos Gómez, Plomero, Esperanza + San Carlos Centro,
--       con 2 servicios aprobados, 3 fotos aprobadas y 1 reseña con respuesta.
--
--   CLIENTE de prueba:           cliente@fixnow.com / cliente123
--       Laura Pérez, de San Carlos Centro.
--
-- (El admin admin@fixnow.com / admin123 se crea con crear_admin_prueba.sql)
-- ---------------------------------------------------------------------------

-- ---------- Trabajador de prueba ----------
INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Carlos', 'Gómez', 'trabajador@fixnow.com',
        '$2y$10$Bsc.SYjePR5Ay1JLlu8MNeLgW8ZZRG/QBs11lXHjTeCuw3d0UZ.Qa',
        'trabajador', 2, 'activo');
SET @id_usuario_trab = LAST_INSERT_ID();

INSERT INTO TRABAJADOR (id_usuario, oficio, descripcion, anios_experiencia, estado_habilitacion)
VALUES (@id_usuario_trab, 'Plomero',
        'Plomero matriculado con experiencia en instalaciones domiciliarias, destape de cañerías y reparación de pérdidas. Trabajo prolijo y con garantía.',
        8, 'habilitado');
SET @id_trab = LAST_INSERT_ID();

-- Zonas de trabajo: 2 = Esperanza, 1 = San Carlos Centro (ver init.sql)
INSERT INTO TRABAJADOR_LOCALIDAD (id_trabajador, id_localidad) VALUES (@id_trab, 2), (@id_trab, 1);

INSERT INTO SERVICIO (id_trabajador, nombre, descripcion, estado_revision) VALUES
    (@id_trab, 'Destape de cañerías', 'Destape de cocina, baño y desagües con máquina eléctrica.', 'aprobado'),
    (@id_trab, 'Instalación de grifería', 'Colocación y cambio de canillas, mezcladores y flexibles.', 'aprobado');

-- Fotos de ejemplo (imágenes externas de relleno, solo para ver la galería)
INSERT INTO IMAGEN (id_trabajador, url, estado_revision) VALUES
    (@id_trab, 'https://placehold.co/400x300?text=Trabajo+1', 'aprobada'),
    (@id_trab, 'https://placehold.co/400x300?text=Trabajo+2', 'aprobada'),
    (@id_trab, 'https://placehold.co/400x300?text=Trabajo+3', 'aprobada');

-- ---------- Cliente de prueba ----------
INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Laura', 'Pérez', 'cliente@fixnow.com',
        '$2y$10$a6gJPCzmxUI/J3pIGkklw.UsTGdwqg1VjPO0uqXLMq3IZzWqdqL06',
        'cliente', 1, 'activo');
SET @id_usuario_cli = LAST_INSERT_ID();

-- ---------- Un trabajo ya completado por ambas partes + reseña + respuesta ----------
INSERT INTO SOLICITUD (id_usuario, id_trabajador, mensaje_inicial, estado_contacto,
                       estado_trabajo, fecha_confirmacion_trabajo,
                       confirmado_cliente, confirmado_trabajador)
VALUES (@id_usuario_cli, @id_trab, 'Hola, se me tapó la cañería de la cocina.', 'aceptada',
        'aceptado', NOW(), TRUE, TRUE);
SET @id_solicitud = LAST_INSERT_ID();

INSERT INTO CALIFICACION (id_solicitud, id_usuario, id_trabajador, puntuacion, comentario)
VALUES (@id_solicitud, @id_usuario_cli, @id_trab, 5,
        'Excelente trabajo, llegó puntual y dejó todo limpio. Muy recomendable.');
SET @id_calificacion = LAST_INSERT_ID();

INSERT INTO RESPUESTA (id_calificacion, contenido)
VALUES (@id_calificacion, '¡Muchas gracias, Laura! Un gusto haberte ayudado.');
