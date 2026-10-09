<?php
// src/models/Servicio.php
// ---------------------------------------------------------------------------
// Modelo de la tabla SERVICIO. Por ahora solo lectura (CU-04). En la fase del
// trabajador se le agregan crear/eliminar (CU-10).
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
}
