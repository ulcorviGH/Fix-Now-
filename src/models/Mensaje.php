<?php
// src/models/Mensaje.php
// ---------------------------------------------------------------------------
// Modelo de la tabla MENSAJE (chat interno, CU-08). Los mensajes pertenecen
// a una SOLICITUD cuyo contacto ya fue aceptado.
// ---------------------------------------------------------------------------

class Mensaje
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** Guarda un mensaje (con fecha y hora automáticas) y devuelve su id. */
    public function crear(int $idSolicitud, int $idEmisor, string $contenido): int
    {
        $consulta = $this->pdo->prepare(
            'INSERT INTO MENSAJE (id_solicitud, id_emisor, contenido) VALUES (?, ?, ?)'
        );
        $consulta->execute([$idSolicitud, $idEmisor, $contenido]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Mensajes de una conversación con id MAYOR a $desdeId, del más viejo al
     * más nuevo. Con $desdeId = 0 devuelve todo el historial. El chat en
     * pantalla usa esto para pedir solo lo nuevo cada pocos segundos.
     */
    public function listarDesde(int $idSolicitud, int $desdeId = 0): array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id_mensaje, id_emisor, contenido, fecha_envio
               FROM MENSAJE
              WHERE id_solicitud = ? AND id_mensaje > ?
              ORDER BY id_mensaje ASC'
        );
        $consulta->execute([$idSolicitud, $desdeId]);
        return $consulta->fetchAll();
    }

    /** Un mensaje puntual (para devolverlo al navegador recién guardado). */
    public function obtener(int $idMensaje): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id_mensaje, id_emisor, contenido, fecha_envio FROM MENSAJE WHERE id_mensaje = ?'
        );
        $consulta->execute([$idMensaje]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    /**
     * Fase 8 (antispam): cuántos mensajes envió una persona en los últimos
     * 60 segundos, en cualquiera de sus conversaciones.
     */
    public function contarUltimoMinuto(int $idEmisor): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT COUNT(*) FROM MENSAJE WHERE id_emisor = ? AND fecha_envio >= DATE_SUB(NOW(), INTERVAL 60 SECOND)'
        );
        $consulta->execute([$idEmisor]);
        return (int) $consulta->fetchColumn();
    }
}
