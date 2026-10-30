<?php
// src/models/Oficio.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 7). Catálogo de OFICIOS (tabla OFICIO). Un trabajador puede
// tener VARIOS (relación N:N en TRABAJADOR_OFICIO, ver models/Trabajador.php).
// Este modelo solo hace SQL; las reglas de validación están en
// controllers/CatalogoController.php.
// ---------------------------------------------------------------------------

class Oficio
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Todo el catálogo, ordenado alfabéticamente: [id_oficio, nombre]. */
    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT id_oficio, nombre FROM OFICIO ORDER BY nombre ASC')->fetchAll();
    }

    /**
     * De una lista de ids, devuelve solo los que EXISTEN en el catálogo
     * (con su nombre). Sirve para descartar ids inventados a mano.
     */
    public function porIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $marcadores = implode(',', array_fill(0, count($ids), '?'));
        $consulta = $this->pdo->prepare(
            "SELECT id_oficio, nombre FROM OFICIO WHERE id_oficio IN ({$marcadores}) ORDER BY nombre ASC"
        );
        $consulta->execute(array_map('intval', array_values($ids)));
        return $consulta->fetchAll();
    }
}
