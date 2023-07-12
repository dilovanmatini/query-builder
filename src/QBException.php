<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

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