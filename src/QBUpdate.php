<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

/**
 * @method QBSelect and (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBSelect or (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */
class QBUpdate extends QBStatement
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Receives all unknown method calls.
     * @param string $method
     * @param array $arguments
     * @return QBUpdate
     * @throws QBException
     */
    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, ['and', 'or'])) {
            $this->checkOrder($method, [
                'join' => ['on', 'and', 'or'],
                'where' => ['where', 'and', 'or'],
                'having' => ['having', 'and', 'or']
            ]);

            if ($this->data->prevState == 'where') {
                $this->data->where->$method(...$arguments);
            }

            $this->setMethod($method);
        }
        return $this;
    }

    /**
     * Selects the table to update.
     * ->update('table_name')
     * ->update(Model::class)
     * @param string|Model $table The table name or the model class
     * @param string|null $as The alias of the table
     * @return QBUpdate
     * @throws QBException
     */
    public function update(string|Model $table, string $as = null): self
    {
        $this->checkOrder('update', ['' => ['']]);

        $this->data->update = [
            'table' => $table,
            'as' => $as
        ];

        $this->setState('update');
        $this->setMethod('update');
        return $this;
    }

    /**
     * Sets the alias of the table.
     *->as('alias')
     * @param string $alias
     * @return QBUpdate
     * @throws QBException
     */
    public function as(string $alias): self
    {
        $this->checkOrder('as', [
            'update' => ['update'],
        ]);

        if ($this->data->prevState == 'update') {
            $this->data->update['as'] = $alias;
        }

        $this->setMethod('as');
        return $this;
    }

    /**
     * Sets the columns to update.
     * ->set('column_name1 = value1, column_name2 = value2')
     * ->set(['column_name1' => 'value1', 'column_name2' => 'value2'])
     * @param string|array $data
     * @return QBUpdate
     * @throws QBException
     */
    public function set(string|array $data): self
    {
        $this->checkOrder('set', [
            'update' => ['update', 'as']
        ]);

        $this->data->set = $data;

        $this->setState('set');
        $this->setMethod('set');
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
     * @return QBUpdate
     * @throws QBException
     */
    public function where(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('where', [
            'set' => ['set'],
        ]);

        $this->data->where = new QBWhere();
        $this->data->where->noParentheses = true;

        $this->data->where->where(...func_get_args());

        $this->setState('where');
        $this->setMethod('where');
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
     *  $obj->table // The table name
     *  $obj->id // The last updated id
     *  $obj->audit_data // The audit data as an array
     * @param bool $withParams
     * @return string|\stdClass
     * @throws QBException
     */
    public function raw(bool $withParams = false): string|\stdClass
    {
        return $this->buildQuery($withParams);
    }

    /**
     * @param bool $withParams
     * @return string|\stdClass
     * @throws QBException
     */
    public function buildQuery(bool $withParams = false): string|\stdClass
    {
        $query = "";
        $params = [];
        $table = "";
        $affected = false;
        if (property_exists($this->data, 'update')) {
            $table = $this->invokeTable($this->data->update['table']);
            $query .= "UPDATE " . $table;
            if (isset($this->data->update['as'])) {
                $query .= " " . $this->data->update['as'];
            }
        }
        if (property_exists($this->data, 'set')) {
            [$set, $param, $audit_data] = QB::resolve('set', $this->data->set);
            $params = array_merge($params, $param ?? []);
            if ($set != "") {
                $affected = true;
            }
            $query .= " SET " . $set;
        } else {
            throw new QBException("SET clause is required. Ex: ->set(['column' => 'value'])");
        }
        if (property_exists($this->data, 'where')) {
            [$where, $param] = QB::resolve('where', $this->data->where);
            $params = array_merge($params, $param ?? []);
            $query .= " WHERE " . $where;
        } else {
            throw new QBException("WHERE clause is required. If you want to update all rows, use raw SQL query instead.");
        }

        if (!$affected) {
            $query = "";
            $params = [];
        }

        if ($withParams) {
            $obj = new \stdClass();
            $obj->query = $query;
            $obj->params = $params;
            $obj->table = $table;
            $obj->id = $this->data->where->id ?? 0;
            $obj->audit_data = $audit_data;
            return $obj;
        }
        return $query;
    }

    /**
     * Runs the query and returns an stdClass object.
     * ->run()
     * ->run(true) // To audit the query
     * Example of return value:
     * $obj->affectedRows // The number of affected rows
     * $obj->affectedAttributes // The number of affected attributes/columns
     * $obj->raw // The raw query string
     * @param bool|null $audit If true, the query will be audited. If null, the value from QBConfig will be used.
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return \stdClass|null
     * @throws QBException
     */
    public function run(bool $audit = null, string $file = null, string|int $line = null): \stdClass|null
    {
        if (is_null($audit)) {
            $audit = QBConfig::get('audit');
        }

        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        if ($query == "") {
            return null;
        }

        $stmt = QBConnector::query($query, $params, $file, $line);

        $statement = new \stdClass();
        $statement->affectedRows = $stmt->rowCount();
        $statement->affectedAttributes = $raw->audit_data;
        $statement->raw = $query;

        if ($audit && !is_null($raw->id) && $raw->id > 0) {
            QBConfig::auditCallback('update', $raw->table, $raw->id, $raw->audit_data, $file, $line);
        }

        return $statement;
    }

    /**
     * Executes the query and returns an stdClass object.
     * ->execute()
     * ->execute(true) // To audit the query
     * Example of return value:
     * $obj->affectedRows // The number of affected rows
     * $obj->affectedAttributes // The number of affected attributes/columns
     * $obj->raw // The raw query string
     * @param bool|null $audit If true, the query will be audited. If null, the value from QBConfig will be used.
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return \stdClass|null
     * @throws QBException
     */
    public function execute(bool $audit = null, string $file = null, string|int $line = null): \stdClass|null
    {
        return $this->run($audit, $file, $line);
    }
}