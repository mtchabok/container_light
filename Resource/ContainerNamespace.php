<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;

class ContainerNamespace
{


    /**
     * @param string $id
     * @return bool
     * @throws Exception
     */
    public function has(string $id): bool
    { return $this->container->has($this->prepareName($id)); }


    /**
     * @param string $id
     * @param mixed $default [optional]
     * @param ?array $parameters [optional]
     * @return mixed
     * @throws Exception
     */
    public function get(string $id, mixed $default=null, ?array $parameters=null) :mixed
    { return $this->container->get($this->prepareName($id), $default, $parameters); }


    /**
     * @param string $class
     * @param ?array $parameters [optional]
     * @return mixed
     * @throws Exception
     */
    public function make(string $class, ?array $parameters=null):mixed
    { return $this->container->makeParamsArray($class, $parameters); }

    /**
     * @param string $class
     * @param ?array $parameters [optional]
     * @return mixed
     * @throws Exception
     */
    public function makeParamsArray(string $class, ?array $parameters=null):mixed
    { return $this->container->makeParamsArray($class, $parameters); }

    /**
     * @param mixed $callable
     * @param mixed ...$parameter [optional]
     * @return mixed
     * @throws Exception
     */
    public function call(mixed $callable, mixed ...$parameter) :mixed
    { return $this->callParamsArray($callable, $parameter); }

    /**
     * @param mixed $callable
     * @param ?array $parameters [optional]
     * @return mixed
     * @throws Exception
     */
    public function callParamsArray(mixed $callable, ?array $parameters=null) :mixed
    {
        if(is_string($callable)){
            if(!$this->has($callable)){
                if(!is_callable($callable))
                    return null;
                $this->container->bind(CallableResource::create($callable)->isAutowired(true), $this->prepareName($callable));
            }
            return $this->get($callable, null, $parameters);
        }
        return $this->container->callParamsArray($callable, $parameters);
    }












    /** @return Container */
    public function getContainer() :Container
    { return $this->container; }

    /** @return string */
    public function getNamespace() :string
    { return $this->namespace; }





    protected function prepareName(string $name) :string
    { return empty($this->namespace) ?$name :(empty($name) ?$this->namespace :"{$this->namespace}.{$name}"); }



    /**
     * @param string $name
     * @return bool
     * @throws Exception
     */
    public function __isset(string $name): bool
    { return !empty($name) && isset($this->container->{$this->prepareName($name)}); }

    /**
     * @param string $name
     * @return mixed
     * @throws Exception
     */
    public function __get(string $name) :mixed
    { return !empty($name) ?$this->container->__get($this->prepareName($name)) :null; }

    public function __set(string $name, $value): void
    { if(!empty($name)) $this->container->{$this->prepareName($name)} = $value; }

    public function __unset(string $name): void
    {}

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws Exception
     */
    public function __call(string $name, array $arguments) :mixed
    { return !empty($name) ?$this->callParamsArray($name, $arguments) :null; }

    public function __construct(protected Container $container, protected string $namespace)
    {}
}