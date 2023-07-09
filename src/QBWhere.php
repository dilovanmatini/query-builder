<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

/**
 * @method QBWhere where(mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBWhere and(mixed $column, mixed $operator_or_value = null, mixed $value = null)
 * @method QBWhere or(mixed $column, mixed $operator_or_value = null, mixed $value = null)
 */

class QBWhere
{
    /**
     * @var bool $rawData If true, the values will be used as raw data. They will NOT be placeholders.
     */
    public bool $rawData = false;

    /**
     * @var bool $noParentheses If true, the condition will not be wrapped in parentheses.
     */
    public bool $noParentheses = false;

    /**
     * @var int $id The id of the condition. It will be used for audit purposes especially in QBUpdate and QBDelete.
     */
    public int $id = 0;

    /**
     * @var array $data Conditions will be stored here.
     */
    public array $data = [];

    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, ['where', 'and', 'or'])) {
            $this->data[] = [
                'type' => $method,
                'value' => $arguments
            ];
        }
        return $this;
    }
}