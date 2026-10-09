<?php
// src/models/Imagen.php
// ---------------------------------------------------------------------------
// Modelo de la tabla IMAGEN (portfolio de fotos de trabajos anteriores).
// Por ahora solo lectura. La subida de fotos (CU-17) viene en otra fase.
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
}
