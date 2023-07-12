<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

class QBConnector
{
    /**
     * @var ?\PDO $connection PDO instance
     */
    private static ?\PDO $connection = null;

    /**
     * @return \PDO PDO instance
     * @throws QBException
     */
    public static function getConnection(): \PDO
    {
        if (!static::$connection) {
            throw new QBException('Query Builder Error: Database connection is not set.');
        }
        return static::$connection;
    }

    /**
     * @return void Connect to database
     * @throws QBException
     */
    public static function connect(): void
    {
        if(static::$connection){
            return;
        }

        $connection = QBConfig::get('connection');

        // Indicates that the library is being used in Laravel
        if (QB::isLaravel()) {
            try {
                $container = \Illuminate\Container\Container::getInstance();
                $connection = $container->make(\Illuminate\Database\ConnectionInterface::class);
                $connection = $connection->getPdo();
            } catch (\Exception $e) {
            }
        }

        if (!$connection) {

            $host = QBConfig::get('host');
            $port = QBConfig::get('port');
            $database = QBConfig::get('database');
            $username = QBConfig::get('username');
            $password = QBConfig::get('password');
            $charset = QBConfig::get('charset');

            if (!$database || !$username || !$password) {
                throw new QBException('Query Builder Error: A PDO instance is required. You can pass DB credentials instead. For more details, check README.md file');
            }

            try {
                $dsn = "mysql:host=$host;dbname=$database;port=$port;charset=$charset;";
                $connection = new \PDO($dsn, $username, $password);
            } catch (\PDOException $e) {
                throw new QBException('Query Builder Error: Failed to connect to database.');
            }

            $connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);
        }

        static::$connection = $connection;
    }

    /**
     * @param string $query SQL query
     * @param array $params Query parameters to be bound
     * @param string|null $file File name
     * @param string|int|null $line Line number
     * @return \PDOStatement
     * @throws QBException
     */
    public static function query(string $query, array $params = [], string $file = null, string|int $line = null): \PDOStatement
    {
        if (static::$connection === null) {
            static::connect();
        }

        try {
            $stmt = static::getConnection()->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            if (is_null($file) || is_null($line)) {
                $trace = debug_backtrace();
                $file = $trace[0]['file'] ?? null;
                $line = $trace[0]['line'] ?? null;
            }
            throw new QBException('Query Builder Error: ' . $e->getMessage() . " The error occurred in $file on line $line.");
        }
    }
}
