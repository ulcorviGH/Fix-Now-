<?php
// src/config/Database.php
// ---------------------------------------------------------------------------
// Clase encargada de abrir UNA sola conexión a MySQL usando PDO
// (PHP Data Objects), tal como se definió en la arquitectura de software
// del proyecto (PHP 8 POO + PDO, para prevenir inyección SQL con
// consultas preparadas).
//
// Usa el patrón "Singleton": no importa cuántas veces se llame a
// Database::getConexion(), siempre devuelve la MISMA conexión ya abierta,
// en vez de abrir una nueva cada vez (eso ahorra recursos del servidor).
// ---------------------------------------------------------------------------

class Database
{
    // Guarda la única instancia de la conexión que va a existir en toda la app.
    private static ?PDO $instancia = null;

    // Constructor privado: nadie puede hacer "new Database()" desde afuera,
    // así se obliga a usar siempre el método getConexion() de abajo.
    private function __construct()
    {
    }

    /**
     * Devuelve la conexión PDO activa. Si todavía no existe, la crea
     * usando los datos que llegan desde las variables de entorno
     * definidas en docker-compose.yml (DB_HOST, DB_NAME, DB_USER, DB_PASS).
     */
    public static function getConexion(): PDO
    {
        if (self::$instancia === null) {
            $host = getenv('DB_HOST');
            $nombreBD = getenv('DB_NAME');
            $usuario = getenv('DB_USER');
            $clave = getenv('DB_PASS');

            // DSN = Data Source Name: le dice a PDO qué motor, host, puerto
            // y base de datos usar, y en qué charset (utf8mb4 = tildes y ñ ok).
            $dsn = "mysql:host={$host};dbname={$nombreBD};charset=utf8mb4";

            // Opciones recomendadas para trabajar de forma segura y prolija:
            $opciones = [
                // Si algo falla, que lance una excepción en vez de un error silencioso.
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                // Que las consultas SELECT devuelvan arrays asociativos (['campo' => valor]).
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Usa consultas preparadas reales del motor (más seguro contra inyección SQL).
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            try {
                self::$instancia = new PDO($dsn, $usuario, $clave, $opciones);
            } catch (PDOException $error) {
                // No mostramos el mensaje completo al usuario final por seguridad,
                // pero sí lo registramos para que el desarrollador lo pueda ver.
                error_log('Error de conexión a la base de datos: ' . $error->getMessage());
                die('No se pudo conectar a la base de datos. Intente más tarde.');
            }
        }

        return self::$instancia;
    }
}
