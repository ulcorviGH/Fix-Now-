SET NAMES utf8mb4;
USE fixnow;
-- 04_datos_prueba_fase7.sql
-- ---------------------------------------------------------------------------
-- Datos de ejemplo para PROBAR el panel del Administrador (Fase 7):
--   * 2 trabajadores PENDIENTES de habilitación (CU-18)
--       nicolas@fixnow.com  y  ariana@fixnow.com   (contraseña: trabajador123)
--       -> no pueden entrar hasta que el admin los habilite.
--   * 1 servicio y 1 foto PENDIENTES del trabajador de prueba (CU-19)
--   * 1 trabajadora habilitada (Rocío, electricista) con una reseña REPORTADA (CU-14)
--   * 1 cliente más (Bruno Peralta, bruno@fixnow.com / cliente123) para la lista de usuarios
--
-- Docker lo ejecuta solo al crear la base desde cero (después de 01, 02 y 03).
-- Si tu base YA existe: primero corré migraciones/fase7_alter.sql y después
-- corré ESTE archivo una vez, a mano, desde la pestaña SQL de phpMyAdmin.
-- ---------------------------------------------------------------------------

-- Hashes bcrypt ya calculados (los mismos que usan los usuarios de la Fase 4)
SET @hash_trab = '$2y$10$Bsc.SYjePR5Ay1JLlu8MNeLgW8ZZRG/QBs11lXHjTeCuw3d0UZ.Qa';  -- trabajador123
SET @hash_cli  = '$2y$10$a6gJPCzmxUI/J3pIGkklw.UsTGdwqg1VjPO0uqXLMq3IZzWqdqL06';  -- cliente123

-- ---------- Dos trabajadores pendientes de habilitación ----------
INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Nicolás', 'Farías', 'nicolas@fixnow.com', @hash_trab, 'trabajador', 1, 'pendiente');
INSERT INTO TRABAJADOR (id_usuario, oficio, estado_habilitacion)
VALUES (LAST_INSERT_ID(), 'Cerrajero', 'pendiente');
INSERT INTO TRABAJADOR_LOCALIDAD (id_trabajador, id_localidad)
VALUES (LAST_INSERT_ID(), 1), (LAST_INSERT_ID(), 2);

INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Ariana', 'Bustos', 'ariana@fixnow.com', @hash_trab, 'trabajador', 1, 'pendiente');
INSERT INTO TRABAJADOR (id_usuario, oficio, estado_habilitacion)
VALUES (LAST_INSERT_ID(), 'Pintora', 'pendiente');
INSERT INTO TRABAJADOR_LOCALIDAD (id_trabajador, id_localidad)
VALUES (LAST_INSERT_ID(), 1);

-- ---------- Contenido pendiente de moderar del trabajador de prueba (Carlos) ----------
SET @id_carlos = (SELECT t.id_trabajador FROM TRABAJADOR t
                    JOIN USUARIO u ON u.id_usuario = t.id_usuario
                   WHERE u.mail = 'trabajador@fixnow.com');
INSERT INTO SERVICIO (id_trabajador, nombre, descripcion, estado_revision)
VALUES (@id_carlos, 'Reparación de pérdidas', 'Detección y reparación de pérdidas de agua fría y caliente.', 'pendiente');
INSERT INTO IMAGEN (id_trabajador, url, estado_revision)
VALUES (@id_carlos, 'https://placehold.co/400x300?text=Pendiente', 'pendiente');

-- ---------- Un cliente más, y una trabajadora con una reseña reportada ----------
INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Bruno', 'Peralta', 'bruno@fixnow.com', @hash_cli, 'cliente', 1, 'activo');
SET @id_bruno = LAST_INSERT_ID();

INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
VALUES ('Rocío', 'Fernández', 'rocio@fixnow.com', @hash_trab, 'trabajador', 1, 'activo');
INSERT INTO TRABAJADOR (id_usuario, oficio, descripcion, anios_experiencia, estado_habilitacion)
VALUES (LAST_INSERT_ID(), 'Electricista', 'Electricista matriculada, instalaciones domiciliarias y tableros.', 6, 'habilitado');
SET @id_rocio = LAST_INSERT_ID();
INSERT INTO TRABAJADOR_LOCALIDAD (id_trabajador, id_localidad) VALUES (@id_rocio, 1);
INSERT INTO SERVICIO (id_trabajador, nombre, descripcion, estado_revision)
VALUES (@id_rocio, 'Instalación de tablero', 'Armado y actualización de tableros eléctricos.', 'aprobado');

INSERT INTO SOLICITUD (id_usuario, id_trabajador, mensaje_inicial, estado_contacto,
                       estado_trabajo, fecha_confirmacion_trabajo, confirmado_cliente, confirmado_trabajador)
VALUES (@id_bruno, @id_rocio, 'Necesito revisar el tablero.', 'aceptada', 'aceptado', NOW(), TRUE, TRUE);
INSERT INTO CALIFICACION (id_solicitud, id_usuario, id_trabajador, puntuacion, comentario, reportada, motivo_reporte)
VALUES (LAST_INSERT_ID(), @id_bruno, @id_rocio, 1,
        'Comentario de ejemplo con lenguaje ofensivo hacia la trabajadora.', TRUE, 'Lenguaje ofensivo');
