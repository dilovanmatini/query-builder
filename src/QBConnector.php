<?php

namespace Database\QueryBuilder;

class QBConnector
{
    private static ?\PDO $connection = null;
    private static array $config = [];
    private static bool $configSet = false;

    public static function getConnection(): \PDO
    {
        if (!static::$connection) {
            throw new QBException('Query Builder Error: Database connection is not set.');
        }
        return static::$connection;
    }

    public static function config(array $params = []): void
    {
        if(static::$configSet){
            return;
        }

        static::$connection = $params['connection'] ?? null;
        static::$config['host'] = $params['host'] ?? '127.0.0.1';
        static::$config['port'] = $params['port'] ?? 3306;
        static::$config['database'] = $params['database'] ?? null;
        static::$config['username'] = $params['username'] ?? null;
        static::$config['password'] = $params['password'] ?? null;
        static::$config['charset'] = $params['charset'] ?? 'utf8mb4';
        static::$config['timestamp'] = $params['timestamp'] ?? date('Y-m-d H:i:s');
        static::$config['model_class'] = $params['model_class'] ?? null;
        static::$config['audit_callback'] = $params['audit_callback'] ?? null;
        static::$configSet = true;
    }

    public static function coonnect(): void
    {
        if(static::$connection){
            return;
        }

        $host = static::$config['host'];
        $port = static::$config['port'];
        $database = static::$config['database'];
        $username = static::$config['username'];
        $password = static::$config['password'];
        $charset = static::$config['charset'];

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

    public static function query(string $query, array $params = [], string $file = null, string|int $line = null): \PDOStatement
    {
        if (static::$connection === null) {
            static::coonnect();
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

    public static function auditCallback(string $type, string $table, int $id = 0, array $audit_data = [], string $file = null, int|string $line = null): void
    {
        $audit_callback = static::$config['audit_callback'] ?? null;
        if (is_callable($audit_callback)) {
            $audit_callback($type, $table, $id, $audit_data, $file, $line);
        }
    }

    public static function getConfig(string $name): mixed
    {
        return static::$config[$name] ?? null;
    }
}
