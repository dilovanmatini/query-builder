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
         * The given callback function will be called after executing INSERT, UPDATE, and DELETE queries.
         * The callback function accepts the below arguments:
         * @param string $type expected values: insert, update, delete
         * @param string $table the affected table name
         * @param int $id the affected id number
         * @param array $audit_data if the $type is update, it provides the updated columns
         * @param string $file if you provide the __FILE__ with your query, it will return to the callback function.
         * @param int|string $line if you provide the __LINE__ with your query, it will return to the callback function.
         *
         * @var callable|null $config['audit_callback']
         */
        'audit_callback' => null,

        /**
         * It provides soft delete functionality. It will add a current timestamp to the soft_delete_column.
         * By enabling this feature, you need to take "soft_delete_column" and "timestamp" configurations into account.
         *
         * @var bool $config['soft_delete']
         */
        'soft_delete' => false,

        /**
         * It accepts a column name for soft delete functionality. By default, it is "deleted_at".
         *
         * @var string $config['soft_delete_column']
         */
        'soft_delete_column' => 'deleted_at',

        /**
         * It accepts a timestamp value for soft delete functionality. By default, it is "now()".
         *
         * @var string $config['timestamp']
         */
        'timestamp' => null,

        /**
         * It accepts a Model class name especially for projects using MVC pattern.
         *
         * @var string|null $config['model_class']
         */
        'model_class' => null,

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
     * It initializes the default configurations.
     * @return void
     */
    public static function init(): void
    {
        if (static::$config['timestamp'] === null) {
            static::$config['timestamp'] = date('Y-m-d H:i:s');
        }
    }

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

        static::init();
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

    public static function auditCallback(string $type, string $table, int $id = 0, array $audit_data = [], string $file = null, int|string $line = null): void
    {
        $audit_callback = static::$config['audit_callback'] ?? null;
        if (is_callable($audit_callback)) {
            $audit_callback($type, $table, $id, $audit_data, $file, $line);
        }
    }
}