<?php

namespace Database\QueryBuilder;

class QBStatement
{
    /**
     * @param string|Model $table The table to be queried. You can use a model class or a table name.
     * @throws QBException
     * @return string
     */
    public function invokeTable(mixed $table): string
    {
        $model_class = static::invokeModel();

        if( $table instanceof $model_class){
            $table = $table->getTable();
        }
        elseif(class_exists($table)){
            $model = new $table();
            if( $model instanceof $model_class){
                $table = $model->getTable();
            }
            else{
                throw new QBException('Invalid model class "' . $table . '"');
            }
        }

        return $table;
    }

    public function invokeModel(): mixed
    {
        $model_class = QBConnector::getConfig('model_class');

        if (!$model_class && QB::isLaravel()) {
            $model_class = '\Illuminate\Database\Eloquent\Model';
        }

        if (class_exists($model_class)) {
            return $model_class;
        }
        return null;
    }
}
