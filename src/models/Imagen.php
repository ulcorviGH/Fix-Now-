<?php
// src/models/Imagen.php
// ---------------------------------------------------------------------------
// Modelo de la tabla IMAGEN (portfolio de fotos de trabajos anteriores).
// Fase 4: lectura de las aprobadas (CU-04).
// Fase 7: subir/eliminar del trabajador (CU-17) y moderación del admin (CU-19).
// ---------------------------------------------------------------------------

class Imagen
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Imágenes APROBADAS por el Administrador (CU-19), la más nueva primero. */
    public function listarAprobadasPorTrabajador(int $idTrabajador): array
    {
        $sql = "SELECT url
                  FROM IMAGEN
                 WHERE id_trabajador = ? AND estado_revision = 'aprobada'
                 ORDER BY fecha_subida DESC";
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    // =====================================================================
    // FASE 7 - Trabajador (CU-17)
    // =====================================================================

    /** TODAS las fotos del trabajador con su estado (para "Mi perfil"). */
    public function listarDeTrabajador(int $idTrabajador): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id_imagen, url, estado_revision, motivo_rechazo
               FROM IMAGEN WHERE id_trabajador = ? ORDER BY fecha_subida DESC, id_imagen DESC'
        );
        $consulta->execute([$idTrabajador]);
        return $consulta->fetchAll();
    }

    /** Cuántas fotos "vivas" tiene (las rechazadas no cuentan para el tope). */
    public function contarVigentesDeTrabajador(int $idTrabajador): int
    {
        $consulta = $this->pdo->prepare(
            "SELECT COUNT(*) FROM IMAGEN WHERE id_trabajador = ? AND estado_revision <> 'rechazada'"
        );
        $consulta->execute([$idTrabajador]);
        return (int) $consulta->fetchColumn();
    }

    /** CU-17 paso 9: guarda la foto como 'pendiente de revisión'. */
    public function crear(int $idTrabajador, string $url): int
    {
        $consulta = $this->pdo->prepare(
            "INSERT INTO IMAGEN (id_trabajador, url, estado_revision) VALUES (?, ?, 'pendiente')"
        );
        $consulta->execute([$idTrabajador, $url]);
        return (int) $this->pdo->lastInsertId();
    }

    /** Devuelve la foto SOLO si es de ese trabajador (null si no existe o es ajena). */
    public function obtenerDeTrabajador(int $idImagen, int $idTrabajador): ?array
    {
        $consulta = $this->pdo->prepare('SELECT id_imagen, url FROM IMAGEN WHERE id_imagen = ? AND id_trabajador = ?');
        $consulta->execute([$idImagen, $idTrabajador]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    public function eliminarDeTrabajador(int $idImagen, int $idTrabajador): bool
    {
        $consulta = $this->pdo->prepare('DELETE FROM IMAGEN WHERE id_imagen = ? AND id_trabajador = ?');
        $consulta->execute([$idImagen, $idTrabajador]);
        return $consulta->rowCount() > 0;
    }

    // =====================================================================
    // FASE 7 - Administrador (CU-19)
    // =====================================================================

    public function listarPendientes(): array
    {
        $consulta = $this->pdo->query(
            "SELECT i.id_imagen, i.url, i.fecha_subida, u.nombre AS trabajador_nombre,
                    u.apellido AS trabajador_apellido, t.oficio
               FROM IMAGEN i
               JOIN TRABAJADOR t ON t.id_trabajador = i.id_trabajador
               JOIN USUARIO u ON u.id_usuario = t.id_usuario
              WHERE i.estado_revision = 'pendiente'
              ORDER BY i.fecha_subida ASC, i.id_imagen ASC"
        );
        return $consulta->fetchAll();
    }

    /** Aprueba o rechaza, SOLO si estaba pendiente. $nuevoEstado: 'aprobada' | 'rechazada'. */
    public function resolver(int $idImagen, string $nuevoEstado, ?string $motivo): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE IMAGEN SET estado_revision = ?, motivo_rechazo = ?
              WHERE id_imagen = ? AND estado_revision = 'pendiente'"
        );
        $consulta->execute([$nuevoEstado, $motivo, $idImagen]);
        return $consulta->rowCount() > 0;
    }
}
