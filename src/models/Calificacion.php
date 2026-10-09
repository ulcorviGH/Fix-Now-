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
}
