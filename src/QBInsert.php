<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

class QBInsert extends QBStatement
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Selects the table to insert data into.
     * ->insert('table_name')
     * ->insert(Model::class)
     * @param string|Model $table
     * @return QBInsert
     * @throws QBException
     */
    public function insert(string|Model $table): self
    {
        $this->checkOrder('insert', ['' => ['']]);

        $this->data->insert = [
            'table' => $table,
        ];

        $this->setState('insert');
        $this->setMethod('insert');
        return $this;
    }

    /**
     * Sets the columns to insert data into.
     * ->columns('column1, column2, column3')
     * ->columns(['column1', 'column2', 'column3'])
     * @param string|array $data
     * @return QBInsert
     * @throws QBException
     */
    public function columns(string|array $data): self
    {
        $this->checkOrder('columns', [
            'insert' => ['insert']
        ]);

        $this->data->columns = $data;

        $this->setState('columns');
        $this->setMethod('columns');
        return $this;
    }

    /**
     * Sets the values to insert into the columns.
     * ->values('value1, value2, value3')
     * ->values(['column1' => 'value1', 'column2' => 'value2', 'column3' => 'value3'])
     * @param string|array $data
     * @return QBInsert
     * @throws QBException
     */
    public function values(string|array $data): self
    {
        $this->checkOrder('columns', [
            'insert' => ['insert', 'as'],
            'columns' => ['columns']
        ]);

        $this->data->values = $data;

        $this->setState('values');
        $this->setMethod('values');
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
        if (property_exists($this->data, 'insert')) {
            $table = $this->invokeTable($this->data->insert['table']);
            $query .= "INSERT INTO " . $table;
        }
        if (property_exists($this->data, 'values')) {
            [$columns, $values, $param] = QB::resolve('insertValues', $this->data->values, $this->data?->columns ?? null, $this);
            $params = array_merge($params, $param ?? []);

            if(empty($columns) || empty($values)){
                $query = "";
            }
            else{
                $query .= " ($columns) VALUES ($values)";
            }
        }
        else{
            throw new QBException("VALUES clause is required. Ex: ->values(['column' => 'value'])");
        }

        if ($withParams) {
            $obj = new \stdClass();
            $obj->query = $query;
            $obj->params = $params;
            $obj->table = $table;
            return $obj;
        }
        return $query;
    }

    /**
     * Runs the query and returns an stdClass object.
     * ->run()
     * ->run(false) // To disable getting last insert id
     * Example of return value:
     * $obj->rowCount; // The number of rows affected.
     * $obj->raw // The raw query string.
     * $obj->lastInsertId // The last insert id. Only available if the $lastInsertId parameter is true.
     * @param bool $lastInsertId If true, the last insert id will be returned.
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return \stdClass|null
     * @throws QBException
     */
    public function run(bool $lastInsertId = true, string $file = null, string|int $line = null): \stdClass|null
    {
        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        if($query == ""){
            return null;
        }

        $stmt = QBConnector::query($query, $params, $file, $line);

        $statement = new \stdClass();
        $statement->rowCount = $stmt->rowCount();
        $statement->raw = $query;

        if ($lastInsertId) {
            $conn = QBConnector::getConnection();
            $statement->lastInsertId = intval($conn->lastInsertId());

            $audit_callback = $this->config('audit_callback');
            if(is_callable($audit_callback)){
                $audit_callback('insert', $raw->table, $statement->lastInsertId, null);
            }
        }

        return $statement;
    }

    /**
     * Executes the query and returns an stdClass object.
     * ->execute()
     * ->execute(false) // To disable getting last insert id
     * Example of return value:
     * $obj->rowCount; // The number of rows affected.
     * $obj->raw // The raw query string.
     * $obj->lastInsertId // The last insert id. Only available if the $lastInsertId parameter is true.
     * @param bool $lastInsertId If true, the last insert id will be returned.
     * @param string|null $file The file name where the method is called. Used for debugging.
     * @param string|int|null $line The line number where the method is called. Used for debugging.
     * @return \stdClass|null
     * @throws QBException
     */
    public function execute(bool $lastInsertId = true, string $file = null, string|int $line = null): \stdClass|null
    {
        return $this->run($lastInsertId, $file, $line);
    }
}