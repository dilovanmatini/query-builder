<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

class QBConfig
{

    /**
     * list of available configurations
     * @var array
     */
    private static array $config = [

        /**
         * It accepts a database connection object.
         * @var \PDO $config['connection']
         */
        'connection' => null,

        /**
         * It accepts a Model class name especially for projects using MVC pattern.
         *
         * @var string|null $config['model_class']
         */
        'model_class' => null,

        /**
         * It accepts all PDO fetch modes.
         *
         * @var int $config['fetch_mode']
         */
        'fetch_mode' => \PDO::FETCH_OBJ,

        /**
         * Database host name or IP address.
         *
         * @var string $config['host']
         */
        'host' => '127.0.0.1',

        /**
         * Database port number.
         *
         * @var int $config['port']
         */
        'port' => 3306,

        /**
         * Database name.
         *
         * @var string $config['database']
         */
        'database' => 'test',

        /**
         * Database username.
         *
         * @var string $config['username']
         */
        'username' => 'root',

        /**
         * Database password.
         *
         * @var string $config['password']
         */
        'password' => '',

        /**
         * Database charset.
         *
         * @var string $config['charset']
         */
        'charset' => 'utf8mb4',
    ];

    /**
     * It sets the set of configurations.
     * @param string|array $params
     * @param mixed $value
     * @return void
     */
    public static function set(array|string $params, mixed $value = null): void
    {
        if (is_array($params)) {
            foreach ($params as $name => $value) {
                if (array_key_exists($name, static::$config)) {
                    static::$config[$name] = $value;
                }
            }
        } else {
            if (array_key_exists($params, static::$config)) {
                static::$config[$params] = $value;
            }
        }
    }

    /**
     * It returns the given configuration.
     * @param string $name
     * @return mixed
     */
    public static function get(string $name): mixed
    {
        return static::$config[$name] ?? null;
    }

    public static function getQueryConfig(): array
    {
        return [
            'model_class' => static::$config['model_class'],
            'fetch_mode' => static::$config['fetch_mode'],
        ];
    }
}