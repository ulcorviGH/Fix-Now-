-- init.sql
-- ---------------------------------------------------------------------------
-- Script de creación de la base de datos FixNow.
-- Docker lo ejecuta AUTOMÁTICAMENTE la primera vez que se crea el contenedor
-- de MySQL (carpeta /docker-entrypoint-initdb.d). No hace falta correrlo a mano.
-- Basado en la Sección 6 del documento de diseño (esquema relacional).
-- ---------------------------------------------------------------------------

-- Usamos utf8mb4 para poder guardar tildes, ñ y emojis sin problemas.
ALTER DATABASE fixnow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE fixnow;

-- =============================================================
-- TABLA: LOCALIDAD
-- Catálogo de localidades/zonas disponibles en la plataforma (CU-03, CU-09)
-- =============================================================
CREATE TABLE LOCALIDAD (
    id_localidad INT AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(80) NOT NULL,
    provincia    VARCHAR(60) NOT NULL,
    latitud      DECIMAL(9,6) NOT NULL,   -- usada para calcular cercanía (CU-03)
    longitud     DECIMAL(9,6) NOT NULL
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: USUARIO
-- Datos base de cualquier persona registrada (cliente, trabajador o admin)
-- =============================================================
CREATE TABLE USUARIO (
    id_usuario     INT AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(60) NOT NULL,
    apellido       VARCHAR(60) NOT NULL,
    mail           VARCHAR(120) NOT NULL UNIQUE,   -- se usa para iniciar sesión (CU-02)
    contrasena     VARCHAR(255) NOT NULL,          -- se guarda con hash, nunca en texto plano
    rol            ENUM('cliente','trabajador','admin') NOT NULL,
    id_localidad   INT,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado         ENUM('activo','pendiente','bloqueado') NOT NULL DEFAULT 'activo',
    -- Fase 7 (CU-02 excepción 3.2): bloqueo TEMPORAL tras varios intentos fallidos.
    -- Es distinto de estado='bloqueado', que lo decide el Administrador (CU-13).
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta   DATETIME NULL,
    -- Fase 7 (CU-16): fecha del último inicio de sesión, para "usuarios activos último mes".
    ultimo_acceso     DATETIME NULL,
    FOREIGN KEY (id_localidad) REFERENCES LOCALIDAD(id_localidad)
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: TRABAJADOR
-- Extiende a un USUARIO con datos profesionales (CU-09)
-- =============================================================
CREATE TABLE TRABAJADOR (
    id_trabajador       INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario          INT NOT NULL UNIQUE,   -- relación 1 a 1 con USUARIO
    oficio              VARCHAR(80),
    descripcion         TEXT,
    anios_experiencia   TINYINT,
    estado_habilitacion ENUM('pendiente','habilitado','rechazado') NOT NULL DEFAULT 'pendiente',
    foto_perfil         VARCHAR(255),
    -- Fase 7 (CU-18): por qué el Administrador rechazó la cuenta (se muestra al intentar entrar)
    motivo_rechazo      VARCHAR(255) NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: ADMINISTRADOR
-- Extiende a un USUARIO con permisos de administración
-- =============================================================
CREATE TABLE ADMINISTRADOR (
    id_admin   INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL UNIQUE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: TRABAJADOR_LOCALIDAD
-- Tabla intermedia N:N -> un trabajador puede cubrir varias zonas (CU-09)
-- =============================================================
CREATE TABLE TRABAJADOR_LOCALIDAD (
    id_trabajador INT NOT NULL,
    id_localidad  INT NOT NULL,
    PRIMARY KEY (id_trabajador, id_localidad),
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE,
    FOREIGN KEY (id_localidad) REFERENCES LOCALIDAD(id_localidad) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: SOLICITUD
-- Registra tanto el contacto/chat (CU-06) como, luego, el trabajo formal (CU-21, CU-22)
-- =============================================================
CREATE TABLE SOLICITUD (
    id_solicitud                INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario                  INT NOT NULL,  -- cliente que envía la solicitud
    id_trabajador                INT NOT NULL,  -- trabajador que la recibe
    mensaje_inicial              TEXT,
    estado_contacto              ENUM('pendiente','aceptada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_creacion                DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado_trabajo                ENUM('no_solicitado','pendiente','aceptado','rechazado') NOT NULL DEFAULT 'no_solicitado',
    fecha_confirmacion_trabajo    DATETIME NULL,  -- dispara el contador de 2 días (CU-21)
    confirmado_cliente            BOOLEAN NOT NULL DEFAULT FALSE,
    confirmado_trabajador         BOOLEAN NOT NULL DEFAULT FALSE,
    -- Fase 7 (CU-14): cuántas calificaciones de este trabajo eliminó el Administrador.
    -- La reseña se borra, pero este contador queda, y permite aplicar la regla:
    -- 1ra baja -> el cliente puede volver a calificar; 2da baja (sumando todos sus
    -- trabajos con ese mismo trabajador) -> ya no puede calificarlo más.
    calificaciones_eliminadas     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    -- Fase 5: cuándo volver a preguntar "¿ya se completó el trabajo?" a cada parte
    -- después de un "Todavía no" (CU-21 pasos 6-7). NULL = preguntar normalmente.
    posponer_cliente_hasta        DATETIME NULL,
    posponer_trabajador_hasta     DATETIME NULL,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: MENSAJE
-- Chat interno, habilitado solo tras aceptar el contacto (CU-08)
-- =============================================================
CREATE TABLE MENSAJE (
    id_mensaje   INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud INT NOT NULL,
    id_emisor    INT NOT NULL,   -- quién lo envió (cliente o trabajador)
    contenido    TEXT NOT NULL,
    fecha_envio  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_solicitud) REFERENCES SOLICITUD(id_solicitud) ON DELETE CASCADE,
    FOREIGN KEY (id_emisor) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: SERVICIO
-- Servicios publicados por un trabajador, sujetos a aprobación (CU-10, CU-19)
-- Nota: NO tiene campo de precio (se eliminó del diseño a propósito)
-- =============================================================
CREATE TABLE SERVICIO (
    id_servicio     INT AUTO_INCREMENT PRIMARY KEY,
    id_trabajador   INT NOT NULL,
    nombre          VARCHAR(100) NOT NULL,
    descripcion     TEXT,
    estado_revision ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    motivo_rechazo  VARCHAR(255) NULL,   -- Fase 7 (CU-19): motivo que ve el trabajador
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: CALIFICACION
-- Reseña del cliente, habilitada solo con doble confirmación (CU-07)
-- =============================================================
CREATE TABLE CALIFICACION (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_solicitud    INT NOT NULL UNIQUE,   -- un trabajo genera como máximo 1 calificación
    id_usuario      INT NOT NULL,
    id_trabajador   INT NOT NULL,
    puntuacion      TINYINT NOT NULL,      -- 1 a 5 estrellas
    comentario      TEXT,
    fecha           DATETIME DEFAULT CURRENT_TIMESTAMP,
    reportada       BOOLEAN NOT NULL DEFAULT FALSE,  -- marca para moderación (CU-14)
    -- Fase 7 (CU-14): motivo que escribió el trabajador al reportar la reseña.
    -- Regla: una reseña solo se puede reportar UNA vez (mientras motivo_reporte sea NULL).
    --   reportada=TRUE  y motivo cargado -> esperando revisión del Administrador
    --   reportada=FALSE y motivo cargado -> el Administrador la revisó y la mantuvo
    motivo_reporte  VARCHAR(255) NULL,
    FOREIGN KEY (id_solicitud) REFERENCES SOLICITUD(id_solicitud) ON DELETE CASCADE,
    FOREIGN KEY (id_usuario) REFERENCES USUARIO(id_usuario) ON DELETE CASCADE,
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE,
    CONSTRAINT chk_puntuacion CHECK (puntuacion BETWEEN 1 AND 5)
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: RESPUESTA
-- Respuesta pública del trabajador a una calificación (CU-20)
-- =============================================================
CREATE TABLE RESPUESTA (
    id_respuesta    INT AUTO_INCREMENT PRIMARY KEY,
    id_calificacion INT NOT NULL UNIQUE,   -- una respuesta por calificación
    contenido       TEXT NOT NULL,
    fecha           DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_calificacion) REFERENCES CALIFICACION(id_calificacion) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- TABLA: IMAGEN
-- Fotos del portfolio del trabajador, sujetas a moderación (CU-17, CU-19)
-- =============================================================
CREATE TABLE IMAGEN (
    id_imagen       INT AUTO_INCREMENT PRIMARY KEY,
    id_trabajador   INT NOT NULL,
    url             VARCHAR(255) NOT NULL,
    estado_revision ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
    fecha_subida    DATETIME DEFAULT CURRENT_TIMESTAMP,
    motivo_rechazo  VARCHAR(255) NULL,   -- Fase 7 (CU-19): motivo que ve el trabajador
    FOREIGN KEY (id_trabajador) REFERENCES TRABAJADOR(id_trabajador) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =============================================================
-- DATOS DE PRUEBA: algunas localidades para poder probar CU-01 y CU-03
-- =============================================================
INSERT INTO LOCALIDAD (nombre, provincia, latitud, longitud) VALUES
    ('San Carlos Centro', 'Santa Fe', -31.6667, -61.1167),
    ('Esperanza',         'Santa Fe', -31.4500, -60.9333),
    ('Santa Fe Capital',  'Santa Fe', -31.6333, -60.7000),
    ('Rafaela',           'Santa Fe', -31.2500, -61.4833);
