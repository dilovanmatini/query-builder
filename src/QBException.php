<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

use Exception;

class QBException extends Exception
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
    public function __toString(): string
    {
        return "$this->message\n";
    }
}