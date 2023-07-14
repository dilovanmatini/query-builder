<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

use stdClass;

/**
 * @method QBSelect and (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBSelect or (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */
class QBDelete extends QBStatement
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Receives all unknown method calls.
     * @param string $method
     * @param array $arguments
     * @return QBDelete
     * @throws QBException
     */
    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, ['and', 'or'])) {
            $this->checkOrder($method, [
                'where' => ['where', 'and', 'or'],
            ]);

            if ($this->data->prevState == 'where') {
                $this->data->where->$method(...$arguments);
            }

            $this->setMethod($method);
        }
        return $this;
    }

    /**
     * Selects the table to delete from.
     * ->delete('table_name')
     * ->delete(Model::class)
     * @param string|Model $table
     * @param string|null $as
     * @return QBDelete
     * @throws QBException
     */
    public function delete(string|Model $table, string $as = null): self
    {
        $this->checkOrder('delete', ['' => ['']]);

        $this->data->delete = [
            'table' => $table,
            'as' => $as
        ];

        $this->setState('delete');
        $this->setMethod('delete');
        return $this;
    }

    /**
     * Sets the alias of the table.
     * ->as('alias')
     * @param string $alias
     * @return QBDelete
     * @throws QBException
     */
    public function as(string $alias): self
    {
        $this->checkOrder('as', [
            'delete' => ['delete'],
        ]);

        if ($this->data->prevState == 'delete') {
            $this->data->delete['as'] = $alias;
        }

        $this->setMethod('as');
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
     * @return QBDelete
     * @throws QBException
     */
    public function where(mixed $column, mixed $operator_or_value = null, mixed $value = null): self
    {
        $this->checkOrder('where', [
            'delete' => ['delete', 'as'],
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
     *  $obj->table; // The table name
     *  $obj->id; // The id of the deleted row
     * @param bool $withParams
     * @return string|stdClass
     * @throws QBException
     */
    public function raw(bool $withParams = false): string|stdClass
    {
        return $this->buildQuery($withParams);
    }

    /**
     * @param bool $withParams If true, returns an object. Otherwise, returns a string.
     * @return string|stdClass
     * @throws QBException
     */
    public function buildQuery(bool $withParams = false): string|stdClass
    {
        $query = "";
        $params = [];
        $table = "";
        $execution = true;
        if (property_exists($this->data, 'delete')) {
            $table = $this->invokeTable($this->data->delete['table']);
            $deleted_callback = $this->config('deleted_callback');
            if( is_callable($deleted_callback) ){
                $data = $deleted_callback();
                $fields = [];
                foreach ($data as $key => $value) {
                    if($key != "" && $value != ""){
                        $fields[] = "$key = '$value'";
                    }
                }
                $fields = implode(", ", $fields);

                if(!empty($fields)){
                    $query .= "UPDATE " . $table;
                    if (isset($this->data->delete['as'])) {
                        $query .= " " . $this->data->delete['as'];
                    }
                    $query .= " SET $fields";
                }
                else{
                    $execution = false;
                }
            }
            else{
                $query .= "DELETE FROM " . $table;
                if (isset($this->data->delete['as'])) {
                    $query .= " " . $this->data->delete['as'];
                }
            }
        }
        if (property_exists($this->data, 'where')) {
            [$where, $param] = QB::resolve('where', $this->data->where);
            $params = array_merge($params, $param ?? []);
            $query .= " WHERE " . $where;
        }
        else{
            throw new QBException("WHERE clause is required. If you want to delete all rows, use raw SQL query instead.");
        }

        if(!$execution){
            $query = "";
        }

        if ($withParams) {
            $obj = new stdClass();
            $obj->query = $query;
            $obj->params = $params;
            $obj->table = $table;
            $obj->id = $this->data->where->id ?? 0;
            return $obj;
        }
        return $query;
    }

    /**
     * Runs the query and returns an stdClass object.
     * ->run()
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return stdClass|null
     * @throws QBException
     */
    public function run(string $file = null, string|int $line = null): stdClass|null
    {
        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        if($query == ""){
            return null;
        }

        $stmt = QBConnector::query($query, $params, $file, $line);

        $statement = new stdClass();
        $statement->affectedRows = $stmt->rowCount();
        $statement->raw = $query;

        if ($raw->id > 0) {
            $audit_callback = $this->config('audit_callback');
            if(is_callable($audit_callback)){
                $audit_callback('delete', $raw->table, $raw->id, null);
            }
        }

        return $statement;
    }

    /**
     * Executes the query and returns an stdClass object.
     * ->execute()
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return stdClass|null
     * @throws QBException
     */
    public function execute(string $file = null, string|int $line = null): stdClass|null
    {
        return $this->run($file, $line);
    }
}