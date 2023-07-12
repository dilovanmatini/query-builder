<?php
/**
 * @package     QueryBuilder
 * @link        https://github.com/dilovanmatini/query-builder
 * @license     MIT License
 */
namespace Database\QueryBuilder;

class QBStatement
{

    /**
     * @var object $data To store the states of the query builder.
     */
    protected object $data;

    public function __construct()
    {
        $this->data = new \stdClass();
        $this->setState('');
        $this->setMethod('');
    }

    /**
     * @param mixed $table The table to be queried. You can use a model class or a table name.
     * @throws QBException
     * @return string
     */
    public function invokeTable(mixed $table): string
    {
        $model_class = static::invokeModel();

        if(!is_null($model_class)){

            if( $table instanceof $model_class){
                $table = $table->getTable();
            }
            elseif(!is_null($table) && class_exists($table)){
                $model = new $table();
                if( $model instanceof $model_class){
                    $table = $model->getTable();
                }
                else{
                    throw new QBException('Invalid model class "' . $table . '"');
                }
            }
        }

        return $table;
    }

    /**
     * Invoke the model class
     * @return string|null
     */
    public function invokeModel(): mixed
    {
        $model_class = QBConfig::get('model_class');

        if (!$model_class && QB::isLaravel()) {
            $model_class = '\Illuminate\Database\Eloquent\Model';
        }

        if (!is_null($model_class) && class_exists($model_class)) {
            return $model_class;
        }
        return null;
    }

    /**
     * To prepare the query.
     * @param \stdClass $raw
     * @return array
     */
    protected function prepareQuery(\stdClass $raw): array
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
    protected function checkOrder(string $state, array $prevStates): void
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

    /**
     * @param string $state - the current state to be checked
     * @return void
     */
    protected function setState(string $state): void
    {
        $this->data->prevState = $state;
    }

    /**
     * @param string $method - the current method to be checked
     * @return void
     */
    protected function setMethod(string $method): void
    {
        $this->data->prevMethod = $method;
    }
}
