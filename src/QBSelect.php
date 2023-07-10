<?php

namespace Database\QueryBuilder;

use PDO;
use PDOStatement;
use stdClass;

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
    private object $data;

    public function __construct()
    {
        $this->data = new stdClass();
        $this->setState('');
        $this->setMethod('');
    }

    /**
     * @throws QBException
     */
    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, array_keys(QB::$joinMethods))) {
            $this->join(QB::$joinMethods[$method], ...$arguments);
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

    public function select(array|string $columns = '*'): self
    {
        $this->checkOrder('select', ['' => ['']]);

        $this->data->select = $columns;

        $this->setState('select');
        $this->setMethod('select');
        return $this;
    }

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

    public function on(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('on', [
            'join' => ['join', 'as']
        ]);

        $this->getDataLast('join', 'on')->where(...func_get_args());

        $this->setMethod('on');
        return $this;
    }

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

    public function limit(int $limit): self
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
        return $this;
    }

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

    public function raw($withParams = false): string|stdClass
    {
        return $this->buildQuery($withParams);
    }

    /**
     * @throws QBException
     */
    public function buildQuery($withParams = false): string|stdClass
    {
        $query = "";
        $params = [];
        if (property_exists($this->data, 'select')) {
            $columns = [];
            if (is_array($this->data->select)) {
                foreach ($this->data->select as $column) {
                    if (gettype($column) == 'string') {
                        $columns[] = $column;
                    } else {
                        [$column, $param] = QB::resolve('columns', $column);
                        $columns[] = $column;
                        $params = array_merge($params, $param ?? []);
                    }
                }
                $query .= "SELECT " . implode(', ', $columns);
            } else {
                $query .= "SELECT " . $this->data->select;
            }
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
            $obj = new stdClass();
            $obj->query = $query;
            $obj->params = $params;
            return $obj;
        }
        return $query;
    }

    public function statement(string $file = null, int $line = null): mixed
    {
        return $this->prepareFetch($file, $line);
    }

    public function fetch(int $fetch = PDO::FETCH_OBJ, string $file = null, int $line = null): mixed
    {
        return $this->prepareFetch($file, $line)->fetch($fetch);
    }

    public function fetchAll(int $fetch = PDO::FETCH_OBJ, string $file = null, int $line = null): mixed
    {
        return $this->prepareFetch($file, $line)->fetchAll($fetch);
    }

    /**
     * @throws QBException
     */
    private function prepareFetch(string $file = null, int $line = null): PDOStatement|array|false
    {
        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        return QBConnector::query($query, $params, $file, $line);
    }

    private function prepareQuery(stdClass $raw): array
    {
        $prepared_params = [];
        foreach ($raw->params as $obj) {
            while (array_key_exists($obj->key, $prepared_params)) {
                $old_key = $obj->key;
                $obj->key = 'param' . substr(md5((string)rand()), 0, 10);
                $raw->query = str_replace($old_key, $obj->key, $raw->query);
            }
            $prepared_params[$obj->key] = $obj->value;
        }
        return [$raw->query, $prepared_params];
    }

    /**
     * @param string $state - the current state to be checked
     * @param array $prevStates - array of previous states and methods to guarantee the order of states and methods
     * @return void
     * @throws QBException
     */
    private function checkOrder(string $state, array $prevStates): void
    {
        foreach ($prevStates as $key => $value) {
            if ($this->data->prevState == $key && in_array($this->data->prevMethod, $value)) {
                return;
            }
        }

        if (!in_array($this->data->prevState, array_keys($prevStates))) {
            $afterMethod = $this->data->prevState;
        } else {
            $afterMethod = $this->data->prevMethod;
        }

        if ($afterMethod != '') {
            throw new QBException("Invalid order of actions: " . strtoupper($state) . " after " . strtoupper($afterMethod));
        }
    }

    private function setState(string $state): void
    {
        $this->data->prevState = $state;
    }

    private function setMethod(string $method): void
    {
        $this->data->prevMethod = $method;
    }

    private function setDataLast(string $action, string $key, mixed $value): void
    {
        $count = count($this->data->$action);
        if ($count > 0) {
            $index = $count - 1;
            $this->data->$action[$index][$key] = $value;
        }
    }

    private function getDataLast(string $action, string $key): mixed
    {
        $index = count($this->data->$action) - 1;
        if (isset($this->data->$action[$index])) {
            return $this->data->$action[$index][$key];
        }
        return null;
    }
}