<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;
use ReflectionException;

abstract class Resources
{
    /** @var bool */
    protected bool $isProtected = false;
    /** @var bool */
    protected bool $isShared = false;
    /** @var bool */
    protected bool $isAutowired = false;
    /** @var array */
    protected array $allowEvents = [];
    /** @var array|callable */
    protected mixed $parameters = null;
    /** @var mixed */
    protected mixed $instance = null;



    /**
     * @param Container $container
     * @param string $id
     * @param mixed $parameters [optional]
     * @param mixed $default [optional]
     * @return mixed
     */
    abstract protected function factory(Container $container, string $id, mixed $parameters=null, mixed
    $default=null) :mixed;

    /**
     * @param mixed $instance
     * @return $this
     * @throws Exception|ReflectionException
     */
    abstract public function setInstance(mixed $instance) :static;


    /**
     * @param Container $container
     * @param string $id
     * @param mixed $parameters [optional]
     * @param mixed $default [optional]
     * @return mixed
     */
    public function getInstance(Container $container, string $id, mixed $parameters=null, mixed $default=null) :mixed
    {
        $isSharable = $this instanceof ResourceSharable;
        $isEventable = $this instanceof ResourceEventable;
        if($isEventable)
            $container->dispatch('before', $this, $id);
        if($isSharable && $this->isShared() && null!==$this->instance)
            return $this->instance;
        $instance = $this->factory($container, $id, $parameters, $default);
        if($isSharable && $this->isShared())
            $this->instance = $instance;
        if($isEventable)
            $instance = $container->dispatch('after', $instance, $id);
        return $instance;
    }







    /**
     * @param array $options
     * @return $this
     * @throws ReflectionException
     */
    public function setOptions(array $options) :static
    {
        foreach ($options as $key => $value) {
            if('instance' === $key)
                $this->setInstance($value);
            elseif(is_string($key))
                $this->{$key}($value);
        }
        return $this;
    }

    /**
     * @param ?bool $isProtected
     * @return bool|$this
     */
    public function isProtected(?bool $isProtected = null) :bool|static
    {
        if(null===$isProtected) return $this->isProtected;
        $this->isProtected = $isProtected;
        return $this;
    }

    /**
     * @param ?bool $isShared [optional]
     * @return bool|$this
     */
    protected function isShared(?bool $isShared = null) :bool|static
    {
        if(null===$isShared) return $this->isShared;
        $this->isShared = $isShared;
        return $this;
    }

    /**
     * @return $this
     */
    protected function clearInstanceShared() :static
    { $this->instance = null; return $this; }

    /**
     * @param ?bool $isAutowired
     * @return bool|$this
     */
    protected function isAutowired(?bool $isAutowired = null) :bool|static
    {
        if(null===$isAutowired) return $this->isAutowired;
        $this->isAutowired = $isAutowired;
        return $this;
    }


    /**
     * @param ?array $allowEvents [optional]
     * @return array|$this
     */
    protected function allowEvents(?array $allowEvents = null) :array|static
    {
        if(null===$allowEvents) return $this->allowEvents;
        $this->allowEvents = $allowEvents;
        return $this;
    }

    /**
     * @param string|array $eventName
     * @param ?bool $isAllow [optional]
     * @return bool|$this
     */
    protected function isAllowEvents(string|array $eventName, ?bool $isAllow = null) :bool|static
    {
        if(is_array($eventName)){
            foreach($eventName as $event)
                $this->isAllowEvents($event, (bool) $isAllow);
            return $this;
        }
        $isAllowEvent = in_array($eventName,$this->allowEvents??[]);
        if(null===$isAllow) return $isAllowEvent;
        if($isAllow && !$isAllowEvent){
            $this->allowEvents[] = $eventName;
        }elseif (!$isAllow && $isAllowEvent){
            $this->allowEvents = array_flip($this->allowEvents);
            unset($this->allowEvents[$eventName]);
            $this->allowEvents = array_flip($this->allowEvents);
        }
        return $this;
    }

    /**
     * @param ?bool $isAllow
     * @return bool|$this
     */
    protected function isAllowBeforeEvent(?bool $isAllow = null) :bool|static
    { return $this->isAllowEvents('before',$isAllow); }

    /**
     * @param ?bool $isAllow
     * @return bool|$this
     */
    protected function isAllowAfterEvent(?bool $isAllow = null) :bool|static
    { return $this->isAllowEvents('after',$isAllow); }


    /**
     * @param mixed $parameters
     * @return $this
     * @throws Exception
     */
    protected function setParameters(mixed $parameters) :static
    {
        $this->parameters = (is_callable($parameters) || is_array($parameters)) ?$parameters :null;
        if(null===$this->parameters)
            throw new Exception('parameters is not valid');
        return $this;
    }

    /**
     * @param ?Container $container [optional]
     * @param ?array $parameters [optional]
     * @return array
     */
    protected function getParameters(?Container $container = null, ?array $parameters = null) :array
    {
        if(null!==$container && is_callable($this->parameters)){
            $p = $this->parameters;
            return $p($container, $this, $parameters);
        }
        return !empty($parameters) ?$parameters :(!empty($this->parameters) ? $this->parameters :[]);
    }















    /**
     * @param mixed $instance
     * @param ?array $options [optional]
     * @return $this
     * @throws Exception
     */
    public static function create(mixed $instance, ?array $options = null) :static
    { return (new static($options))->setInstance($instance); }



    /**
     * @param ?array $options [optional]
     * @throws Exception
     */
    public function __construct(?array $options = null)
    { if(null !== $options) $this->setOptions($options); }

    public function __get(string $name)
    {}
    public function __call(string $name, array $arguments)
    {}

}
