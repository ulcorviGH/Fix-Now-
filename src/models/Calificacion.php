<?php
// src/models/Calificacion.php
// ---------------------------------------------------------------------------
// Modelo de la tabla CALIFICACION (con su RESPUESTA opcional).
// Por ahora solo lectura para el perfil público (CU-04). Crear calificaciones
// (CU-07) y responderlas (CU-20) viene en la Fase 6.
// ---------------------------------------------------------------------------

class Calificacion
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Reseñas de un trabajador junto con la respuesta pública del trabajador
     * (si la hay). LEFT JOIN: la reseña aparece aunque no tenga respuesta.
     */
    public function listarPorTrabajador(int $idTrabajador): array
    {
        $sql = 'SELECT c.puntuacion,
                       c.comentario,
                       c.fecha,
                       u.nombre AS nombre_cliente,
                       r.contenido AS respuesta
                  FROM CALIFICACION c
                  JOIN USUARIO u ON u.id_usuario = c.id_usuario
                  LEFT JOIN RESPUESTA r ON r.id_calificacion = c.id_calificacion
                 WHERE c.id_trabajador = ?
                 ORDER BY c.fecha DESC';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    // =====================================================================
    // FASE 6
    // =====================================================================

    /** CU-07 paso 6: guarda la calificación. La columna id_solicitud es UNIQUE: una por trabajo. */
    public function crear(int $idSolicitud, int $idUsuarioCliente, int $idTrabajador, int $puntuacion, ?string $comentario): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO CALIFICACION (id_solicitud, id_usuario, id_trabajador, puntuacion, comentario)
             VALUES (?, ?, ?, ?, ?)'
        );
        $consulta->execute([$idSolicitud, $idUsuarioCliente, $idTrabajador, $puntuacion, $comentario]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Puntaje promedio y cantidad de calificaciones de un trabajador (CU-12). */
    public function resumen(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT AVG(puntuacion) AS promedio, COUNT(*) AS cantidad
               FROM CALIFICACION WHERE id_trabajador = ?'
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetch();
    }

    /** Reseñas recibidas con su id y la respuesta del trabajador, para "Mis calificaciones" (CU-12). */
    public function listarRecibidas(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT c.id_calificacion, c.puntuacion, c.comentario, c.fecha,
                    u.nombre AS nombre_cliente, u.apellido AS apellido_cliente,
                    r.contenido AS respuesta
               FROM CALIFICACION c
               JOIN USUARIO u ON u.id_usuario = c.id_usuario
               LEFT JOIN RESPUESTA r ON r.id_calificacion = c.id_calificacion
              WHERE c.id_trabajador = ?
              ORDER BY c.fecha DESC, c.id_calificacion DESC'
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    /**
     * Devuelve la calificación SOLO si pertenece al trabajador que tiene ese
     * usuario. Es la barrera de seguridad de CU-20: nadie responde reseñas ajenas.
     */
    public function obtenerDeTrabajadorUsuario(int $idCalificacion, int $idUsuarioTrabajador): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT c.id_calificacion
               FROM CALIFICACION c
               JOIN TRABAJADOR t ON t.id_trabajador = c.id_trabajador
              WHERE c.id_calificacion = ? AND t.id_usuario = ?'
        );
        $consulta->execute([$idCalificacion, $idUsuarioTrabajador]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    /**
     * CU-20: guarda la respuesta del trabajador. Si ya había una, la edita
     * (RESPUESTA.id_calificacion es UNIQUE: una respuesta por reseña).
     * Se hace en dos pasos (buscar y luego INSERT o UPDATE) para que funcione
     * igual en cualquier versión de MySQL/MariaDB.
     */
    public function guardarRespuesta(int $idCalificacion, string $contenido): void
    {
        $busqueda = $this->pdo->prepare('SELECT 1 FROM RESPUESTA WHERE id_calificacion = ?');
        $busqueda->execute([$idCalificacion]);

        if ($busqueda->fetchColumn() !== false) {
            $consulta = $this->pdo->prepare('UPDATE RESPUESTA SET contenido = ?, fecha = NOW() WHERE id_calificacion = ?');
            $consulta->execute([$contenido, $idCalificacion]);
        } else {
            $consulta = $this->pdo->prepare('INSERT INTO RESPUESTA (id_calificacion, contenido) VALUES (?, ?)');
            $consulta->execute([$idCalificacion, $contenido]);
        }
    }
}
