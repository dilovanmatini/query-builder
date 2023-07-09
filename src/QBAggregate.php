<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

class QBAggregate
{
    /**
     * @var string $name The name of the aggregation function.
     */
    public string $name;

    /**
     * @var string $value The value to be aggregated.
     */
    public string $value;

    /**
     * @var string|null $alias The alias of the aggregated value.
     */
    public ?string $alias;

    /**
     * @throws QBException
     */
    public function __call(string $method, array $arguments): self
    {
        if (in_array($method, ['count', 'sum', 'min', 'max', 'avg', 'distinct'])) {
            return $this->aggregate($method, ...$arguments);
        }
        throw new QBException('Invalid aggregation method "' . $method . '"');
    }

    /**
     * @param string $name The name of the aggregation function.
     * @param string $value The value to be aggregated.
     * @param string|null $alias The alias of the aggregated value.
     * @return self
     */
    public function aggregate(string $name, string $value, string $alias = null): self
    {
        $this->name = $name;
        $this->value = $value;
        $this->alias = $alias;
        return $this;
    }

    /**
     * @param string $alias The alias of the aggregated value.
     * @return self
     */
    public function as(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
}