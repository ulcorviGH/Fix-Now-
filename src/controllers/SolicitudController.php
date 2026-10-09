<?php
// src/controllers/SolicitudController.php
// ---------------------------------------------------------------------------
// Reglas de negocio de los casos de uso:
//   CU-06  Solicitar contacto con un trabajador
//   CU-11  Gestionar solicitudes recibidas (aceptar / rechazar)
//   CU-21  Confirmar contratación de trabajo
//   CU-22  Marcar trabajo como terminado (manual)
//
// TODOS los métodos devuelven ['exito' => bool, 'mensaje' => string] para que
// la pantalla muestre el resultado como aviso. Los permisos se verifican acá
// (en el servidor) y no solo ocultando botones: un botón oculto no frena a
// quien arme el pedido a mano.
// ---------------------------------------------------------------------------

class SolicitudController
{
    private static function ok(string $mensaje, array $extra = []): array
    {
        return array_merge(['exito' => true, 'mensaje' => $mensaje], $extra);
    }

    private static function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }

    // =====================================================================
    // CU-06: el cliente pide contacto
    // =====================================================================

    public static function solicitarContacto(int $idUsuarioCliente, int $idTrabajador, string $mensaje): array
    {
        $pdo = Database::getConexion();
        $mensaje = trim($mensaje);

        // Excepción 3.1: mensaje en blanco.
        if ($mensaje === '') {
            return self::error('Tenés que describir lo que necesitás.');
        }
        if (mb_strlen($mensaje) > 1000) {
            return self::error('El mensaje es demasiado largo (máximo 1000 caracteres).');
        }

        $modeloTrabajador = new Trabajador($pdo);

        // Precondición: el trabajador existe, está habilitado y su cuenta está activa.
        if ($modeloTrabajador->obtenerPerfilCompleto($idTrabajador) === null) {
            return self::error('Este perfil no está disponible.');
        }

        // Precondición: el trabajador cubre alguna localidad del cliente.
        $localidad = (new Localidad($pdo))->obtenerDeUsuario($idUsuarioCliente);
        if ($localidad === null || !$modeloTrabajador->cubreLocalidad($idTrabajador, (int) $localidad['id_localidad'])) {
            return self::error('Este trabajador no ofrece sus servicios en tu localidad.');
        }

        // No duplicar: si ya hay una solicitud o un chat en curso, no se crea otra.
        $modeloSolicitud = new Solicitud($pdo);
        if ($modeloSolicitud->vigenteEntre($idUsuarioCliente, $idTrabajador) !== null) {
            return self::error('Ya tenés una solicitud o una conversación en curso con este trabajador.');
        }

        $id = $modeloSolicitud->crear($idUsuarioCliente, $idTrabajador, $mensaje);

        // Paso 5: el "aviso" al trabajador es que la solicitud aparece en su
        // pestaña "Solicitudes de chat" (con globito de cantidad).
        return self::ok('Solicitud enviada. Te avisamos cuando el trabajador responda.', ['id_solicitud' => $id]);
    }

    // =====================================================================
    // Despachador: recibe las acciones de los botones (public/accion.php)
    // =====================================================================

    public static function ejecutar(int $idUsuario, string $rol, string $accion, int $idSolicitud): array
    {
        $solicitud = (new Solicitud(Database::getConexion()))->obtenerSiParticipa($idSolicitud, $idUsuario);
        if ($solicitud === null) {
            return self::error('La solicitud no existe o no tenés acceso a ella.');
        }

        // Cada acción exige un rol: "Contratar" solo el cliente; aceptar,
        // rechazar y marcar terminado solo el trabajador de ESA solicitud.
        $soloCliente = ['contratar'];
        $soloTrabajador = ['aceptar_chat', 'rechazar_chat', 'aceptar_trabajo', 'rechazar_trabajo', 'terminar_manual'];

        if (in_array($accion, $soloCliente, true) && $solicitud['mi_rol'] !== 'cliente') {
            return self::error('Esta acción solo la puede hacer el cliente.');
        }
        if (in_array($accion, $soloTrabajador, true) && $solicitud['mi_rol'] !== 'trabajador') {
            return self::error('Esta acción solo la puede hacer el trabajador.');
        }

        switch ($accion) {
            case 'aceptar_chat':     return self::responderContacto($solicitud, true);
            case 'rechazar_chat':    return self::responderContacto($solicitud, false);
            case 'contratar':        return self::contratar($solicitud);
            case 'aceptar_trabajo':  return self::responderTrabajo($solicitud, true);
            case 'rechazar_trabajo': return self::responderTrabajo($solicitud, false);
            case 'terminar_manual':  return self::confirmarFinalizacion($solicitud);
            case 'pregunta_si':      return self::confirmarFinalizacion($solicitud);
            case 'pregunta_no':      return self::posponer($solicitud);
        }
        return self::error('Acción desconocida.');
    }

    // =====================================================================
    // Acciones individuales (privadas: solo se llegan desde ejecutar())
    // =====================================================================

    /** CU-06 paso 6 / CU-11: Aceptar o Rechazar el pedido de chat. */
    private static function responderContacto(array $s, bool $aceptar): array
    {
        $modelo = new Solicitud(Database::getConexion());
        if (!$modelo->resolverContacto((int) $s['id_solicitud'], $aceptar)) {
            return self::error('Esta solicitud ya fue respondida.'); // CU-11 excepción 4.1
        }
        return $aceptar
            ? self::ok('Aceptaste la solicitud. Ya podés chatear con el cliente.')
            : self::ok('Rechazaste la solicitud de contacto.');
    }

    /** CU-21 pasos 1-3: el cliente presiona "Contratar trabajador". */
    private static function contratar(array $s): array
    {
        if ($s['estado_contacto'] !== 'aceptada') {
            return self::error('El chat todavía no está habilitado.');
        }
        $modelo = new Solicitud(Database::getConexion());
        if (!$modelo->pedirTrabajo((int) $s['id_solicitud'])) {
            // Excepción 1.1: ya hay una solicitud de trabajo pendiente o aceptada.
            return self::error('Ya hay una solicitud de trabajo en curso para esta conversación.');
        }
        return self::ok('Pedido enviado. El trabajador tiene que aceptar el trabajo.');
    }

    /** CU-21 paso 4 / CU-11: el trabajador acepta o rechaza el trabajo. */
    private static function responderTrabajo(array $s, bool $aceptar): array
    {
        $modelo = new Solicitud(Database::getConexion());
        if (!$modelo->resolverTrabajo((int) $s['id_solicitud'], $aceptar)) {
            return self::error('Esta solicitud de trabajo ya fue respondida.');
        }
        return $aceptar
            ? self::ok('Trabajo aceptado. Más adelante vamos a preguntarles si ya se completó.')
            : self::ok('Rechazaste el trabajo. El chat sigue abierto.');
    }

    /**
     * "Sí, está terminado" (cliente o trabajador) y también el botón manual
     * del trabajador (CU-22): las dos cosas hacen lo mismo en la base.
     */
    private static function confirmarFinalizacion(array $s): array
    {
        $modelo = new Solicitud(Database::getConexion());
        if ($s['estado_trabajo'] !== 'aceptado') {
            return self::error('Este trabajo todavía no fue aceptado.');
        }
        if (!$modelo->confirmarFinalizacion((int) $s['id_solicitud'], $s['mi_rol'])) {
            return self::error('Ya habías confirmado que este trabajo está terminado.'); // CU-22 excepción 6.1
        }

        $confirmoOtro = ($s['mi_rol'] === 'cliente') ? (bool) $s['confirmado_trabajador'] : (bool) $s['confirmado_cliente'];
        return $confirmoOtro
            ? self::ok('¡Trabajo terminado! Ambas partes confirmaron.')
            : self::ok('Registramos tu confirmación. Falta que la otra parte también confirme.');
    }

    /** "Todavía no": se vuelve a preguntar en unos días (CU-21 paso 7). */
    private static function posponer(array $s): array
    {
        $modelo = new Solicitud(Database::getConexion());
        $modelo->posponerPregunta((int) $s['id_solicitud'], $s['mi_rol']);
        return self::ok('Listo, te lo vamos a volver a preguntar en unos días.');
    }
}
