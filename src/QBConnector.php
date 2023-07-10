<?php

namespace Database\QueryBuilder;

class QBConnector
{
    private static \PDO $connection;
    private static array $config = [];
    public static function getConnection(): \PDO {
        if(!static::$connection){
            throw new QBException('Query Builder Error: Database connection is not set.');
        }
        return static::$connection;
    }
    public static function config(array $params = []): void {
        $connection = $params['connection'] ?? null;
        $host = $params['host'] ?? '127.0.0.1';
        $port = $params['port'] ?? 3306;
        $database = $params['database'] ?? null;
        $username = $params['username'] ?? null;
        $password = $params['password'] ?? null;
        $charset = $params['charset'] ?? 'utf8mb4';
        $timestamp = $params['timestamp'] ?? date('Y-m-d H:i:s');
        $audit_callback = $params['audit_callback'] ?? null;

        if ( !$connection ) {
            if( !$database || !$username || !$password ) {
                throw new QBException('Query Builder Error: Database, username and password are required');
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

        static::$config['host'] = $host;
        static::$config['port'] = $port;
        static::$config['database'] = $database;
        static::$config['username'] = $username;
        static::$config['password'] = $password;
        static::$config['charset'] = $charset;
        static::$config['timestamp'] = $timestamp;
        static::$config['audit_callback'] = $audit_callback;

        static::$connection = $connection;
    }

    public static function query(string $query, array $params = [], string $file = null, string|int $line = null): \PDOStatement
    {
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
            throw new QBException('Query Builder Error: '.$e->getMessage()." The error occurred in $file on line $line.");
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