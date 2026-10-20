<?php
// src/controllers/TrabajadorController.php
// ---------------------------------------------------------------------------
// Reglas de negocio de la pantalla "Mi perfil" del Trabajador:
//   CU-09  Crear y editar perfil (datos, foto y zonas de trabajo)
//   CU-10  Publicar servicios (quedan pendientes hasta que los apruebe el admin)
//   CU-17  Subir imágenes al portfolio (quedan pendientes hasta que las apruebe el admin)
//
// Todos los métodos de escritura reciben el id del USUARIO logueado (nunca un
// id_trabajador que venga del formulario): así un trabajador solo puede tocar
// SUS datos. Devuelven ['exito' => bool, 'mensaje' => string].
//
// "Perfil completo" (precondición de CU-10 y CU-17): oficio + descripción
// personal + al menos una zona de trabajo. Los años de experiencia son opcionales.
// ---------------------------------------------------------------------------

class TrabajadorController
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
     * Trabajador del usuario, SOLO si su cuenta está habilitada y activa
     * (precondición de CU-09, CU-10 y CU-17). Null en cualquier otro caso.
     */
    private static function contexto(int $idUsuario): ?array
    {
        $trabajador = (new Trabajador(Database::getConexion()))->obtenerParaEdicion($idUsuario);
        if ($trabajador === null
            || $trabajador['estado_habilitacion'] !== 'habilitado'
            || $trabajador['estado_usuario'] !== 'activo') {
            return null;
        }
        return $trabajador;
    }

    /** ¿Tiene el perfil lo mínimo para publicar servicios y fotos? */
    private static function perfilCompleto(array $trabajador, array $idsLocalidades): bool
    {
        return trim((string) $trabajador['oficio']) !== ''
            && trim((string) $trabajador['descripcion']) !== ''
            && !empty($idsLocalidades);
    }

    // =====================================================================
    // Datos para dibujar la pantalla
    // =====================================================================

    /** Todo lo que necesita public/mi_perfil.php. Null si no hay perfil de trabajador habilitado. */
    public static function panel(int $idUsuario): ?array
    {
        $pdo = Database::getConexion();
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return null;
        }
        $idTrabajador = (int) $perfil['id_trabajador'];
        $modelo = new Trabajador($pdo);
        $idsLocalidades = $modelo->idsLocalidades($idTrabajador);

        return [
            'perfil'          => $perfil,
            'localidades'     => (new Localidad($pdo))->listarTodas(),
            'ids_localidades' => $idsLocalidades,
            'servicios'       => (new Servicio($pdo))->listarDeTrabajador($idTrabajador),
            'imagenes'        => (new Imagen($pdo))->listarDeTrabajador($idTrabajador),
            'completo'        => self::perfilCompleto($perfil, $idsLocalidades),
        ];
    }

    // =====================================================================
    // CU-09: crear / editar perfil
    // =====================================================================

    /**
     * $post: datos del formulario. $archivoFoto: $_FILES['foto'] (puede venir vacío).
     */
    public static function guardarPerfil(int $idUsuario, array $post, ?array $archivoFoto): array
    {
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return self::error('Tu cuenta no está habilitada para editar el perfil.');
        }
        $pdo = Database::getConexion();

        // Fase 8: Seguridad::texto() descarta listas armadas a mano (evita error 500).
        $oficio = trim(Seguridad::texto($post['oficio'] ?? ''));
        $descripcion = trim(Seguridad::texto($post['descripcion'] ?? ''));
        $aniosTexto = trim(Seguridad::texto($post['anios_experiencia'] ?? ''));

        // Excepción 4.1: campos obligatorios.
        if ($oficio === '' || $descripcion === '') {
            return self::error('Campos obligatorios sin completar: oficio y descripción.');
        }
        if (mb_strlen($oficio) > 80) {
            return self::error('El oficio es demasiado largo (máximo 80 caracteres).');
        }
        if (mb_strlen($descripcion) > 2000) {
            return self::error('La descripción es demasiado larga (máximo 2000 caracteres).');
        }

        // Años de experiencia: opcional, número entero entre 0 y 80.
        $anios = null;
        if ($aniosTexto !== '') {
            $anios = filter_var($aniosTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 80]]);
            if ($anios === false) {
                return self::error('Los años de experiencia deben ser un número entre 0 y 80.');
            }
        }

        // Excepción 3.2: al menos una localidad, y todas deben existir de verdad.
        $elegidas = array_values(array_unique(array_map('intval', (array) ($post['localidades'] ?? []))));
        if (empty($elegidas)) {
            return self::error('Debe seleccionar al menos una localidad.');
        }
        $existentes = array_map('intval', array_column((new Localidad($pdo))->listarTodas(), 'id_localidad'));
        if (array_diff($elegidas, $existentes) !== []) {
            return self::error('Alguna de las localidades elegidas no es válida.');
        }

        // Foto de perfil (opcional). Se procesa DESPUÉS de validar los textos,
        // para no dejar un archivo guardado si el resto del formulario estaba mal.
        $rutaNueva = null;
        if ($archivoFoto !== null && ($archivoFoto['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            // Excepción 3.1: "Formato inválido. Use JPG o PNG".
            $subida = Archivos::guardarImagen(
                $archivoFoto, Archivos::CARPETA_PERFILES, ['image/jpeg', 'image/png'], 'Formato inválido. Use JPG o PNG'
            );
            if (!$subida['ok']) {
                return self::error($subida['error']);
            }
            $rutaNueva = $subida['ruta'];
        }

        // Todo junto en una transacción: datos + zonas + foto, o nada.
        $modelo = new Trabajador($pdo);
        $idTrabajador = (int) $perfil['id_trabajador'];
        $pdo->beginTransaction();
        try {
            $modelo->actualizarPerfil($idTrabajador, $oficio, $descripcion, $anios);
            $modelo->reemplazarLocalidades($idTrabajador, $elegidas);
            if ($rutaNueva !== null) {
                $modelo->actualizarFoto($idTrabajador, $rutaNueva);
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            Archivos::borrar($rutaNueva);   // la foto recién subida ya no sirve
            error_log('Error al guardar perfil: ' . $e->getMessage());
            return self::error('No se pudo guardar el perfil. Intentá de nuevo.');
        }

        // Recién ahora, con todo guardado, se borra la foto anterior del disco.
        if ($rutaNueva !== null) {
            Archivos::borrar($perfil['foto_perfil']);
        }
        return self::ok('Perfil actualizado correctamente.');
    }

    // =====================================================================
    // CU-10: servicios
    // =====================================================================

    public static function agregarServicio(int $idUsuario, string $nombre, string $descripcion): array
    {
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return self::error('Tu cuenta no está habilitada para publicar servicios.');
        }
        $pdo = Database::getConexion();
        $idTrabajador = (int) $perfil['id_trabajador'];

        // Precondición de CU-10: tener el perfil completado.
        $ids = (new Trabajador($pdo))->idsLocalidades($idTrabajador);
        if (!self::perfilCompleto($perfil, $ids)) {
            return self::error('Completá tu perfil (oficio, descripción y zonas) antes de publicar servicios.');
        }

        $nombre = trim($nombre);
        $descripcion = trim($descripcion);

        // Excepción 5.1: campos obligatorios vacíos.
        if ($nombre === '' || $descripcion === '') {
            return self::error('Campos obligatorios sin completar.');
        }
        if (mb_strlen($nombre) > 100) {
            return self::error('El nombre del servicio es demasiado largo (máximo 100 caracteres).');
        }
        if (mb_strlen($descripcion) > 1000) {
            return self::error('La descripción es demasiado larga (máximo 1000 caracteres).');
        }

        $modelo = new Servicio($pdo);
        if ($modelo->contarDeTrabajador($idTrabajador) >= Config::MAX_SERVICIOS) {
            return self::error('Llegaste al máximo de ' . Config::MAX_SERVICIOS . ' servicios. Eliminá alguno para agregar otro.');
        }

        // Paso 6: se guarda 'pendiente de revisión' (el admin lo ve en CU-19).
        $modelo->crear($idTrabajador, $nombre, $descripcion);
        return self::ok('Servicio enviado a revisión. Se publicará cuando lo apruebe el administrador.');
    }

    /** CU-10 excepción 3.1: eliminar un servicio propio (la confirmación se pide en la pantalla). */
    public static function eliminarServicio(int $idUsuario, int $idServicio): array
    {
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return self::error('Tu cuenta no está habilitada.');
        }
        // El SQL exige que el servicio sea de ESTE trabajador.
        $borrado = (new Servicio(Database::getConexion()))->eliminarDeTrabajador($idServicio, (int) $perfil['id_trabajador']);
        return $borrado ? self::ok('Servicio eliminado.') : self::error('Ese servicio no existe o no es tuyo.');
    }

    // =====================================================================
    // CU-17: fotos de portfolio
    // =====================================================================

    public static function subirFoto(int $idUsuario, ?array $archivo): array
    {
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return self::error('Tu cuenta no está habilitada para subir fotos.');
        }
        $pdo = Database::getConexion();
        $idTrabajador = (int) $perfil['id_trabajador'];

        $ids = (new Trabajador($pdo))->idsLocalidades($idTrabajador);
        if (!self::perfilCompleto($perfil, $ids)) {
            return self::error('Completá tu perfil (oficio, descripción y zonas) antes de subir fotos.');
        }

        $modelo = new Imagen($pdo);
        if ($modelo->contarVigentesDeTrabajador($idTrabajador) >= Config::MAX_FOTOS_PORTFOLIO) {
            return self::error('Llegaste al máximo de ' . Config::MAX_FOTOS_PORTFOLIO . ' fotos. Eliminá alguna para subir otra.');
        }

        // Pasos 5-6 y excepciones 5.1 / 5.2: formato JPG, PNG o WEBP; máximo 5 MB.
        $subida = Archivos::guardarImagen(
            $archivo, Archivos::CARPETA_PORTFOLIO,
            ['image/jpeg', 'image/png', 'image/webp'], 'Formato no válido. Use JPG, PNG o WEBP'
        );
        if (!$subida['ok']) {
            return self::error($subida['error']);
        }

        try {
            $modelo->crear($idTrabajador, $subida['ruta']);   // paso 9: 'pendiente de revisión'
        } catch (Exception $e) {
            Archivos::borrar($subida['ruta']);
            error_log('Error al guardar imagen: ' . $e->getMessage());
            return self::error('No se pudo guardar la foto. Intentá de nuevo.');
        }
        return self::ok('Foto subida. Se publicará cuando la apruebe el administrador.');
    }

    /** CU-17 excepción 2.1: eliminar una foto propia. */
    public static function eliminarFoto(int $idUsuario, int $idImagen): array
    {
        $perfil = self::contexto($idUsuario);
        if ($perfil === null) {
            return self::error('Tu cuenta no está habilitada.');
        }
        $modelo = new Imagen(Database::getConexion());
        $idTrabajador = (int) $perfil['id_trabajador'];

        $imagen = $modelo->obtenerDeTrabajador($idImagen, $idTrabajador);   // solo si es suya
        if ($imagen === null || !$modelo->eliminarDeTrabajador($idImagen, $idTrabajador)) {
            return self::error('Esa foto no existe o no es tuya.');
        }
        Archivos::borrar($imagen['url']);
        return self::ok('Foto eliminada.');
    }
}
