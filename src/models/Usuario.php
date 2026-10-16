<?php
// src/models/Usuario.php
// ---------------------------------------------------------------------------
// Modelo de la tabla USUARIO. Contiene ÚNICAMENTE consultas relacionadas
// a esa tabla (crear usuario, buscar por mail, etc). No sabe nada de
// formularios, ni de HTML, ni de sesiones: solo de la base de datos.
// Todas las consultas usan sentencias preparadas (bindValue) para evitar
// inyección SQL, tal como exige la arquitectura de software del proyecto.
// ---------------------------------------------------------------------------

class Usuario
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Busca un usuario por su mail. Se usa tanto en el login (CU-02) como
     * en el registro, para chequear que el mail no esté ya usado (CU-01).
     * Devuelve el array con los datos, o null si no existe.
     */
    public function buscarPorMail(string $mail): ?array
    {
        $sql = 'SELECT * FROM USUARIO WHERE mail = :mail LIMIT 1';
        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':mail', $mail);
        $consulta->execute();

        $resultado = $consulta->fetch();
        return $resultado ?: null;
    }

    /**
     * Crea un nuevo usuario (Cliente o Trabajador) y devuelve su id.
     * La contraseña que llega en $datos['contrasena'] YA debe venir
     * hasheada (ver AuthController), esta clase nunca hashea nada,
     * solo guarda lo que le dan.
     */
    public function crear(array $datos): int
    {
        $sql = 'INSERT INTO USUARIO (nombre, apellido, mail, contrasena, rol, id_localidad, estado)
                VALUES (:nombre, :apellido, :mail, :contrasena, :rol, :id_localidad, :estado)';

        $consulta = $this->pdo->prepare($sql);
        $consulta->bindValue(':nombre', $datos['nombre']);
        $consulta->bindValue(':apellido', $datos['apellido']);
        $consulta->bindValue(':mail', $datos['mail']);
        $consulta->bindValue(':contrasena', $datos['contrasena']);
        $consulta->bindValue(':rol', $datos['rol']);
        $consulta->bindValue(':id_localidad', $datos['id_localidad'], PDO::PARAM_INT);
        $consulta->bindValue(':estado', $datos['estado']);
        $consulta->execute();

        // lastInsertId() devuelve el id_usuario que MySQL le acaba de asignar
        // a la fila (gracias a que la columna es AUTO_INCREMENT).
        return (int) $this->pdo->lastInsertId();
    }

    // =====================================================================
    // FASE 7 - Bloqueo temporal de login (CU-02 excepción 3.2)
    // =====================================================================

    /**
     * Minutos que le faltan a una cuenta para dejar de estar bloqueada
     * temporalmente. 0 = no está bloqueada. Se calcula con la hora de la base
     * de datos (NOW()) para que no dependa de la hora del servidor web.
     */
    public function minutosDeBloqueoRestantes(int $idUsuario): int
    {
        $consulta = $this->pdo->prepare(
            'SELECT CEIL(TIMESTAMPDIFF(SECOND, NOW(), bloqueado_hasta) / 60)
               FROM USUARIO WHERE id_usuario = ? AND bloqueado_hasta IS NOT NULL'
        );
        $consulta->execute([$idUsuario]);
        return max(0, (int) $consulta->fetchColumn());
    }

    /** Guarda un intento fallido más (todavía sin llegar al límite). */
    public function guardarIntentosFallidos(int $idUsuario, int $intentos): void
    {
        $consulta = $this->pdo->prepare('UPDATE USUARIO SET intentos_fallidos = ? WHERE id_usuario = ?');
        $consulta->execute([$intentos, $idUsuario]);
    }

    /** Bloquea la cuenta $minutos minutos y reinicia el contador de intentos. */
    public function bloquearTemporalmente(int $idUsuario, int $minutos): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE USUARIO SET intentos_fallidos = 0, bloqueado_hasta = DATE_ADD(NOW(), INTERVAL ? MINUTE)
              WHERE id_usuario = ?'
        );
        $consulta->execute([$minutos, $idUsuario]);
    }

    /** Login correcto: reinicia el contador, levanta bloqueos vencidos y anota el último acceso (CU-16). */
    public function registrarLoginExitoso(int $idUsuario): void
    {
        $consulta = $this->pdo->prepare(
            'UPDATE USUARIO SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW()
              WHERE id_usuario = ?'
        );
        $consulta->execute([$idUsuario]);
    }

    // =====================================================================
    // FASE 7 - Sesión y gestión de usuarios (CU-13)
    // =====================================================================

    /**
     * Estado actual de un usuario en la base ('activo' | 'pendiente' | 'bloqueado'),
     * o null si ya no existe. Lo usa Sesion::requerirLogin() para sacar al
     * instante a quien el Administrador bloqueó o eliminó mientras estaba logueado.
     */
    public function obtenerEstado(int $idUsuario): ?string
    {
        $consulta = $this->pdo->prepare('SELECT estado FROM USUARIO WHERE id_usuario = ?');
        $consulta->execute([$idUsuario]);
        $estado = $consulta->fetchColumn();
        return $estado !== false ? $estado : null;
    }

    /** Datos básicos de un usuario por id (null si no existe). */
    public function buscarPorId(int $idUsuario): ?array
    {
        $consulta = $this->pdo->prepare(
            'SELECT id_usuario, nombre, apellido, mail, rol, estado FROM USUARIO WHERE id_usuario = ?'
        );
        $consulta->execute([$idUsuario]);
        $fila = $consulta->fetch();
        return $fila ?: null;
    }

    /**
     * Lista para la pantalla "Gestión de usuarios" (CU-13 paso 3).
     * NO incluye a los administradores: así un admin nunca aparece con botón
     * de bloquear o eliminar. Filtros opcionales: rol, estado y texto (nombre/mail).
     * El "estado visible" distingue al trabajador RECHAZADO (su USUARIO sigue
     * 'pendiente' pero su TRABAJADOR dice 'rechazado').
     */
    public function listarParaAdmin(string $rol, string $estado, string $texto, int $limite = 200): array
    {
        $limite = (int) $limite;
        $params = [];
        $filtros = '';

        if (in_array($rol, ['cliente', 'trabajador'], true)) {
            $filtros .= ' AND u.rol = ?';
            $params[] = $rol;
        }
        if ($texto !== '') {
            $filtros .= ' AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.mail LIKE ? OR CONCAT(u.nombre, \' \', u.apellido) LIKE ?)';
            $comodin = '%' . $texto . '%';
            array_push($params, $comodin, $comodin, $comodin, $comodin);
        }

        $sql = "SELECT * FROM (
                    SELECT u.id_usuario, u.nombre, u.apellido, u.mail, u.rol, u.fecha_registro,
                           l.nombre AS localidad, t.oficio,
                           CASE WHEN t.estado_habilitacion = 'rechazado' THEN 'rechazado' ELSE u.estado END AS estado_visible
                      FROM USUARIO u
                      LEFT JOIN LOCALIDAD l ON l.id_localidad = u.id_localidad
                      LEFT JOIN TRABAJADOR t ON t.id_usuario = u.id_usuario
                     WHERE u.rol <> 'admin' {$filtros}
                ) AS lista
                WHERE 1 = 1";

        if (in_array($estado, ['activo', 'pendiente', 'bloqueado', 'rechazado'], true)) {
            $sql .= ' AND estado_visible = ?';
            $params[] = $estado;
        }
        $sql .= " ORDER BY fecha_registro DESC, id_usuario DESC LIMIT {$limite}";

        $consulta = $this->pdo->prepare($sql);
        $consulta->execute($params);
        return $consulta->fetchAll();
    }

    /**
     * Cambia el estado SOLO si el usuario está en $estadoEsperado y no es admin.
     * Devuelve true si cambió algo (así un doble clic no se aplica dos veces).
     */
    public function cambiarEstado(int $idUsuario, string $estadoNuevo, string $estadoEsperado): bool
    {
        $consulta = $this->pdo->prepare(
            "UPDATE USUARIO SET estado = ? WHERE id_usuario = ? AND estado = ? AND rol <> 'admin'"
        );
        $consulta->execute([$estadoNuevo, $idUsuario, $estadoEsperado]);
        return $consulta->rowCount() > 0;
    }

    /** Elimina la cuenta (y, por ON DELETE CASCADE, todo lo suyo). Nunca a un admin. */
    public function eliminar(int $idUsuario): bool
    {
        $consulta = $this->pdo->prepare("DELETE FROM USUARIO WHERE id_usuario = ? AND rol <> 'admin'");
        $consulta->execute([$idUsuario]);
        return $consulta->rowCount() > 0;
    }
}
