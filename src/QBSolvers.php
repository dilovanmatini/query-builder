<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

use stdClass;

trait QBSolvers
{
    protected array $permittedSTDClass = [];

    public function __construct()
    {
        $this->permittedSTDClass['column_alias'] = ['alias'];
        $this->permittedSTDClass['if_column'] = ['now', 'param'];
        $this->permittedSTDClass['second_column'] = ['raw', 'now', 'param', ...QB::$comparisonOperators];
        $this->permittedSTDClass['third_column'] = ['raw', 'now', 'param'];
        $this->permittedSTDClass['value'] = ['now', 'param'];
        $this->permittedSTDClass['insert_value'] = ['now', 'raw'];
    }

    public static function aliasSolver(stdClass $alias): array{
        foreach($alias->columns as &$column){
            $column = $alias->alias.'.'.$column;
        }
        $columns = implode(', ', $alias->columns);
        return [$columns, []];
    }
    /**
     * @throws QBException
     */
    public function rawSolver(stdClass $value): array{
        if( is_string($value->value) ){
            return [$value->value, []];
        }
        throw new QBException('Raw value must be a string');
    }
    public function nowSolver(): array{
        return ['NOW()', []];
    }
    public function paramSolver(stdClass $value): array{
        if(is_null($value->key)){
            $value->key = 'param'.substr(md5((string)rand()), 0, 10);
        }
        return [":$value->key", [$value]];
    }

    /**
     * @throws QBException
     */
    public function equalSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['= '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function notEqualSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['!= '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function greaterThanSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['> '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function lessThanSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['< '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function greaterThanOrEqualSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['>= '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function lessThanOrEqualSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['<= '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function likeSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['LIKE '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function notLikeSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['NOT LIKE '.$value, $params];
    }

    /**
     * @throws QBException
     */
    public function betweenSolver(stdClass $value): array{
        [$value1, $params1] = $this->valueSolver($value->arguments[0] ?? "");
        [$value2, $params2] = $this->valueSolver($value->arguments[1] ?? "");
        return ['BETWEEN '.$value1.' AND '.$value2, array_merge($params1, $params2)];
    }

    /**
     * @throws QBException
     */
    public function notBetweenSolver(stdClass $value): array{
        [$value1, $params1] = $this->valueSolver($value->arguments[0] ?? "");
        [$value2, $params2] = $this->valueSolver($value->arguments[1] ?? "");
        return ['NOT BETWEEN '.$value1.' AND '.$value2, array_merge($params1, $params2)];
    }

    /**
     * @throws QBException
     */
    public function inSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['IN ('.$value.')', $params];
    }

    /**
     * @throws QBException
     */
    public function notInSolver(stdClass $value): array{
        [$value, $params] = $this->valueSolver($value->arguments[0] ?? "");
        return ['NOT IN ('.$value.')', $params];
    }

    public function isNullSolver(): array{
        return ['IS NULL', []];
    }

    public function isNotNullSolver(): array{
        return ['IS NOT NULL', []];
    }

    public function isEmptySolver(): array{
        return ["= ''", []];
    }

    public function isNotEmptySolver(): array{
        return ["!= ''", []];
    }

    /**
     * @throws QBException
     */
    public function valueSolver(mixed $value): array{
        if( $this->isRaw($value) ){
            if(is_string($value)){
                $value = "'$value'";
            }
            return [$value, []];
        }
        elseif( $value instanceof QBSelect ){
            $select = $value->raw(true);
            return [$this->parenthesis($select->query), $select->params];
        }
        elseif ($value instanceof QBIf) {
            return $this->if($value);
        }
        elseif ($value instanceof stdClass) {
            return $this->stdClass('value', $value);
        }
        throw new QBException('Invalid value');
    }

    /**
     * @throws QBException
     */
    public function stdClass(string $method, stdClass $value): array
    {
        $name = $value->name;
        if (in_array($name, $this->permittedSTDClass[$method])) {
            $resolver = $name . 'Solver';
            return $this->$resolver($value);
        }
        throw new QBException('Invalid operation');
    }

    private function parenthesis(string $value): string
    {
        return '(' . $value . ')';
    }

    public function isRaw(mixed $value): bool
    {
        return in_array(gettype($value), ['string', 'integer', 'double']);
    }
}