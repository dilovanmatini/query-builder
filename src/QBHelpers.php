<?php

namespace Database\QueryBuilder;

use stdClass;

trait QBHelpers
{
    /**
     * To provide table alias for array of columns. Ex ('u', ['id', 'name']) will be ['u.id', 'u.name']
     * @param string $alias The alias of the table.
     * @param array $columns The columns of the table.
     * @return stdClass
     */
    public static function alias(string $alias, array $columns): stdClass{
        $obj = new stdClass();
        $obj->name = 'alias';
        $obj->alias = $alias;
        $obj->columns = $columns;
        return $obj;
    }

    /**
     * To provide raw SQL syntax. Ex ('IS NULL'), ('NOW()'), ('IF(condition, true, false)')
     * @param mixed $value The raw SQL syntax.
     * @return stdClass
     */
    public static function raw(mixed $value): stdClass{
        $obj = new stdClass();
        $obj->name = 'raw';
        $obj->value = $value;
        return $obj;
    }

    /**
     * To provide NOW() function.
     * @return stdClass
     */
    public static function now(): stdClass{
        $obj = new stdClass();
        $obj->name = 'now';
        return $obj;
    }

    /**
     * To send the value as placeholder in SQL query. Ex ('Ahmed'), (123), ('name@gmail.com', 'email')
     * @param mixed $value The value to be sent as placeholder.
     * @param string|null $key If you want to use the same placeholder multiple times, you can provide a key for it.
     * @return stdClass
     */
    public static function param(mixed $value, string $key = null): stdClass{
        $obj = new stdClass();
        $obj->name = 'param';
        $obj->key = $key;
        $obj->value = $value;
        return $obj;
    }
}