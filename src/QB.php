<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

use stdClass;

/**
 * @method static QBSelect select(array|string $columns = '*')
 * @method static QBUpdate update(string|Model $table, string $as = null)
 * @method static QBInsert insert(string|Model $table)
 * @method static QBInsert insertInto(string|Model $table)
 * @method static QBDelete delete(string|Model $table, string $as = null)
 * @method static QBDelete deleteFrom(string|Model $table, string $as = null)
 * @method static QBWhere where(mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method static QBIf if (mixed $condition, mixed $successValue, mixed $failedValue)
 *
 * @method static stdClass equal(mixed $value)
 * @method static stdClass notEqual(mixed $value)
 * @method static stdClass greaterThan(mixed $value)
 * @method static stdClass lessThan(mixed $value)
 * @method static stdClass greaterThanOrEqual(mixed $value)
 * @method static stdClass lessThanOrEqual(mixed $value)
 * @method static stdClass like(mixed $value)
 * @method static stdClass notLike(mixed $value)
 * @method static stdClass between(mixed $value1, mixed $value2)
 * @method static stdClass notBetween(mixed $value1, mixed $value2)
 * @method static stdClass in(string|array $value)
 * @method static stdClass notIn(string|array $value)
 * @method static stdClass isNull()
 * @method static stdClass isNotNull()
 * @method static stdClass isEmpty()
 * @method static stdClass isNotEmpty()
 *
 * @method static QBAggregate count(string $value, string $alias = null)
 * @method static QBAggregate sum(string $value, string $alias = null)
 * @method static QBAggregate min(string $value, string $alias = null)
 * @method static QBAggregate max(string $value, string $alias = null)
 * @method static QBAggregate avg(string $value, string $alias = null)
 * @method static QBAggregate distinct(string $value, string $alias = null)
 *
 */
class QB
{
    use QBHelpers;

    /**
     * @var array $queryTypes Query types
     */
    public static array $queryTypes = ['select', 'update', 'insert', 'insertInto', 'delete', 'deleteFrom'];

    public static array $joinTypes = [
        'leftJoin' => 'LEFT JOIN',
        'rightJoin' => 'RIGHT JOIN',
        'crossJoin' => 'CROSS JOIN',
        'innerJoin' => 'INNER JOIN',
        'fullJoin' => 'FULL JOIN',
    ];

    /**
     * @var array $comparisonOperators Comparison operators
     */
    public static array $comparisonOperators = [
        'equal', 'notEqual', 'greaterThan', 'lessThan', 'greaterThanOrEqual', 'lessThanOrEqual', 'like', 'notLike',
        'between', 'notBetween', 'in', 'notIn', 'isNull', 'isNotNull', 'isEmpty', 'isNotEmpty'
    ];

    /**
     * @var array $comparisonPureOperators Comparison operators
     */
    public static array $comparisonPureOperators = ['=', '!=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE', 'BETWEEN', 'NOT BETWEEN', 'IN', 'NOT IN'];

    /**
     * @var array $aggregationFunctions Aggregation functions
     */
    public static array $aggregationFunctions = [
        'count', 'sum', 'min', 'max', 'avg', 'distinct'
    ];

    /**
     * Receives all unknown static method calls.
     * @param string $method
     * @param array $arguments
     * @return mixed
     * @throws QBException
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (in_array($method, self::$queryTypes)) {
            $method = str_replace('insertInto', 'insert', $method);
            $method = str_replace('deleteFrom', 'delete', $method);
            $statement = "Database\\QueryBuilder\\QB" . ucfirst($method);
            return (new $statement())->$method(...$arguments);
        } elseif ($method == 'where') {
            return (new QBWhere())->where(...$arguments);
        } elseif (in_array($method, self::$comparisonOperators)) {
            $obj = new stdClass();
            $obj->name = $method;
            $obj->arguments = $arguments;
            return $obj;
        } elseif (in_array($method, self::$aggregationFunctions)) {
            return (new QBAggregate())->$method(...$arguments);
        } elseif ($method == 'if') {
            return (new QBIf())->if(...$arguments);
        }
        throw new QBException("Method $method not found");
    }

    /**
     * @param array $params Config parameters
     * @example QB::config([
     *      'connection' => null,
     *      'model_class' => null,
     *      'host' => 'localhost',
     *      'port' => 3306,
     *      'database' => 'test',
     *      'username' => 'root',
     *      'password' => '',
     *      'charset' => 'utf8',
     * ]);
     * @return void
     */
    public static function config(array $params): void
    {
        QBConfig::set(...func_get_args());
    }

    /**
     * Resolve the value and return the value and the type
     * @param string $type
     * @param mixed $value
     * @param mixed|null $extra_data
     * @return array
     */
    public static function resolve(...$args): array
    {
        $type = array_shift($args);
        return (new QBResolver())->$type(...$args);
    }

    /**
     * @return bool
     */
    public static function isLaravel(): bool
    {
        return class_exists('Illuminate\Foundation\Application');
    }

    /**
     *
     * QB::insert('users')->values([
     *        'firstname' => DB::idata('Zahraa'),
     *        'lastname' => DB::idata('Alaa'),
     *        'email' => DB::idata('zara@yahoo.com', true),                                    // true means insert the row
     *        'password' => DB::idata('456', false),                                            // false means don't insert
     *    'fullname' => DB::idata('Zahraa Alaa', check: ['firstname', 'lastname']),        // do insert if firstname or lastname inserted. It should be set after the fields that you want to check
     *        'birthdate' => DB::idata("DATE()", raw: true),                                    // it means passing raw for the value to the query
     *        'country' => DB::idata(function() { return 'Sweden'; })                        // you can use a function to get the new value
     * ])->run(__FILE__, __LINE__);
     *
     * @param mixed $value
     * @param bool $allow Allow insert or not
     * @param array $check Check if array fields inserted. It should be set after the fields that you want to check
     * @param bool $raw Pass raw for the value to the query
     * @return array
     */
    public static function idata(mixed $value, bool $allow = true, array $check = [], bool $raw = false): array
    {
        return [$value, $allow, $check, $raw];
    }

    /**
     * QB::update('users')->set([
     *        'firstname' => DB::udata('Zara', 'Zahraa'),
     *        'lastname' => DB::udata('Ali', 'Alaa'),
     *        'email' => DB::udata('zara@gmail.com', 'zara@yahoo.com', true),                        // true means update the row
     *        'password' => DB::udata('123', '456', false),                                            // false means don't update
     *        'fullname' => DB::udata('Zara Ali', 'Zahraa Alaa', check: ['firstname', 'lastname']),    // do update if firstname or lastname changed. It should be set after the fields that you want to check
     *        'birthdate' => DB::udata('1999-12-20', "DATE()", raw: true),                            // it means passing raw for the new_value to the query
     *        'country' => DB::udata('Spain', function() { return 'Sweden'; })                        // you can use a function to get the new value
     * ])->where('id = 23')->run(__FILE__, __LINE__);
     *
     * @param mixed $old_value The old value
     * @param mixed $new_value The new value
     * @param bool $allow Allow update or not
     * @param array $check Check if array fields changed. It should be set after the fields that you want to check
     * @param bool $raw Pass raw for the new_value to the query
     * @return array
     */
    public static function udata(mixed $old_value, mixed $new_value, bool $allow = true, array $check = [], bool $raw = false): array
    {
        return [$old_value, $new_value, $allow, $check, $raw];
    }
}
