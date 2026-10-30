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
        // Fase 8: si alguien arma el POST a mano mandando una LISTA donde va un texto
        // (ej. nombre[]=x), PHP daría error 500. Este ayudante lo convierte en vacío.
        $texto = fn(string $clave): string => is_string($post[$clave] ?? null) ? $post[$clave] : '';

        $nombre = trim($texto('nombre'));
        $apellido = trim($texto('apellido'));
        $mail = trim($texto('mail'));
        $contrasena = $texto('contrasena');
        $idLocalidad = $texto('id_localidad');
        $esTrabajador = $texto('es_trabajador') === 'si';
        // Fase 9 (punto 7): los oficios llegan como LISTA de ids del catálogo (oficios[])
        // y las zonas como lista de ids (localidades_trabajo[], armada por el autocompletado).
        $oficiosElegidos = $post['oficios'] ?? [];
        $localidadesTrabajo = $post['localidades_trabajo'] ?? [];

        if ($nombre === '' || $apellido === '' || $mail === '' || $contrasena === '' || $idLocalidad === '') {
            $errores[] = 'Campos obligatorios sin completar.';
        }

        if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El mail ingresado no es válido.';
        }

        // Fase 8: largos máximos = tamaño de las columnas de USUARIO/TRABAJADOR.
        // Sin esto, un texto demasiado largo provocaba un error genérico de la base.
        if (mb_strlen($nombre) > 60 || mb_strlen($apellido) > 60) {
            $errores[] = 'El nombre y el apellido pueden tener hasta 60 caracteres.';
        }
        if (mb_strlen($mail) > 120) {
            $errores[] = 'El mail puede tener hasta 120 caracteres.';
        }

        // Fase 8: contraseña mínima (8) y máxima (72 bytes: bcrypt ignora el resto).
        if ($contrasena !== '' && mb_strlen($contrasena) < Config::MIN_LARGO_CONTRASENA) {
            $errores[] = 'La contraseña debe tener al menos ' . Config::MIN_LARGO_CONTRASENA . ' caracteres.';
        }
        if (strlen($contrasena) > Config::MAX_BYTES_CONTRASENA) {
            $errores[] = 'La contraseña es demasiado larga (máximo ' . Config::MAX_BYTES_CONTRASENA . ' caracteres).';
        }

        // Fase 8: los datos deben tener la forma esperada (alguien puede armar
        // el POST a mano con texto donde va una lista, o una localidad inexistente).
        if ($idLocalidad !== '' && !(new Localidad($pdo))->existe((int) $idLocalidad)) {
            $errores[] = 'La localidad elegida no es válida.';
        }
        // Fase 9: la validación de oficios y zonas vive en CatalogoController (la comparte con Mi perfil).
        $oficiosIds = [];
        $oficioTexto = '';
        $zonasIds = [];

        // Reglas extra si se registra como Trabajador (CU-01, sección "SI"):
        if ($esTrabajador) {
            $oficios = CatalogoController::resolverOficios($oficiosElegidos);
            if ($oficios['error'] !== null) {
                $errores[] = $oficios['error'];
            } else {
                $oficiosIds = $oficios['ids'];
                $oficioTexto = $oficios['texto'];
            }
            $zonas = CatalogoController::resolverLocalidades($localidadesTrabajo);
            if ($zonas['error'] !== null) {
                $errores[] = $zonas['error'];
            } else {
                $zonasIds = $zonas['ids'];
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
                $idTrabajador = $modeloTrabajador->crear($idUsuario, $oficioTexto);
                $modeloTrabajador->reemplazarOficios($idTrabajador, $oficiosIds);   // Fase 9: varios oficios
                $modeloTrabajador->asignarLocalidades($idTrabajador, $zonasIds);
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
        if ($usuario === null) {
            return ['exito' => false, 'mensaje' => 'Mail o contraseña incorrectos.', 'usuario' => null];
        }
        $idUsuario = (int) $usuario['id_usuario'];

        // Excepción 3.2 (Fase 7): la cuenta está bloqueada TEMPORALMENTE por
        // demasiados intentos fallidos. Se avisa antes de mirar la contraseña:
        // mientras dura el bloqueo no se acepta ni la contraseña correcta.
        // (Esto revela que el mail existe; es el costo habitual de este tipo de bloqueo.)
        $minutos = $modeloUsuario->minutosDeBloqueoRestantes($idUsuario);
        if ($minutos > 0) {
            return ['exito' => false, 'usuario' => null,
                    'mensaje' => 'Cuenta bloqueada temporalmente por demasiados intentos fallidos. '
                               . 'Probá de nuevo en ' . $minutos . ' minuto' . ($minutos === 1 ? '' : 's') . '.'];
        }

        if (!password_verify($contrasena, $usuario['contrasena'])) {
            // Cuenta el intento fallido; al llegar al límite se bloquea y se avisa.
            if (Config::MINUTOS_BLOQUEO_LOGIN > 0) {
                $intentos = (int) $usuario['intentos_fallidos'] + 1;
                if ($intentos >= Config::MAX_INTENTOS_LOGIN) {
                    $modeloUsuario->bloquearTemporalmente($idUsuario, Config::MINUTOS_BLOQUEO_LOGIN);
                    return ['exito' => false, 'usuario' => null,
                            'mensaje' => 'Superaste los ' . Config::MAX_INTENTOS_LOGIN . ' intentos permitidos. '
                                       . 'Tu cuenta quedó bloqueada por ' . Config::MINUTOS_BLOQUEO_LOGIN . ' minutos.'];
                }
                $modeloUsuario->guardarIntentosFallidos($idUsuario, $intentos);
            }
            return ['exito' => false, 'mensaje' => 'Mail o contraseña incorrectos.', 'usuario' => null];
        }

        // Excepción 3.4: cuenta bloqueada por el Administrador.
        if ($usuario['estado'] === 'bloqueado') {
            return ['exito' => false, 'mensaje' => 'Cuenta suspendida. Contacte al administrador.', 'usuario' => null];
        }

        // Excepción 3.3: trabajador todavía no habilitado (o rechazado, CU-18 paso 9b).
        if ($usuario['rol'] === 'trabajador' && $usuario['estado'] === 'pendiente') {
            $habilitacion = (new Trabajador($pdo))->estadoHabilitacionDeUsuario($idUsuario);
            if ($habilitacion !== null && $habilitacion['estado_habilitacion'] === 'rechazado') {
                $motivo = trim((string) $habilitacion['motivo_rechazo']);
                return ['exito' => false, 'usuario' => null,
                        'mensaje' => 'Tu cuenta no fue habilitada.' . ($motivo !== '' ? ' Motivo: ' . $motivo : '')];
            }
            return ['exito' => false, 'mensaje' => 'Tu cuenta está siendo revisada por el administrador.', 'usuario' => null];
        }

        // Login correcto: se reinicia el contador de intentos y se anota el último acceso.
        $modeloUsuario->registrarLoginExitoso($idUsuario);

        // Camino feliz: credenciales válidas y cuenta en condiciones de entrar.
        return ['exito' => true, 'mensaje' => '', 'usuario' => $usuario];
    }

    /** CU logout: simplemente delega en el helper de sesión. */
    public static function logout(): void
    {
        Sesion::logout();
    }
}
