<?php
// src/controllers/CuentaController.php
// ---------------------------------------------------------------------------
// Fase 9 (punto 4). Acciones que una persona hace sobre SU PROPIA cuenta:
//   - editarPerfil():   el CLIENTE cambia nombre, apellido, localidad y/o contraseña
//   - eliminarPropia(): CLIENTE o TRABAJADOR borra su cuenta (confirmación estricta)
// El administrador no puede borrarse (se crea y se mantiene por SQL; eliminarlo
// dejaría el sistema sin nadie que lo gestione).
// Todas las respuestas son ['exito' => bool, 'mensaje' => string].
// ---------------------------------------------------------------------------

class CuentaController
{
    private static function ok(string $m): array    { return ['exito' => true,  'mensaje' => $m]; }
    private static function error(string $m): array { return ['exito' => false, 'mensaje' => $m]; }

    /**
     * Editar perfil del CLIENTE: nombre, apellido, localidad y (opcional) contraseña.
     * Para cambiar la contraseña hay que escribir la ACTUAL (si alguien deja la
     * sesión abierta, no puede quedarse con la cuenta).
     */
    public static function editarPerfil(int $idUsuario, array $post): array
    {
        $pdo = Database::getConexion();
        $modelo = new Usuario($pdo);

        $usuario = $modelo->buscarPorId($idUsuario);
        if ($usuario === null || $usuario['rol'] !== 'cliente') {
            return self::error('Esta pantalla de edición es solo para clientes.');
        }

        $nombre = trim(Seguridad::texto($post['nombre'] ?? ''));
        $apellido = trim(Seguridad::texto($post['apellido'] ?? ''));
        $idLocalidad = Seguridad::texto($post['id_localidad'] ?? '');
        $actual = Seguridad::texto($post['contrasena_actual'] ?? '');
        $nueva = Seguridad::texto($post['contrasena_nueva'] ?? '');
        $repetida = Seguridad::texto($post['contrasena_repetida'] ?? '');

        if ($nombre === '' || $apellido === '') {
            return self::error('El nombre y el apellido son obligatorios.');
        }
        if (mb_strlen($nombre) > 60 || mb_strlen($apellido) > 60) {
            return self::error('El nombre y el apellido pueden tener hasta 60 caracteres.');
        }
        if (!ctype_digit($idLocalidad) || !(new Localidad($pdo))->existe((int) $idLocalidad)) {
            return self::error('Elegí una localidad de la lista.');
        }

        // ¿Quiere cambiar la contraseña? (si completó cualquiera de los tres campos)
        $cambiaContrasena = ($actual !== '' || $nueva !== '' || $repetida !== '');
        if ($cambiaContrasena) {
            $hash = $modelo->obtenerHashContrasena($idUsuario);
            if ($hash === null || !password_verify($actual, $hash)) {
                return self::error('La contraseña actual no es correcta.');
            }
            if (mb_strlen($nueva) < Config::MIN_LARGO_CONTRASENA) {
                return self::error('La contraseña nueva debe tener al menos ' . Config::MIN_LARGO_CONTRASENA . ' caracteres.');
            }
            if (strlen($nueva) > Config::MAX_BYTES_CONTRASENA) {
                return self::error('La contraseña nueva es demasiado larga (máximo ' . Config::MAX_BYTES_CONTRASENA . ' caracteres).');
            }
            if ($nueva !== $repetida) {
                return self::error('La contraseña nueva y su repetición no coinciden.');
            }
        }

        $pdo->beginTransaction();
        try {
            $modelo->actualizarDatos($idUsuario, $nombre, $apellido, (int) $idLocalidad);
            if ($cambiaContrasena) {
                $modelo->cambiarContrasena($idUsuario, password_hash($nueva, PASSWORD_BCRYPT));
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error al editar perfil: ' . $e->getMessage());
            return self::error('No se pudo guardar el perfil. Intentá de nuevo.');
        }

        // Si cambió la contraseña, se renueva el id de sesión (buena práctica de seguridad).
        if ($cambiaContrasena && session_status() === PHP_SESSION_ACTIVE && PHP_SAPI !== 'cli') {
            session_regenerate_id(true);
        }
        return self::ok($cambiaContrasena ? 'Perfil y contraseña actualizados.' : 'Perfil actualizado correctamente.');
    }

    /**
     * Eliminar la PROPIA cuenta. Confirmación estricta, las DOS tienen que cumplirse:
     *   1) escribir exactamente la palabra ELIMINAR
     *   2) escribir la contraseña actual
     * Se borra en cascada todo lo del usuario (solicitudes, mensajes, calificaciones,
     * fotos), igual que cuando lo elimina el administrador (CU-13).
     */
    public static function eliminarPropia(int $idUsuario, string $palabra, string $contrasena): array
    {
        $pdo = Database::getConexion();
        $modelo = new Usuario($pdo);

        $usuario = $modelo->buscarPorId($idUsuario);
        if ($usuario === null) {
            return self::error('No encontramos tu cuenta.');
        }
        // El rol se lee de la BASE (no de la sesión) y el admin queda excluido.
        if (!in_array($usuario['rol'], ['cliente', 'trabajador'], true)) {
            return self::error('Esta cuenta no se puede eliminar desde acá.');
        }
        if ($palabra !== Config::PALABRA_ELIMINAR_CUENTA) {
            return self::error('Para confirmar tenés que escribir ' . Config::PALABRA_ELIMINAR_CUENTA . ' exactamente.');
        }
        $hash = $modelo->obtenerHashContrasena($idUsuario);
        if ($hash === null || !password_verify($contrasena, $hash)) {
            return self::error('La contraseña no es correcta.');
        }

        // Se anotan los archivos antes de borrar, para limpiarlos del disco después.
        $rutas = (new Trabajador($pdo))->rutasDeArchivosDeUsuario($idUsuario);
        if (!$modelo->eliminar($idUsuario)) {
            return self::error('No se pudo eliminar la cuenta.');
        }
        foreach ($rutas as $ruta) {
            Archivos::borrar($ruta);
        }
        return self::ok('Tu cuenta fue eliminada.');
    }
}
