<?php
namespace MCL\Resource;
use Closure;
use MCL\Container;
use \Exception;

/**
 * @method ClassDefinition class(string|object|null $classname=null)
 * @method ClassDefinition classIf(string|object|null $classname=null)
 * @method ClassDefinition classShared(string|object|null $classname=null)
 * @method ClassDefinition classSharedIf(string|object|null $classname=null)
 *
 * @method CallableDefinition callable(Closure|callable|array|string $callable)
 * @method CallableDefinition callableIf(Closure|callable|array|string $callable)
 * @method CallableDefinition callableShared(Closure|callable|array|string $callable)
 * @method CallableDefinition callableSharedIf(Closure|callable|array|string $callable)
 *
 * @method AliasDefinition alias(string $aliasOf)
 * @method AliasDefinition aliasIf(string $aliasOf)
 * @method AliasDefinition aliasShared(string $aliasOf)
 * @method AliasDefinition aliasSharedIf(string $aliasOf)
 *
 * @method ValueDefinition value(mixed $value)
 * @method ValueDefinition valueIf(mixed $value)
 *
 * @method Event event(callable|Closure|array|string $callable)
 */
class Source
{
    /**
     * @param Container $container
     * @param Closure|array|string $data
     * @param bool $ifNotExists
     */
    public function __construct(
        protected readonly Container                $container ,
        private readonly Closure|array|string       $data ,
        public readonly bool                        $ifNotExists = false)
    {}




    /**
     * @return array
     * @throws Exception
     */
    public function getData(): array
    {
        static $isLoading = false;
        if($isLoading) return [];
        $isLoading = true;
        $data = [];
        if($this->data){
            if(is_string($this->data))
                $data = require $this->data;
            elseif($this->data instanceof Closure || is_callable($this->data))
                $data = call_user_func($this->data, $this, $this->container);
            elseif(is_array($this->data))
                $data = $this->data;
        }
        $isLoading = false;
        if(!is_array($data))
            throw new Exception('load data failed!');
        return $data;
    }




    public function __call(string $name, array $arguments)
    {
        $obj = null;
        if(str_starts_with($name, 'class'))
            $obj = Definition::createClass($arguments[0]);
        elseif(str_starts_with($name, 'callable'))
            $obj = Definition::createCallable($arguments[0]);
        elseif(str_starts_with($name, 'alias'))
            $obj = Definition::createAlias($arguments[0]);
        elseif(str_starts_with($name, 'value'))
            $obj = Definition::createValue($arguments[0]);
        elseif(str_starts_with($name, 'event'))
            $obj = Event::createEvent($arguments[0]);
        if($obj){
            if(str_contains($name, 'If') || $this->ifNotExists)
                $obj->ifNotExists(true);
            if(str_contains($name, 'Shared'))
                $obj->shared(true);
        }
        return $obj;
    }

}