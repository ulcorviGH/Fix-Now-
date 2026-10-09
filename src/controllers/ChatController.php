<?php
// src/controllers/ChatController.php
// ---------------------------------------------------------------------------
// Lógica del CU-08 "Enviar y recibir mensajes".
// Regla central: el chat SOLO existe si el trabajador ACEPTÓ el contacto
// (estado_contacto = 'aceptada') y solo pueden entrar las dos personas de
// esa solicitud.
// ---------------------------------------------------------------------------

class ChatController
{
    private const MAX_CARACTERES = 2000;

    /** Devuelve la solicitud si el usuario puede ver este chat; si no, null. */
    public static function abrir(int $idUsuario, int $idSolicitud): ?array
    {
        $solicitud = (new Solicitud(Database::getConexion()))->obtenerSiParticipa($idSolicitud, $idUsuario);

        if ($solicitud === null || $solicitud['estado_contacto'] !== 'aceptada') {
            return null;
        }
        return $solicitud;
    }

    /** Mensajes con id mayor a $desdeId (0 = todo el historial). null si no tiene acceso. */
    public static function mensajesDesde(int $idUsuario, int $idSolicitud, int $desdeId): ?array
    {
        if (self::abrir($idUsuario, $idSolicitud) === null) {
            return null;
        }
        return (new Mensaje(Database::getConexion()))->listarDesde($idSolicitud, $desdeId);
    }

    /**
     * Guarda un mensaje nuevo (CU-08 pasos 3-5).
     * Devuelve ['exito' => bool, 'error' => string, 'mensaje' => array|null]
     */
    public static function enviar(int $idUsuario, int $idSolicitud, string $contenido): array
    {
        if (self::abrir($idUsuario, $idSolicitud) === null) {
            return ['exito' => false, 'error' => 'No tenés acceso a esta conversación.', 'mensaje' => null];
        }

        $contenido = trim($contenido);

        // Excepción 3.1: no se envían mensajes vacíos.
        if ($contenido === '') {
            return ['exito' => false, 'error' => 'No se puede enviar un mensaje vacío.', 'mensaje' => null];
        }
        if (mb_strlen($contenido) > self::MAX_CARACTERES) {
            return ['exito' => false, 'error' => 'El mensaje es demasiado largo.', 'mensaje' => null];
        }

        $modelo = new Mensaje(Database::getConexion());
        $idMensaje = $modelo->crear($idSolicitud, $idUsuario, $contenido);

        return ['exito' => true, 'error' => '', 'mensaje' => $modelo->obtener($idMensaje)];
    }
}
