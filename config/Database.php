<?php
declare(strict_types=1);

/**
 * Gestor de conexión a la base de datos institucional db_tickets_soporte
 * Implementa PDO estricto con sentencias preparadas nativas
 */
class Database
{
    private static ?PDO $instanciaConexion = null;
    private string $servidor;
    private string $baseDatos;
    private string $usuario;
    private string $clave;
    private string $codificacion;

    public function __construct()
    {
        $this->servidor     = defined('DB_HOST') ? DB_HOST : 'localhost';
        $this->baseDatos    = defined('DB_NAME') ? DB_NAME : 'db_tickets_soporte';
        $this->usuario      = defined('DB_USER') ? DB_USER : 'root';
        $this->clave        = defined('DB_PASS') ? DB_PASS : '';
        $this->codificacion = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
    }

    /**
     * Devuelve la conexión activa mediante PDO estricto
     */
    public function obtenerConexion(): PDO
    {
        if (self::$instanciaConexion === null) {
            $dsn = "mysql:host={$this->servidor};dbname={$this->baseDatos};charset={$this->codificacion}";
            $atributoInit = class_exists(\Pdo\Mysql::class)
                ? \Pdo\Mysql::ATTR_INIT_COMMAND
                : PDO::MYSQL_ATTR_INIT_COMMAND;

            $opciones = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                $atributoInit                => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];

            try {
                self::$instanciaConexion = new PDO($dsn, $this->usuario, $this->clave, $opciones);
                self::$instanciaConexion->exec("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
            } catch (PDOException $excepcion) {
                error_log('Error de conexión con la base de datos: ' . $excepcion->getMessage());
                http_response_code(500);
                die('El servicio de datos institucional no está disponible en este momento. Verifique que el servidor MySQL esté activo.');
            }
        }

        return self::$instanciaConexion;
    }

    /**
     * Acceso estático directo a la conexión PDO compartida
     */
    public static function conectar(): PDO
    {
        $gestor = new self();
        return $gestor->obtenerConexion();
    }
}
