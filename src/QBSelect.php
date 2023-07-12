<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

/**
 * @method QBSelect leftJoin(string|Model $table, string $as = null)
 * @method QBSelect rightJoin(string|Model $table, string $as = null)
 * @method QBSelect crossJoin(string|Model $table, string $as = null)
 * @method QBSelect innerJoin(string|Model $table, string $as = null)
 * @method QBSelect fullJoin(string|Model $table, string $as = null)
 *
 * @method QBSelect and (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBSelect or (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */
class QBSelect extends QBStatement
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Receives all unknown method calls.
     * @param string $method
     * @param array $arguments
     * @return QBSelect
     * @throws QBException
     */
    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, array_keys(QB::$joinTypes))) {
            $this->join(QB::$joinTypes[$method], ...$arguments);
        } elseif (in_array($method, ['and', 'or'])) {
            $this->checkOrder($method, [
                'join' => ['on', 'and', 'or'],
                'where' => ['where', 'and', 'or'],
                'having' => ['having', 'and', 'or']
            ]);

            if ($this->data->prevState == 'where') {
                $this->data->where->$method(...$arguments);
            } elseif ($this->data->prevState == 'join') {
                $this->getDataLast('join', 'on')->$method(...$arguments);
            } elseif ($this->data->prevState == 'having') {
                $this->data->having->$method(...$arguments);
            }

            $this->setMethod($method);
        }
        return $this;
    }

    /**
     * Sets the columns to be selected.
     * ->select()
     * ->select('*')
     * ->select('column1')
     * ->select('column1, column2')
     * ->select('column1', 'column2')
     * ->select(['column1', 'column2'])
     * @param string|array ...$columns
     * @return QBSelect
     * @throws QBException
     */
    public function select(array|string ...$columns): self
    {
        $this->checkOrder('select', ['' => ['']]);

        $this->data->select = $columns;

        $this->setState('select');
        $this->setMethod('select');
        return $this;
    }

    /**
     * Sets the table to be selected from.
     * ->from('table', 'alias')
     * ->from(Model::class, 'alias')
     * @param string|Model $table
     * @param string|null $as
     * @return QBSelect
     * @throws QBException
     */
    public function from(string|Model $table, string $as = null): self
    {
        $this->checkOrder('from', [
            'select' => ['select']
        ]);

        $this->data->from = [
            'table' => $table,
            'as' => $as
        ];

        $this->setState('from');
        $this->setMethod('from');
        return $this;
    }

    /**
     * Sets the table to be selected from.
     * ->leftJoin('table', 'alias')
     * ->rightJoin('table', 'alias')
     * ->crossJoin('table', 'alias')
     * ->innerJoin('table', 'alias')
     * ->fullJoin('table', 'alias')
     * @param string $type
     * @param string|Model $table
     * @param string|null $as
     * @return QBSelect
     * @throws QBException
     */
    public function join(string $type, string|Model $table, string $as = null): self
    {
        $this->checkOrder('join', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or']
        ]);

        $where = new QBWhere();
        $where->rawData = true;
        $where->noParentheses = true;

        if (!property_exists($this->data, 'join')) $this->data->join = [];
        $this->data->join[] = [
            'type' => $type,
            'table' => $table,
            'as' => $as,
            'on' => $where
        ];

        $this->setState('join');
        $this->setMethod('join');
        return $this;
    }

    /**
     * Sets the alias of the table.
     * ->as('alias')
     * @param string $alias
     * @return QBSelect
     * @throws QBException
     */
    public function as(string $alias): self
    {
        $this->checkOrder('as', [
            'from' => ['from'],
            'join' => ['join']
        ]);

        if ($this->data->prevState == 'from') {
            $this->data->from['as'] = $alias;
        } elseif ($this->data->prevState == 'join') {
            $this->setDataLast('join', 'as', $alias);
        }

        $this->setMethod('as');
        return $this;
    }

    /**
     * Sets the conditions of the join.
     * ->on('column1 = column2')
     * ->on('column1', 'column2')
     * ->on('column1', 'operator', 'column2')
     * @param mixed $column
     * @param mixed|null $operator_or_value
     * @param mixed|null $value
     * @return QBSelect
     * @throws QBException
     */
    public function on(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('on', [
            'join' => ['join', 'as']
        ]);

        $this->getDataLast('join', 'on')->where(...func_get_args());

        $this->setMethod('on');
        return $this;
    }

    /**
     * Sets the conditions of the WHERE clause.
     * ->where('column = value')
     * ->where('column', 'value')
     * ->where('column', 'operator', 'value')
     * @param mixed $column
     * @param mixed|null $operator_or_value
     * @param mixed|null $value
     * @return QBSelect
     * @throws QBException
     */
    public function where(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('where', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or']
        ]);

        $this->data->where = new QBWhere();
        $this->data->where->noParentheses = true;

        $this->data->where->where(...func_get_args());

        $this->setState('where');
        $this->setMethod('where');
        return $this;
    }

    /**
     * Sets the columns to be grouped by.
     * ->groupBy('column1', 'column2', ...)
     * @param string ...$columns
     * @return QBSelect
     * @throws QBException
     */
    public function groupBy(string ...$columns): self
    {
        $this->checkOrder('groupBy', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or'],
            'where' => ['where', 'and', 'or'],
        ]);

        $this->data->groupBy = $columns;

        $this->setState('groupBy');
        $this->setMethod('groupBy');
        return $this;
    }

    /**
     * Sets the columns to be ordered by.
     * ->orderBy('column1 ASC', 'column2 DESC', ...)
     * @param string ...$columns
     * @return QBSelect
     * @throws QBException
     */
    public function orderBy(string ...$columns): self
    {
        $this->checkOrder('orderBy', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or'],
            'where' => ['where', 'and', 'or'],
            'groupBy' => ['groupBy'],
        ]);

        $this->data->orderBy = $columns;

        $this->setState('orderBy');
        $this->setMethod('orderBy');
        return $this;
    }

    /**
     * Sets the conditions of the HAVING clause.
     * ->having('column1', '>', 10)->and('column2', '<', 20)->or('column3', 30)
     * @param mixed $column
     * @param mixed|null $operator_or_value
     * @param mixed|null $value
     * @return QBSelect
     * @throws QBException
     */
    public function having(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('having', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or'],
            'where' => ['where', 'and', 'or'],
            'groupBy' => ['groupBy'],
            'orderBy' => ['orderBy'],
        ]);

        $this->data->having = new QBWhere();
        $this->data->having->noParentheses = true;

        $this->data->having->where(...func_get_args());

        $this->setState('having');
        $this->setMethod('having');
        return $this;
    }

    /**
     * Sets the number of rows to be returned.
     * ->limit(10)
     * ->limit(10, 20)
     * @param int $limit
     * @param int|null $offset
     * @return QBSelect
     * @throws QBException
     */
    public function limit(int $limit, int $offset = null): self
    {
        $this->checkOrder('limit', [
            'from' => ['from', 'as'],
            'join' => ['on', 'and', 'or'],
            'where' => ['where', 'and', 'or'],
            'groupBy' => ['groupBy'],
            'orderBy' => ['orderBy'],
            'having' => ['having', 'and', 'or'],
        ]);

        $this->data->limit = $limit;

        $this->setState('limit');
        $this->setMethod('limit');

        if ($offset !== null) {
            $this->offset($offset);
        }

        return $this;
    }

    /**
     * Sets the number of rows to be skipped. Can only be used after limit().
     * ->offset(10)
     * @param int $offset
     * @return QBSelect
     * @throws QBException
     */
    public function offset(int $offset): self
    {
        $this->checkOrder('offset', [
            'limit' => ['limit'],
        ]);

        $this->data->offset = $offset;

        $this->setState('offset');
        $this->setMethod('offset');
        return $this;
    }

    /**
     * To get the query string and parameters. If the $withParams parameter is true, the return
     * value will be an object. Otherwise, it will be a string.
     * ->raw() // to get the query string
     * ->raw(true) // to get the query string and parameters
     * Example of return value when $withParams is true:
     *  $obj->query; // The query string
     *  $obj->params; // The parameters as an array
     * @param bool $withParams
     * @return string|\stdClass
     * @throws QBException
     */
    public function raw(bool $withParams = false): string|\stdClass
    {
        return $this->buildQuery($withParams);
    }

    /**
     * Executes the query and returns the PDOStatement object.
     * ->statement()
     * ->statement(__FILE__, __LINE__) // for debugging
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return \PDOStatement|array|bool
     * @throws QBException
     */
    public function statement(string $file = null, string|int $line = null): \PDOStatement|array|bool
    {
        return $this->prepareFetch($file, $line);
    }

    /**
     * Executes the query and returns the first row of the result set. Only it can be used when
     * you are sure that only one row will be returned.
     * ->fetch() // to get the result as an object
     * ->fetch(\PDO""FETCH_ASSOC) // to get the result as an associative array
     * ->fetch(\PDO""FETCH_ASSOC, __FILE__, __LINE__) // for debugging
     * @param int $fetch The fetch style. Default is \PDO::FETCH_OBJ
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return mixed
     * @throws QBException
     */
    public function fetch(int $fetch = \PDO::FETCH_OBJ, string $file = null, string|int $line = null): mixed
    {
        return $this->prepareFetch($file, $line)->fetch($fetch);
    }

    /**
     * Executes the query and returns all rows of the result set.
     * ->fetchAll() // to get the result as an object
     * ->fetchAll(\PDO""FETCH_ASSOC) // to get the result as an associative array
     * ->fetchAll(\PDO""FETCH_ASSOC, __FILE__, __LINE__) // for debugging
     * @param int $fetch The fetch style. Default is \PDO::FETCH_OBJ
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return mixed
     * @throws QBException
     */
    public function fetchAll(int $fetch = \PDO::FETCH_OBJ, string $file = null, string|int $line = null): mixed
    {
        return $this->prepareFetch($file, $line)->fetchAll($fetch);
    }

    /**
     * Builds the query string.
     * @param bool $withParams
     * @return string|\stdClass
     * @throws QBException
     */
    private function buildQuery(bool $withParams = false): string|\stdClass
    {
        $query = "";
        $params = [];
        if (property_exists($this->data, 'select')) {
            $columns = [];
            foreach ($this->data->select as $argument) {
                if (is_array($argument)) {
                    foreach ($argument as $column) {
                        if (gettype($column) == 'string') {
                            $columns[] = $column;
                        } else {
                            [$column, $param] = QB::resolve('columns', $column);
                            $columns[] = $column;
                            $params = array_merge($params, $param ?? []);
                        }
                    }
                } else {
                    $columns[] = $argument;
                }
            }
            $columns = count($columns) > 0 ? implode(', ', $columns) : '*';
            $query .= "SELECT " . $columns;
        }
        if (property_exists($this->data, 'from')) {
            $table = $this->invokeTable($this->data->from['table']);
            $query .= " FROM " . $table;
            if (isset($this->data->from['as'])) {
                $query .= " " . $this->data->from['as'];
            }
        }
        if (property_exists($this->data, 'join')) {
            foreach ($this->data->join as $join) {
                $table = $this->invokeTable($join['table']);
                $query .= " " . $join['type'] . " " . $table;
                if (isset($join['as'])) {
                    $query .= " " . $join['as'];
                }
                if (isset($join['on'])) {
                    [$on, $param] = QB::resolve('where', $join['on']);
                    $params = array_merge($params, $param ?? []);
                    $query .= " ON " . $on;
                }
            }
        }
        if (property_exists($this->data, 'where')) {
            [$where, $param] = QB::resolve('where', $this->data->where);
            $params = array_merge($params, $param ?? []);
            $query .= " WHERE " . $where;
        }
        if (property_exists($this->data, 'groupBy')) {
            $query .= " GROUP BY " . implode(', ', $this->data->groupBy);
        }
        if (property_exists($this->data, 'orderBy')) {
            $query .= " ORDER BY " . implode(', ', $this->data->orderBy);
        }
        if (property_exists($this->data, 'having')) {
            [$having, $param] = QB::resolve('where', $this->data->having);
            $params = array_merge($params, $param ?? []);
            $query .= " HAVING " . $having;
        }
        if (property_exists($this->data, 'limit')) {
            if (property_exists($this->data, 'offset')) {
                $query .= " LIMIT " . $this->data->limit . ", " . $this->data->offset;
            } else {
                $query .= " LIMIT " . $this->data->limit;
            }
        }

        if ($withParams) {
            $obj = new \stdClass();
            $obj->query = $query;
            $obj->params = $params;
            return $obj;
        }
        return $query;
    }

    /**
     * Prepares the query string and the parameters.
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param int|null $line The line number where the method is called. Used for debugging.
     * @return \PDOStatement|array|false
     * @throws QBException
     */
    private function prepareFetch(string $file = null, int $line = null): \PDOStatement|array|false
    {
        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        return QBConnector::query($query, $params, $file, $line);
    }

    /**
     * Sets the actions of the query.
     * @param string $action
     * @param string $key
     * @param mixed $value
     * @return void
     */
    private function setDataLast(string $action, string $key, mixed $value): void
    {
        $count = count($this->data->$action);
        if ($count > 0) {
            $index = $count - 1;
            $this->data->$action[$index][$key] = $value;
        }
    }

    /**
     * Gets the actions of the query.
     * @param string $action
     * @param string $key
     * @return mixed
     */
    private function getDataLast(string $action, string $key): mixed
    {
        $index = count($this->data->$action) - 1;
        if (isset($this->data->$action[$index])) {
            return $this->data->$action[$index][$key];
        }
        return null;
    }
}