<?php
// src/models/Localidad.php
// ---------------------------------------------------------------------------
// Modelo de la tabla LOCALIDAD.
// Fase 3: listarTodas() para los formularios.
// Fase 4: obtenerDeUsuario() para saber desde qué localidad se calcula la
//         cercanía de los trabajadores (CU-03).
// ---------------------------------------------------------------------------

class Localidad
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Devuelve todas las localidades ordenadas alfabéticamente. */
    public function listarTodas(): array
    {
        $sql = 'SELECT id_localidad, nombre, provincia FROM LOCALIDAD ORDER BY nombre ASC';
        return $this->pdo->query($sql)->fetchAll();
    }

    /**
     * Devuelve la localidad principal de un usuario (la que eligió al
     * registrarse), con su latitud y longitud. Null si no tiene cargada.
     */
    public function obtenerDeUsuario(int $idUsuario): ?array
    {
        $sql = 'SELECT l.id_localidad, l.nombre, l.provincia, l.latitud, l.longitud
                FROM USUARIO u
                JOIN LOCALIDAD l ON l.id_localidad = u.id_localidad
                WHERE u.id_usuario = ?
                LIMIT 1';
        $consulta = $this->pdo->prepare($sql);
        $consulta->execute([$idUsuario]);

        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    /**
     * Fase 8: ¿existe una localidad con ese id? Sirve para rechazar datos
     * inventados a mano en el formulario de registro (CU-01).
     */
    public function existe(int $idLocalidad): bool
    {
        $consulta = $this->pdo->prepare('SELECT 1 FROM LOCALIDAD WHERE id_localidad = ? LIMIT 1');
        $consulta->execute([$idLocalidad]);
        return $consulta->fetchColumn() !== false;
    }
}
