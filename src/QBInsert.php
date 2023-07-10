<?php

namespace Database\QueryBuilder;

use stdClass;

class QBInsert extends QBStatement
{
    private object $data;

    public function __construct()
    {
        $this->data = new stdClass();
        $this->setState('');
        $this->setMethod('');
    }

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
        $table = "";
        if (property_exists($this->data, 'insert')) {
            $table = $this->invokeTable($this->data->insert['table']);
            $query .= "INSERT INTO " . $table;
        }
        if (property_exists($this->data, 'values')) {
            [$columns, $values, $param] = QB::resolve('insertValues', $this->data->values, $this->data?->columns ?? null);
            $params = array_merge($params, $param ?? []);

            $query .= " ($columns) VALUES ($values)";
        }
        else{
            throw new QBException("VALUES clause is required. Ex: ->values(['column' => 'value'])");
        }

        if ($withParams) {
            $obj = new stdClass();
            $obj->query = $query;
            $obj->params = $params;
            $obj->table = $table;
            return $obj;
        }
        return $query;
    }

    public function run($audit = null, $lastInsertId = true, string $file = '', string|int $line = ''): stdClass|null
    {
        if(is_null($audit)){
            $audit = QB::AUDIT;
        }

        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        if($query == ""){
            return null;
        }

        $stmt = QBConnector::query($query, $params, $file, $line);

        $statement = new \stdClass();
        $statement->rowCount = $stmt->rowCount();
        $statement->raw = $query;

        if ($lastInsertId || $audit) {
            $conn = QBConnector::getConnection();
            $statement->lastInsertId = intval($conn->lastInsertId());
            if($audit){
                QBConnector::auditCallback('insert', $raw->table, $statement->lastInsertId, file: $file, line: $line);
            }
        }

        return $statement;
    }

    public function execute($audit = null, $lastInsertId = true, string $file = '', string|int $line = ''): stdClass|null
    {
        return $this->run($audit, $lastInsertId, $file, $line);
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
}