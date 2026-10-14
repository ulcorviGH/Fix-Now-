<?php
// src/controllers/CalificacionController.php
// ---------------------------------------------------------------------------
// Reglas de negocio de:
//   CU-07  Calificar trabajador   (Cliente)
//   CU-12  Ver calificaciones recibidas (Trabajador)
//   CU-20  Responder a comentarios de clientes (Trabajador)
//
// La regla central del sistema: SOLO se puede calificar un trabajo cuando
// AMBAS partes confirmaron que terminó. Se verifica acá, en el servidor, no
// solo ocultando el botón (CU-07 excepción 1.1: acceso por URL directa).
// ---------------------------------------------------------------------------

class CalificacionController
{
    /**
     * Qué pasa si el cliente intenta calificar esta solicitud. Devuelve
     * ['puede' => bool, 'mensaje' => string, 'solicitud' => array|null].
     * Lo usan tanto el formulario (para mostrarlo o no) como el guardado.
     */
    public static function evaluarElegibilidad(int $idUsuarioCliente, int $idSolicitud): array
    {
        $solicitud = (new Solicitud(Database::getConexion()))->obtenerSiParticipa($idSolicitud, $idUsuarioCliente);

        // Solo el CLIENTE de ese trabajo puede calificarlo.
        if ($solicitud === null || $solicitud['mi_rol'] !== 'cliente') {
            return ['puede' => false, 'mensaje' => 'Este trabajo no existe o no tenés acceso a él.', 'solicitud' => null];
        }

        // Excepción 6.1: ya existe una calificación para este trabajo.
        if ($solicitud['id_calificacion'] !== null) {
            return ['puede' => false, 'mensaje' => 'Ya enviaste una calificación para este trabajo.', 'solicitud' => $solicitud];
        }

        // Excepción 1.1: falta la doble confirmación.
        $dobleConfirmacion = $solicitud['estado_trabajo'] === 'aceptado'
            && (bool) $solicitud['confirmado_cliente']
            && (bool) $solicitud['confirmado_trabajador'];

        if (!$dobleConfirmacion) {
            return ['puede' => false,
                    'mensaje' => 'Todavía no se confirmó la finalización del trabajo por ambas partes.',
                    'solicitud' => $solicitud];
        }

        return ['puede' => true, 'mensaje' => '', 'solicitud' => $solicitud];
    }

    /** CU-07 pasos 4-7: valida y publica la calificación. Devuelve ['exito', 'mensaje', 'id_trabajador']. */
    public static function calificar(int $idUsuarioCliente, int $idSolicitud, int $puntuacion, string $comentario): array
    {
        $estado = self::evaluarElegibilidad($idUsuarioCliente, $idSolicitud);
        if (!$estado['puede']) {
            return ['exito' => false, 'mensaje' => $estado['mensaje'], 'id_trabajador' => null];
        }
        $solicitud = $estado['solicitud'];

        // Excepción 4.1: la puntuación es obligatoria (y tiene que estar entre 1 y 5).
        if ($puntuacion < 1 || $puntuacion > 5) {
            return ['exito' => false, 'mensaje' => 'Debe seleccionar una puntuación.', 'id_trabajador' => null];
        }

        $comentario = trim($comentario);
        if (mb_strlen($comentario) > 1000) {
            return ['exito' => false, 'mensaje' => 'El comentario es demasiado largo (máximo 1000 caracteres).', 'id_trabajador' => null];
        }

        try {
            (new Calificacion(Database::getConexion()))->crear(
                $idSolicitud,
                $idUsuarioCliente,
                (int) $solicitud['id_trabajador'],
                $puntuacion,
                $comentario !== '' ? $comentario : null   // el comentario es opcional
            );
        } catch (PDOException $e) {
            // 23000 = violación de UNIQUE: otra pestaña calificó justo antes.
            if ($e->getCode() === '23000') {
                return ['exito' => false, 'mensaje' => 'Ya enviaste una calificación para este trabajo.', 'id_trabajador' => null];
            }
            throw $e;
        }

        // El promedio y "trabajos completados" NO se guardan: se calculan al
        // consultar, así que "se actualizan automáticamente" (CU-07 post-condición).
        return ['exito' => true, 'mensaje' => '¡Gracias! Tu calificación fue publicada.',
                'id_trabajador' => (int) $solicitud['id_trabajador']];
    }

    /** CU-12: todo lo que muestra la pantalla "Mis calificaciones" del trabajador. */
    public static function panelDelTrabajador(int $idUsuarioTrabajador): ?array
    {
        $pdo = Database::getConexion();
        $idTrabajador = (new Trabajador($pdo))->obtenerIdPorUsuario($idUsuarioTrabajador);
        if ($idTrabajador === null) {
            return null;
        }

        $modelo = new Calificacion($pdo);
        $resumen = $modelo->resumen($idTrabajador);

        return [
            'promedio'    => $resumen['promedio'] !== null ? (float) $resumen['promedio'] : null,
            'cantidad'    => (int) $resumen['cantidad'],
            'completados' => (new Solicitud($pdo))->contarCompletados($idTrabajador),
            'resenias'    => $modelo->listarRecibidas($idTrabajador),
        ];
    }

    /** CU-20 pasos 5-6: publica (o edita) la respuesta del trabajador a una reseña. */
    public static function responder(int $idUsuarioTrabajador, int $idCalificacion, string $contenido): array
    {
        $modelo = new Calificacion(Database::getConexion());

        // Solo el trabajador calificado puede responder esa reseña.
        if ($modelo->obtenerDeTrabajadorUsuario($idCalificacion, $idUsuarioTrabajador) === null) {
            return ['exito' => false, 'mensaje' => 'Esa calificación no existe o no es tuya.'];
        }

        // Excepción 5.1: la respuesta no puede estar vacía.
        $contenido = trim($contenido);
        if ($contenido === '') {
            return ['exito' => false, 'mensaje' => 'La respuesta no puede estar vacía.'];
        }
        if (mb_strlen($contenido) > 1000) {
            return ['exito' => false, 'mensaje' => 'La respuesta es demasiado larga (máximo 1000 caracteres).'];
        }

        $modelo->guardarRespuesta($idCalificacion, $contenido);
        return ['exito' => true, 'mensaje' => 'Tu respuesta quedó publicada.'];
    }
}
