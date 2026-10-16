<?php
// src/controllers/AdminController.php
// ---------------------------------------------------------------------------
// Reglas de negocio de TODAS las acciones del Administrador:
//   CU-18  Habilitar / rechazar cuenta de trabajador
//   CU-13  Gestionar usuarios (bloquear, activar, eliminar)
//   CU-15 / CU-19  Moderar servicios e imágenes (aprobar / rechazar)
//   CU-14  Moderar calificaciones reportadas (eliminar / mantener)
//
// Cada acción llega desde public/accion_admin.php (un botón = un POST).
// El PERMISO se verifica acá, en el servidor: si quien llama no es un
// administrador activo, no se hace nada, aunque haya armado el pedido a mano.
// Todas las acciones devuelven ['exito' => bool, 'mensaje' => string].
// ---------------------------------------------------------------------------

class AdminController
{
    private static function ok(string $mensaje): array
    {
        return ['exito' => true, 'mensaje' => $mensaje];
    }

    private static function error(string $mensaje): array
    {
        return ['exito' => false, 'mensaje' => $mensaje];
    }

    /**
     * Punto de entrada único. $id es el id del objeto sobre el que se actúa
     * (trabajador, usuario, servicio, imagen o calificación, según la acción)
     * y $motivo solo se usa en los rechazos.
     */
    public static function ejecutar(int $idAdmin, string $rol, string $accion, int $id, string $motivo = ''): array
    {
        // Barrera 1: el rol de la sesión.
        if ($rol !== 'admin') {
            return self::error('No tenés permiso para hacer esto.');
        }

        // Barrera 2: se vuelve a comprobar en la base (por si lo bloquearon recién).
        $pdo = Database::getConexion();
        $yo = (new Usuario($pdo))->buscarPorId($idAdmin);
        if ($yo === null || $yo['rol'] !== 'admin' || $yo['estado'] !== 'activo') {
            return self::error('No tenés permiso para hacer esto.');
        }

        if ($id <= 0) {
            return self::error('Falta indicar sobre qué elemento actuar.');
        }

        $motivo = trim($motivo);

        // Lista cerrada de acciones: cualquier otra cosa se rechaza.
        switch ($accion) {
            case 'habilitar_trabajador':   return self::habilitarTrabajador($id);
            case 'rechazar_trabajador':    return self::rechazarTrabajador($id, $motivo);
            case 'bloquear_usuario':       return self::bloquearUsuario($idAdmin, $id);
            case 'activar_usuario':        return self::activarUsuario($idAdmin, $id);
            case 'eliminar_usuario':       return self::eliminarUsuario($idAdmin, $id);
            case 'aprobar_servicio':       return self::resolverServicio($id, 'aprobado', null);
            case 'rechazar_servicio':      return self::resolverServicio($id, 'rechazado', $motivo);
            case 'aprobar_imagen':         return self::resolverImagen($id, 'aprobada', null);
            case 'rechazar_imagen':        return self::resolverImagen($id, 'rechazada', $motivo);
            case 'eliminar_calificacion':  return self::eliminarCalificacion($id);
            case 'mantener_calificacion':  return self::mantenerCalificacion($id);
            default:                       return self::error('Acción desconocida.');
        }
    }

    /** Valida un motivo de rechazo (obligatorio, hasta 255 caracteres). Devuelve el error o null. */
    private static function errorDeMotivo(string $motivo): ?string
    {
        if ($motivo === '') {
            return 'Tenés que escribir el motivo.';
        }
        if (mb_strlen($motivo) > 255) {
            return 'El motivo es demasiado largo (máximo 255 caracteres).';
        }
        return null;
    }

    // =====================================================================
    // CU-18: habilitar cuenta de trabajador
    // =====================================================================

    private static function habilitarTrabajador(int $idTrabajador): array
    {
        $pdo = Database::getConexion();

        // Se tocan dos tablas (TRABAJADOR y USUARIO): transacción, o todo o nada.
        $pdo->beginTransaction();
        try {
            $cambio = (new Trabajador($pdo))->habilitar($idTrabajador);
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error al habilitar trabajador: ' . $e->getMessage());
            return self::error('No se pudo aplicar el cambio. Intentá de nuevo.');
        }

        return $cambio
            ? self::ok('Cuenta habilitada. El trabajador ya puede ingresar a FixNow.')
            : self::error('Esa cuenta ya fue resuelta o no existe.');
    }

    private static function rechazarTrabajador(int $idTrabajador, string $motivo): array
    {
        if ($error = self::errorDeMotivo($motivo)) {
            return self::error($error);
        }

        $cambio = (new Trabajador(Database::getConexion()))->rechazar($idTrabajador, $motivo);
        return $cambio
            ? self::ok('Cuenta rechazada. El trabajador verá el motivo al intentar ingresar.')
            : self::error('Esa cuenta ya fue resuelta o no existe.');
    }

    // =====================================================================
    // CU-13: gestionar usuarios
    // =====================================================================

    /**
     * Comprobaciones comunes a bloquear / activar / eliminar. Devuelve
     * [usuario, null] si se puede seguir o [null, mensajeDeError] si no.
     */
    private static function usuarioGestionable(int $idAdmin, int $idObjetivo): array
    {
        if ($idObjetivo === $idAdmin) {
            return [null, 'No podés modificar tu propia cuenta.'];
        }
        $usuario = (new Usuario(Database::getConexion()))->buscarPorId($idObjetivo);
        if ($usuario === null) {
            return [null, 'Ese usuario no existe.'];
        }
        if ($usuario['rol'] === 'admin') {
            return [null, 'No se puede modificar la cuenta de un administrador.'];
        }
        return [$usuario, null];
    }

    private static function bloquearUsuario(int $idAdmin, int $idObjetivo): array
    {
        [$usuario, $error] = self::usuarioGestionable($idAdmin, $idObjetivo);
        if ($error) {
            return self::error($error);
        }
        // Solo se bloquea una cuenta activa (las pendientes se resuelven con CU-18).
        $cambio = (new Usuario(Database::getConexion()))->cambiarEstado($idObjetivo, 'bloqueado', 'activo');
        return $cambio
            ? self::ok('Cuenta de ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' bloqueada.')
            : self::error('Solo se puede bloquear una cuenta activa.');
    }

    private static function activarUsuario(int $idAdmin, int $idObjetivo): array
    {
        [$usuario, $error] = self::usuarioGestionable($idAdmin, $idObjetivo);
        if ($error) {
            return self::error($error);
        }
        $cambio = (new Usuario(Database::getConexion()))->cambiarEstado($idObjetivo, 'activo', 'bloqueado');
        return $cambio
            ? self::ok('Cuenta de ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' activada.')
            : self::error('Solo se puede activar una cuenta bloqueada.');
    }

    private static function eliminarUsuario(int $idAdmin, int $idObjetivo): array
    {
        [$usuario, $error] = self::usuarioGestionable($idAdmin, $idObjetivo);
        if ($error) {
            return self::error($error);
        }

        $pdo = Database::getConexion();
        // Antes de borrar la cuenta anotamos sus archivos, para limpiarlos del disco después.
        $rutas = (new Trabajador($pdo))->rutasDeArchivosDeUsuario($idObjetivo);

        if (!(new Usuario($pdo))->eliminar($idObjetivo)) {
            return self::error('No se pudo eliminar la cuenta.');
        }
        foreach ($rutas as $ruta) {
            Archivos::borrar($ruta);
        }
        return self::ok('Cuenta de ' . $usuario['nombre'] . ' ' . $usuario['apellido'] . ' eliminada.');
    }

    // =====================================================================
    // CU-15 / CU-19: moderar servicios e imágenes
    // =====================================================================

    private static function resolverServicio(int $idServicio, string $estado, ?string $motivo): array
    {
        if ($estado === 'rechazado' && ($error = self::errorDeMotivo((string) $motivo))) {
            return self::error($error);
        }
        $cambio = (new Servicio(Database::getConexion()))->resolver($idServicio, $estado, $estado === 'rechazado' ? $motivo : null);
        if (!$cambio) {
            return self::error('Ese servicio ya fue revisado o no existe.');
        }
        return self::ok($estado === 'aprobado' ? 'Servicio aprobado: ya es visible en el perfil.' : 'Servicio rechazado.');
    }

    private static function resolverImagen(int $idImagen, string $estado, ?string $motivo): array
    {
        if ($estado === 'rechazada' && ($error = self::errorDeMotivo((string) $motivo))) {
            return self::error($error);
        }
        $cambio = (new Imagen(Database::getConexion()))->resolver($idImagen, $estado, $estado === 'rechazada' ? $motivo : null);
        if (!$cambio) {
            return self::error('Esa imagen ya fue revisada o no existe.');
        }
        return self::ok($estado === 'aprobada' ? 'Imagen aprobada: ya es visible en el perfil.' : 'Imagen rechazada.');
    }

    // =====================================================================
    // CU-14: moderar calificaciones reportadas
    // =====================================================================

    private static function eliminarCalificacion(int $idCalificacion): array
    {
        $bajas = (new Calificacion(Database::getConexion()))->eliminarReportada($idCalificacion);
        if ($bajas === null) {
            return self::error('Esa calificación ya fue revisada o no existe.');
        }

        $mensaje = 'Calificación eliminada. El promedio del trabajador se recalculó.';
        if ($bajas >= Config::MAX_BAJAS_CALIFICACION) {
            // Segunda baja: el cliente queda bloqueado para calificar a este trabajador.
            $mensaje .= ' Es la baja número ' . $bajas . ' de ese cliente con este trabajador: ya no podrá calificarlo más.';
        } else {
            $mensaje .= ' El cliente puede volver a calificar ese trabajo una vez más.';
        }
        return self::ok($mensaje);
    }

    private static function mantenerCalificacion(int $idCalificacion): array
    {
        return (new Calificacion(Database::getConexion()))->mantener($idCalificacion)
            ? self::ok('Calificación mantenida. Ya no se puede volver a reportar.')
            : self::error('Esa calificación ya fue revisada o no existe.');
    }
}
