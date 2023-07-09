<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

use stdClass;

/**
 * @method QBSelect and (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBSelect or (mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */
class QBUpdate extends QBStatement
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
            if($set != ""){
                $affected = true;
            }
            $query .= " SET " . $set;
        }
        else{
            throw new QBException("SET clause is required. Ex: ->set(['column' => 'value'])");
        }
        if (property_exists($this->data, 'where')) {
            [$where, $param] = QB::resolve('where', $this->data->where);
            $params = array_merge($params, $param ?? []);
            $query .= " WHERE " . $where;
        }
        else{
            throw new QBException("WHERE clause is required. If you want to update all rows, use DB::query with raw SQL instead.");
        }

        if(!$affected){
            $query = "";
            $params = [];
        }

        if ($withParams) {
            $obj = new stdClass();
            $obj->query = $query;
            $obj->params = $params;
            $obj->table = $table;
            $obj->id = $this->data->where->id ?? 0;
            $obj->audit_data = $audit_data;
            return $obj;
        }
        return $query;
    }

    public function run($audit = null, string $file = null, int $line = null): stdClass|null
    {
        if(is_null($audit)){
            $audit = QB::AUDIT;
        }

        if (is_null($file) || is_null($line)) {
            $trace = debug_backtrace();
            $file = $trace[0]['file'] ?? null;
            $line = $trace[0]['line'] ?? null;
        }

        $raw = $this->raw(true);

        [$query, $params] = $this->prepareQuery($raw);

        if($query == ""){
            return null;
        }

        $sql = DB()->execute($query, $params, $file, $line);

        if(!$sql){
            return null;
        }

        $statement = new stdClass();
        $statement->affectedRows = $sql->rowCount();
        $statement->affectedAttributes = $raw->audit_data;
        $statement->raw = $query;

        if ($audit && !is_null($raw->id) && $raw->id > 0) {
            save_actions('edit', $raw->table, $raw->id, $raw->audit_data, [], $file, $line);
        }

        return $statement;
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