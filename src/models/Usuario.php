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
}
