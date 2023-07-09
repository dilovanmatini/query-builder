<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

class QBIf
{
    /**
     * @var mixed $condition The condition to be checked.
     */
    public mixed $condition;

    /**
     * @var mixed $success The value to be returned if the condition is true.
     */
    public mixed $success;

    /**
     * @var mixed $failed The value to be returned if the condition is false.
     */
    public mixed $failed;

    /**
     * @var string|null $alias The alias of the value to be returned.
     */
    public ?string $alias = null;

    /**
     * @param mixed $condition The condition to be checked.
     * @param mixed $success The value to be returned if the condition is true.
     * @param mixed $failed The value to be returned if the condition is false.
     * @return self
     */
    public function if(mixed $condition, mixed $success, mixed $failed): self
    {
        $this->condition = $condition;
        $this->success = $success;
        $this->failed = $failed;
        return $this;
    }

    /**
     * @param string $alias The alias of the value to be returned.
     * @return self
     */
    public function as(string $alias): self
    {
        $this->alias = $alias;
        return $this;
    }
}