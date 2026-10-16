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
                    c.reportada, c.motivo_reporte,
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

    // =====================================================================
    // FASE 7 - Reportes y moderación (CU-14)
    // =====================================================================

    /**
     * El trabajador reporta una reseña. Solo se puede UNA vez: la condición
     * "motivo_reporte IS NULL" lo garantiza en el SQL (si ya fue reportada, o
     * ya revisada y mantenida por el admin, no cambia nada y devuelve false).
     */
    public function reportar(int $idCalificacion, string $motivo): bool
    {
        $consulta = $this->pdo->prepare(
            'UPDATE CALIFICACION SET reportada = TRUE, motivo_reporte = ?
              WHERE id_calificacion = ? AND motivo_reporte IS NULL'
        );
        $consulta->execute([$motivo, $idCalificacion]);
        return $consulta->rowCount() > 0;
    }

    /** Reseñas reportadas esperando revisión (CU-14 paso 2). */
    public function listarReportadas(): array
    {
        $consulta = $this->pdo->query(
            'SELECT c.id_calificacion, c.puntuacion, c.comentario, c.fecha, c.motivo_reporte,
                    uc.nombre AS cliente_nombre, uc.apellido AS cliente_apellido,
                    ut.nombre AS trabajador_nombre, ut.apellido AS trabajador_apellido
               FROM CALIFICACION c
               JOIN USUARIO uc ON uc.id_usuario = c.id_usuario
               JOIN TRABAJADOR t ON t.id_trabajador = c.id_trabajador
               JOIN USUARIO ut ON ut.id_usuario = t.id_usuario
              WHERE c.reportada = TRUE
              ORDER BY c.fecha ASC, c.id_calificacion ASC'
        );
        return $consulta->fetchAll();
    }

    /** CU-14 paso 4b: la reseña es válida, se mantiene (queda marcada como ya revisada). */
    public function mantener(int $idCalificacion): bool
    {
        $consulta = $this->pdo->prepare(
            'UPDATE CALIFICACION SET reportada = FALSE WHERE id_calificacion = ? AND reportada = TRUE'
        );
        $consulta->execute([$idCalificacion]);
        return $consulta->rowCount() > 0;
    }

    /**
     * CU-14 paso 4a: elimina la reseña (y por CASCADE su respuesta). Solo si
     * está reportada. El promedio se "recalcula solo" porque nunca se guarda.
     *
     * Antes de borrarla se suma 1 a SOLICITUD.calificaciones_eliminadas: la reseña
     * desaparece pero queda registrado que el cliente "perdió" una calificación,
     * y con eso se aplica el límite (Config::MAX_BAJAS_CALIFICACION).
     * Todo va en una transacción: o se borra y se anota, o no pasa nada.
     *
     * Devuelve null si no se pudo eliminar (no existía o no estaba reportada);
     * si se eliminó, devuelve cuántas bajas lleva ese cliente con ese trabajador.
     */
    public function eliminarReportada(int $idCalificacion): ?int
    {
        $this->pdo->beginTransaction();
        try {
            // Se leen los datos ANTES de borrar (después ya no existen).
            $datos = $this->pdo->prepare(
                'SELECT id_solicitud, id_usuario, id_trabajador FROM CALIFICACION
                  WHERE id_calificacion = ? AND reportada = TRUE'
            );
            $datos->execute([$idCalificacion]);
            $cal = $datos->fetch();
            if (!$cal) {
                $this->pdo->rollBack();
                return null;
            }

            $borrar = $this->pdo->prepare('DELETE FROM CALIFICACION WHERE id_calificacion = ? AND reportada = TRUE');
            $borrar->execute([$idCalificacion]);
            if ($borrar->rowCount() === 0) {
                $this->pdo->rollBack();
                return null;
            }

            $this->pdo->prepare(
                'UPDATE SOLICITUD SET calificaciones_eliminadas = calificaciones_eliminadas + 1 WHERE id_solicitud = ?'
            )->execute([(int) $cal['id_solicitud']]);

            $this->pdo->commit();
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
        return $this->contarBajas((int) $cal['id_usuario'], (int) $cal['id_trabajador']);
    }

    /**
     * Cuántas calificaciones de este cliente hacia este trabajador fueron
     * eliminadas por el Administrador (sumando todos los trabajos que hicieron juntos).
     */
    public function contarBajas(int $idCliente, int $idTrabajador): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COALESCE(SUM(calificaciones_eliminadas), 0) FROM SOLICITUD
              WHERE id_usuario = ? AND id_trabajador = ?'
        );
        $consulta->execute([$idCliente, $idTrabajador]);
        return (int) $consulta->fetchColumn();
    }
}
