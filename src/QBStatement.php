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
        $this->data->config = QBConfig::getQueryConfig();
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
                $this->setModelConfig($table);
                $table = $table->getTable();
            }
            elseif(!is_null($table) && class_exists($table)){
                $obj = new $table();
                if( $obj instanceof $model_class){
                    $this->setModelConfig($obj);
                    $table = $obj->getTable();
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
        $model_class = $this->config('model_class');

        if (!$model_class && QB::isLaravel()) {
            $model_class = '\Illuminate\Database\Eloquent\Model';
        }

        if (!is_null($model_class) && class_exists($model_class)) {
            return $model_class;
        }
        return null;
    }

    /**
     * To set the model configuration.
     * @param mixed $model The model instance
     * @return void
     */
    protected function setModelConfig(mixed $model): void
    {
        $config = [];
        if( QB::isLaravel() ){
            // Laravel...
        }
        else{
            if( method_exists($model, 'createdCallback') ){
                $config['created_callback'] = $this->prepareCallback($model, 'createdCallback');
            }
            if( method_exists($model, 'updatedCallback') ){
                $config['updated_callback'] = $this->prepareCallback($model, 'updatedCallback');
            }
            if( method_exists($model, 'deletedCallback') ){
                $config['deleted_callback'] = $this->prepareCallback($model, 'deletedCallback');
            }
            if ( method_exists($model, 'auditCallback') ){
                $config['audit_callback'] = function($action, $table, $id, $data) use ($model){
                    $model->auditCallback($action, $table, $id, $data);
                };
            }
        }

        $this->config(array_filter($config, function($value){
            return !is_null($value);
        }));

        $this->config($config);
    }

    private function prepareCallback(mixed $model, string $name){
        return function() use ($model, $name){
            $result = $model->$name();
            if( is_array($result) ){
                return $result;
            }
            return [];
        };
    }

    /**
     * To get the value, you need to pass the name of the configuration of this query.
     * To override the value, you need to pass an array of configurations for this query only.
     * It should be called before the fetch(), fetchAll(), statement(), run(), and execute() methods.
     * @param string|array $name_or_array
     * @return mixed
     */
    public function config(string|array $name_or_array): mixed {
        if(is_array($name_or_array)){
            $this->data->config = array_merge($this->data->config, $name_or_array);
        }
        else{
            return $this->data->config[$name_or_array] ?? null;
        }
        return $this;
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
