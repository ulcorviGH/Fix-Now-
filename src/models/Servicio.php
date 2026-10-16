<?php
// src/models/Servicio.php
// ---------------------------------------------------------------------------
// Modelo de la tabla SERVICIO.
//   Fase 4: lectura de los servicios aprobados (CU-04).
//   Fase 7: crear / eliminar del trabajador (CU-10) y moderación del
//           Administrador (CU-15, CU-19).
// ---------------------------------------------------------------------------

class Servicio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Servicios APROBADOS por el Administrador (CU-19) de un trabajador. */
    public function listarAprobadosPorTrabajador(int $idTrabajador): array
    {
        $sql = "SELECT nombre, descripcion
                  FROM SERVICIO
                 WHERE id_trabajador = ? AND estado_revision = 'aprobado'
                 ORDER BY nombre ASC";
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    // =====================================================================
    // FASE 7 - Trabajador (CU-10)
    // =====================================================================

    /** TODOS los servicios del trabajador, con su estado (para "Mi perfil"). */
    public function listarDeTrabajador(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id_servicio, nombre, descripcion, estado_revision, motivo_rechazo
               FROM SERVICIO WHERE id_trabajador = ? ORDER BY id_servicio DESC'
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    public function contarDeTrabajador(int $idTrabajador): int
    {
        $consulta = $this->pdo->prepare('SELECT COUNT(*) FROM SERVICIO WHERE id_trabajador = ?');
        $consulta->execute([$idTrabajador]);
        return (int) $consulta->fetchColumn();
    }

    /** CU-10 paso 6: guarda el servicio como 'pendiente de revisión'. */
    public function crear(int $idTrabajador, string $nombre, string $descripcion): int
    {
        $consulta = $this->pdo->prepare(
            "INSERT INTO SERVICIO (id_trabajador, nombre, descripcion, estado_revision)
             VALUES (?, ?, ?, 'pendiente')"
        );
        $consulta->execute([$idTrabajador, $nombre, $descripcion]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Borra un servicio SOLO si es de ese trabajador (la condición va en el SQL). */
    public function eliminarDeTrabajador(int $idServicio, int $idTrabajador): bool
    {
        $consulta = $this->pdo->prepare('DELETE FROM SERVICIO WHERE id_servicio = ? AND id_trabajador = ?');
        $consulta->execute([$idServicio, $idTrabajador]);
        return $consulta->rowCount() > 0;
    }

    // =====================================================================
    // FASE 7 - Administrador (CU-15, CU-19)
    // =====================================================================

    /** Servicios 'pendientes' de revisión, del más viejo al más nuevo. */
    public function listarPendientes(): array
    {
        $consulta = $this->pdo->query(
            "SELECT s.id_servicio, s.nombre, s.descripcion, u.nombre AS trabajador_nombre,
                    u.apellido AS trabajador_apellido, t.oficio
               FROM SERVICIO s
               JOIN TRABAJADOR t ON t.id_trabajador = s.id_trabajador
               JOIN USUARIO u ON u.id_usuario = t.id_usuario
              WHERE s.estado_revision = 'pendiente'
              ORDER BY s.id_servicio ASC"
        );
        return $consulta->fetchAll();
    }

    /** Aprueba o rechaza, SOLO si todavía estaba pendiente. $nuevoEstado: 'aprobado' | 'rechazado'. */
    public function resolver(int $idServicio, string $nuevoEstado, ?string $motivo): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE SERVICIO SET estado_revision = ?, motivo_rechazo = ?
              WHERE id_servicio = ? AND estado_revision = 'pendiente'"
        );
        $consulta->execute([$nuevoEstado, $motivo, $idServicio]);
        return $consulta->rowCount() > 0;
    }
}
