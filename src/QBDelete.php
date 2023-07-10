<?php

namespace Database\QueryBuilder;

use stdClass;

/**
 * @method QBSelect and (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBSelect or (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */
class QBDelete extends QBStatement
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

    public function raw($withParams = false, bool $softDelete = null): string|stdClass
    {
        return $this->buildQuery($withParams, $softDelete);
    }

    /**
     * @throws QBException
     */
    public function buildQuery($withParams = false, bool $softDelete = null): string|stdClass
    {
        if(is_null($softDelete)){
            $softDelete = QB::SOFT_DELETE;
        }

        $query = "";
        $params = [];
        $table = "";
        if (property_exists($this->data, 'delete')) {
            $table = $this->invokeTable($this->data->delete['table']);
            if( $softDelete ){
                $query .= "UPDATE " . $table;
                if (isset($this->data->delete['as'])) {
                    $query .= " " . $this->data->delete['as'];
                }
                $query .= " SET deleted_at = '".QBConnector::getConfig('timestamp')."'";
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
            throw new QBException("WHERE clause is required. If you want to delete all rows, use DB::query with raw SQL instead.");
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

    public function run($softDelete = null, $audit = null, string $file = null, int $line = null): stdClass|null
    {
        if(is_null($audit)){
            $audit = QB::AUDIT;
        }

        $raw = $this->raw(true, $softDelete);

        [$query, $params] = $this->prepareQuery($raw);

        if($query == ""){
            return null;
        }

        $stmt = QBConnector::query($query, $params, $file, $line);

        $statement = new stdClass();
        $statement->affectedRows = $stmt->rowCount();
        $statement->raw = $query;

        if ($audit && $raw->id > 0) {
            QBConnector::auditCallback('delete', $raw->table, $raw->id, file: $file, line: $line);
        }

        return $statement;
    }

    public function execute($softDelete = null, $audit = null, string $file = null, string|int $line = null): stdClass|null
    {
        return $this->run($softDelete, $audit, $file, $line);
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