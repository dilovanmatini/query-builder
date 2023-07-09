<?php

declare(strict_types = 1);

namespace QueryBuilder\QB;

class QBStatement
{
    /**
     * @param string|Model $table The table to be queried. You can use a model class or a table name.
     * @throws QBException
     * @return string
     */
    public function invokeTable(string|Model $table): string
    {
        if ($table instanceof Model) {
            $table = $table->getTable();
        } elseif (str_contains($table, "\\models\\") && class_exists($table)) {
            $model = new $table();
            if ($model instanceof Model) {
                $table = $model->getTable();
            } else {
                throw new QBException('Invalid model class "' . $table . '"');
            }
        }
        return $table;
    }
}