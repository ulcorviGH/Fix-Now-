<?php
// src/controllers/AuthController.php
// ---------------------------------------------------------------------------
// Orquesta los casos de uso CU-01 (Registrar usuario) y CU-02 (Iniciar
// sesión). Es el único lugar que conoce el ORDEN de los pasos; los modelos
// (Usuario, Trabajador, Localidad) no saben nada de reglas de negocio,
// solo saben guardar y leer datos.
// ---------------------------------------------------------------------------

class AuthController
{
    /**
     * CU-01 "Registrar usuario".
     * Recibe el array $_POST completo del formulario de registro.
     * Devuelve ['exito' => bool, 'errores' => string[]].
     */
    public static function registrar(array $post): array
    {
        $pdo = Database::getConexion();
        $errores = [];

        // -----------------------------------------------------------------
        // Paso 4.1 del CU-01: validar que los campos obligatorios no estén
        // vacíos ANTES de tocar la base de datos.
        // -----------------------------------------------------------------
        $nombre = trim($post['nombre'] ?? '');
        $apellido = trim($post['apellido'] ?? '');
        $mail = trim($post['mail'] ?? '');
        $contrasena = $post['contrasena'] ?? '';
        $idLocalidad = $post['id_localidad'] ?? '';
        $esTrabajador = ($post['es_trabajador'] ?? 'no') === 'si';
        $oficio = trim($post['oficio'] ?? '');
        $localidadesTrabajo = $post['localidades_trabajo'] ?? [];

        if ($nombre === '' || $apellido === '' || $mail === '' || $contrasena === '' || $idLocalidad === '') {
            $errores[] = 'Campos obligatorios sin completar.';
        }

        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El mail ingresado no es válido.';
        }

        // Reglas extra si se registra como Trabajador (CU-01, sección "SI"):
        if ($esTrabajador) {
            if ($oficio === '') {
                $errores[] = 'Debe indicar su profesión/oficio.';
            }
            if (empty($localidadesTrabajo)) {
                $errores[] = 'Debe seleccionar al menos una localidad de trabajo.';
            }
        }

        if (!empty($errores)) {
            return ['exito' => false, 'errores' => $errores];
        }

        // -----------------------------------------------------------------
        // Chequeo de mail ya registrado (evita el error feo de MySQL por
        // violar la restricción UNIQUE, y da un mensaje claro al usuario).
        // -----------------------------------------------------------------
        $modeloUsuario = new Usuario($pdo);
        if ($modeloUsuario->buscarPorMail($mail) !== null) {
            return ['exito' => false, 'errores' => ['Ese mail ya está registrado. Iniciá sesión en su lugar.']];
        }

        // -----------------------------------------------------------------
        // Paso 6 del CU-01: crear la cuenta.
        //   - Cliente  -> estado 'activo' de inmediato.
        //   - Trabajador -> estado 'pendiente' hasta que lo habilite el
        //                    Administrador (CU-18).
        // -----------------------------------------------------------------
        $rol = $esTrabajador ? 'trabajador' : 'cliente';
        $estadoInicial = $esTrabajador ? 'pendiente' : 'activo';

        // password_hash() convierte la contraseña en un hash irreversible
        // (bcrypt). Nunca guardamos la contraseña en texto plano.
        $hashContrasena = password_hash($contrasena, PASSWORD_BCRYPT);

        // Usamos una transacción: si algo falla a mitad de camino (por
        // ejemplo, al guardar las localidades del trabajador), se deshace
        // TODO, para no dejar un usuario a medio crear en la base.
        $pdo->beginTransaction();
        try {
            $idUsuario = $modeloUsuario->crear([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'mail' => $mail,
                'contrasena' => $hashContrasena,
                'rol' => $rol,
                'id_localidad' => (int) $idLocalidad,
                'estado' => $estadoInicial,
            ]);

            if ($esTrabajador) {
                $modeloTrabajador = new Trabajador($pdo);
                $idTrabajador = $modeloTrabajador->crear($idUsuario, $oficio);
                $modeloTrabajador->asignarLocalidades($idTrabajador, $localidadesTrabajo);
            }

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log('Error al registrar usuario: ' . $e->getMessage());
            return ['exito' => false, 'errores' => ['Ocurrió un error al crear la cuenta. Intente nuevamente.']];
        }

        return ['exito' => true, 'errores' => []];
    }

    /**
     * CU-02 "Iniciar sesión".
     * Devuelve ['exito' => bool, 'mensaje' => string, 'usuario' => array|null].
     */
    public static function login(string $mail, string $contrasena): array
    {
        $mail = trim($mail);

        if ($mail === '' || $contrasena === '') {
            return ['exito' => false, 'mensaje' => 'Completá mail y contraseña.', 'usuario' => null];
        }

        $pdo = Database::getConexion();
        $modeloUsuario = new Usuario($pdo);
        $usuario = $modeloUsuario->buscarPorMail($mail);

        // Excepción 3.1 del CU-02: credenciales incorrectas.
        // OJO: usamos el mismo mensaje tanto si el mail no existe como si
        // la contraseña está mal, para no darle pistas a un atacante sobre
        // qué mails están registrados.
        if ($usuario === null || !password_verify($contrasena, $usuario['contrasena'])) {
            return ['exito' => false, 'mensaje' => 'Mail o contraseña incorrectos.', 'usuario' => null];
        }

        // Excepción 3.4: cuenta bloqueada por el Administrador.
        if ($usuario['estado'] === 'bloqueado') {
            return ['exito' => false, 'mensaje' => 'Cuenta suspendida. Contacte al administrador.', 'usuario' => null];
        }

        // Excepción 3.3: trabajador todavía no habilitado.
        if ($usuario['rol'] === 'trabajador' && $usuario['estado'] === 'pendiente') {
            return ['exito' => false, 'mensaje' => 'Tu cuenta está siendo revisada por el administrador.', 'usuario' => null];
        }

        // Camino feliz: credenciales válidas y cuenta en condiciones de entrar.
        return ['exito' => true, 'mensaje' => '', 'usuario' => $usuario];
    }

    /** CU logout: simplemente delega en el helper de sesión. */
    public static function logout(): void
    {
        Sesion::logout();
    }
}
