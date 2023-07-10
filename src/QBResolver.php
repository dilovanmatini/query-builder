<?php

namespace Database\QueryBuilder;

use stdClass;

class QBResolver
{
    use QBSolvers{
        QBSolvers::__construct as private __constructSolvers;
    }

    public function __construct()
    {
        $this->__constructSolvers();
    }

    /**
     * @throws QBException
     */
    public function columns(mixed $value): array
    {
        if (is_string($value)) {
            return [$value, []];
        } elseif ($value instanceof QBAggregate) {
            return $this->aggregate($value);
        } elseif ($value instanceof QBIf) {
            return $this->if($value);
        } elseif ($value instanceof stdClass) {
            return $this->stdClass('column_alias', $value);
        }
        throw new QBException('Invalid column value');
    }

    public function aggregate(QBAggregate $aggregate): array
    {
        $name = $aggregate->name;
        $value = $aggregate->value;
        $alias = $aggregate->alias;

        $aggregate = strtoupper($name) . '(' . $value . ')';

        if ($name == 'distinct') {
            $aggregate = 'DISTINCT ' . $value;
        }

        if ($alias) {
            $aggregate .= ' AS ' . $alias;
        }

        return [$aggregate, []];
    }

    /**
     * @throws QBException
     */
    public function if(QBIf $if): array
    {
        $params = [];
        if ($this->isRaw($if->condition)) {
            $condition = $if->condition;
        } else {
            [$condition, $param] = $this->condition($if->condition);
            $params = $this->addParam($params, $param);
        }

        if ($this->isRaw($if->success)) {
            $success = $if->success;
        } else {
            [$success, $param] = $this->ifColumn($if->success);
            $params = $this->addParam($params, $param);
        }

        if ($this->isRaw($if->failed)) {
            $failed = $if->failed;
        } else {
            [$failed, $param] = $this->ifColumn($if->failed);
            $params = $this->addParam($params, $param);
        }

        $text = "IF($condition, $success, $failed)";

        if (!is_null($if->alias)) {
            $text .= ' AS ' . $if->alias;
        }

        return [$text, $params];
    }

    /**
     * @throws QBException
     */
    public function ifColumn(mixed $value): array
    {
        if ($this->isRaw($value)) {
            return [$value, []];
        } elseif ($value instanceof QBSelect) {
            $select = $value->raw(true);
            return [$this->parenthesis($select->query), $select->params];
        } elseif ($value instanceof QBAggregate) {
            return $this->aggregate($value);
        } elseif ($value instanceof stdClass) {
            return $this->stdClass('if_column', $value);
        }
        throw new QBException('Invalid if values');
    }

    /**
     * @throws QBException
     */
    public function condition(mixed $value): array
    {
        if ($value instanceof QBWhere) {
            return $this->where($value);
        }
        throw new QBException('Invalid condition');
    }

    /**
     * @throws QBException
     */
    public function where(QBWhere $where): array
    {
        $params = [];
        $data = $where->data;
        $rawData = $where->rawData;
        $noParentheses = $where->noParentheses;
        if (count($data) > 0) {
            $operations = [];
            foreach ($data as $value) {
                $key = $value['type'];
                $value = $value['value'];
                if ($key != 'where') {
                    $operations[] = strtoupper($key);
                }
                [$operation, $param, $id] = $this->operation($value, $rawData);
                $operations[] = $operation;
                $params = $this->addParam($params, $param);
                if( $id > 0 ){
                    $where->id = $id;
                }
            }
            $operations = implode(' ', $operations);
            return [$noParentheses ? $operations : $this->parenthesis($operations), $params];
        }
        throw new QBException('Invalid where condition');
    }

    /**
     * Single operation in where condition. Ex: ('id = 1'), ('id', 1), or ('id', '=', 1)
     * @throws QBException
     */
    public function operation(mixed $value, bool $rawData = false): array
    {
        $column = $value[0] ?? null;
        $operator_or_value = $value[1] ?? null;
        $value = $value[2] ?? null;

        $params = [];
        if (is_null($value)) {
            if (is_null($operator_or_value)) {
                return $this->comparisonColumn($column);
            } else {
                [$arg1, $param] = $this->singleColumn($column);
                $params = $this->addParam($params, $param);
                [$arg2, $param, $id] = array_pad($this->secondColumn($operator_or_value, $rawData), 3, null);
                $params = $this->addParam($params, $param);
                if( $arg1 != 'id' ){
                    $id = null;
                }
                return ["$arg1 $arg2", $params, $id];
            }
        } else {
            [$arg1, $param] = $this->singleColumn($column);
            $params = $this->addParam($params, $param);
            $operator_or_value = $this->checkOperator($operator_or_value);
            [$arg2, $param, $id] = array_pad($this->thirdColumn($value, $rawData), 3, null);
            $params = $this->addParam($params, $param);
            if( $arg1 != 'id' ){
                $id = null;
            }
            return ["$arg1 $operator_or_value $arg2", $params, $id];
        }
    }

    /**
     * The first column in where condition when it has only one argument. Ex 'id', QBSelect, or QBIf
     * @throws QBException
     */
    public function comparisonColumn(mixed $value): array
    {
        if (is_string($value)) {
            return [$value, []];
        } elseif ($value instanceof QBSelect) {
            $select = $value->raw(true);
            return [$this->parenthesis($select->query), $select->params];
        } elseif ($value instanceof QBWhere) {
            return $this->where($value);
        } elseif ($value instanceof QBIf) {
            return $this->if($value);
        }
        throw new QBException('Invalid comparison column');
    }

    /**
     * The first column in where condition when it has two or three arguments
     * @throws QBException
     */
    public function singleColumn(mixed $value): array
    {
        if (gettype($value) == 'string') {
            return [$value, []];
        } elseif ($value instanceof QBSelect) {
            $select = $value->raw(true);
            return [$this->parenthesis($select->query), $select->params];
        } elseif ($value instanceof QBIf) {
            return $this->if($value);
        }
        throw new QBException('Invalid column');
    }

    /**
     * The second column in where condition when it has only two arguments
     * @throws QBException
     */
    public function secondColumn(mixed $value, bool $rawData = false): array
    {
        if ($this->isRaw($value)) {
            $pure_value = $value;
            if(!$rawData && is_string($value)){
                $value = QB::param($value);
                [$value, $params] = $this->paramSolver($value);
                return ["= " . $value, $params, $pure_value];
            }
            return ["= " . $value, [], $pure_value];
        } elseif ($value instanceof QBSelect) {
            $select = $value->raw(true);
            return ["= " . $this->parenthesis($select->query), $select->params];
        } elseif ($value instanceof QBIf) {
            [$if, $params] = $this->if($value);
            return ["= " . $if, $params];
        } elseif ($value instanceof stdClass) {
            if(!in_array($value->name, QB::$relationalOperators)){
                [$helper, $params] = $this->stdClass('second_column', $value);
                return ["= " . $helper, $params];
            }
            return $this->stdClass('second_column', $value);
        }
        throw new QBException('Invalid column value');
    }

    /**
     * @throws QBException
     */
    public function thirdColumn(mixed $value, bool $rawData = false): array
    {
        if ($this->isRaw($value)) {
            $pure_value = $value;
            if(!$rawData && is_string($value)){
                $value = QB::param($value);
                return array_pad($this->paramSolver($value), 3, $pure_value);
            }
            return [$value, [], $pure_value];
        } elseif ($value instanceof QBSelect) {
            $select = $value->raw(true);
            return [$this->parenthesis($select->query), $select->params];
        } elseif ($value instanceof QBIf) {
            return $this->if($value);
        } elseif ($value instanceof stdClass) {
            return $this->stdClass('third_column', $value);
        }
        throw new QBException('Invalid column value');
    }

    /**
     * @throws QBException
     */
    public function set(string|array $data): array
    {
        if(is_string($data)){
            return [$data, [], []];
        }
        elseif(is_array($data)){
            $fields = [];
            $audit_data = [];
            $params = [];
            foreach ($data as $key => $item) {
                if (empty($key)) {
                    continue;
                }

                if(is_array($item)){
                    $old_value = $item[0] ?? null;
                    $new_value = $item[1] ?? null;
                    $allow = boolval($item[2] ?? true);
                    $check = $item[3] ?? [];
                    $raw = boolval($item[4] ?? false);
                }
                else{
                    $old_value = null;
                    $new_value = $item;
                    $allow = true;
                    $check = [];
                    $raw = false;
                }

                // if the new value is a function, call it
                if (is_callable($new_value)) {
                    $new_value = $new_value($key, $old_value, $allow, $check, $raw);
                }

                // skip this row if $allow is false or the old value is the same as the new value
                if (!$allow || $old_value === $new_value) {
                    continue;
                }

                if (count($check) > 0) {
                    $check_defined = true;
                    foreach ($check as $val) {
                        if(!isset($fields[$val])){
                            $check_defined = false;
                            break;
                        }
                    }
                    if(!$check_defined){
                        continue;
                    }
                }

                if ($raw) {
                    $value = $new_value;
                }else{
                    $param = QB::param($new_value, $key);
                    [$value, $param] = $this->paramSolver($param);
                    $params = array_merge($params, $param);
                }

                $fields[$key] = $key . ' = ' . $value;
                $audit_data[$key] = [$new_value, $old_value];
            }

            if (count($fields) == 0) {
                return ["", [], []];
            }

            return [implode(", ", $fields), $params, $audit_data];
        }
        throw new QBException('Invalid set value');
    }

    /**
     * @throws QBException
     */
    public function insertValues(string|array $data, array|string|null $columns): array
    {
        if(is_string($data)){
            if(!is_null($columns) && !is_string($columns)){
                throw new QBException('Invalid insert columns. Use columns as string.');
            }
            return [$columns, $data, []];
        }
        elseif(is_array($data)){
            if(!is_null($columns)){
                if(!is_array($columns)){
                    throw new QBException('Invalid insert columns. Use array of strings as columns.');
                }
                $columns = array_values($columns);
            }

            $fields = [];
            $values = [];
            $params = [];
            $index = 0;
            foreach ($data as $field => $item) {
                if (!is_null($columns) && !empty($field)) {
                    $columns[] = $field;
                }

                if (!is_null($columns)){
                    $field = $columns[$index] ?? "";
                }
                $index++;

                if (empty($field)) {
                    continue;
                }

                if(is_array($item)){
                    $value = $item[0] ?? "";
                    $allow = boolval($item[1] ?? true);
                    $check = $item[2] ?? [];
                    $raw = boolval($item[3] ?? false);
                }
                else{
                    $value = $item;
                    $allow = true;
                    $check = [];
                    $raw = false;
                }

                // if the value is a function, call it
                if (is_callable($value)) {
                    $value = $value($field, $allow, $check, $raw);
                }

                // skip this row if $allow is false
                if(!$allow){
                    continue;
                }

                // check the dependencies are set or not. if not, skip this row
                if(count($check) > 0){
                    $check_defined = true;
                    foreach ($check as $val) {
                        if(!isset($fields[$val])){
                            $check_defined = false;
                            break;
                        }
                    }
                    if(!$check_defined){
                        continue;
                    }
                }

                if (!$raw) {
                    $param = QB::param($value, $field);
                    [$value, $param] = $this->paramSolver($param);
                    $params = array_merge($params, $param);
                }

                $fields[] = $field;
                $values[] = $value;
            }
            if (count($fields) == 0) {
                return ["", "", []];
            }

            return [implode(", ", $fields), implode(", ", $values), $params];
        }
        throw new QBException('Invalid insert values');
    }

    /**
     * @throws QBException
     */
    public function checkOperator(string $value): string
    {
        $value = strtoupper($value);
        if (in_array($value, QB::$relationalPureOperators)) {
            return $value;
        }
        throw new QBException('Invalid operator');
    }

    public function addParam(array $params, array|null $param = null): array
    {
        return array_merge($params, $param ?? []);
    }
}