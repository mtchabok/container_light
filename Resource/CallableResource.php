<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;
use MCL\Reflection\CallableReflection;
use ReflectionException;

class CallableResource extends Resources implements ResourceSharable, ResourceEventable, ResourceParameterizable
{
    /** @var callable */
    protected mixed $factory = null;
    /** @var mixed */
    protected mixed $instance = null;


    /**
     * @param ?callable $callback [optional]
     * @return callable|null|$this
     * @throws Exception
     */
    public function callback(mixed $callback = null) :callable|null|static
    {
        if(null===$callback) return $this->factory;
        return $this->setInstance($callback);
    }

    /**
     * @throws ReflectionException
     */
    protected function factory(Container $container, string $id, mixed $parameters = null, mixed $default = null): mixed
    {
        if(!$this->factory instanceof CallableReflection) {
            if(!empty($this->factory) && is_callable($this->factory))
                $this->factory = new CallableReflection($this->factory);
            else
                return $default;
        }
        return $this->factory->call(
            $this->getParameters($container, $parameters)
            , $this->isAutowired(), $container);
    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setInstance(mixed $instance): static
    {
        $this->instance = null;
        $this->factory = is_callable($instance) ?$instance :null;
        if(null===$this->factory)
            throw new Exception("Callable resource must have a callable instance");
        return $this;
    }





    public function isShared(?bool $isShared = null) :bool|static
    { return parent::isShared($isShared); }

    public function clearInstanceShared() :static
    { return parent::clearInstanceShared(); }

    public function isAutowired(?bool $isAutowired = null) :bool|static
    { return parent::isAutowired($isAutowired); }

    public function allowEvents(?array $allowEvents = null) :array|static
    { return parent::allowEvents($allowEvents); }

    public function isAllowEvents(string|array $eventName, ?bool $isAllow = null) :bool|static
    { return parent::isAllowEvents($eventName, $isAllow); }

    public function isAllowBeforeEvent(?bool $isAllow = null) :bool|static
    { return parent::isAllowBeforeEvent($isAllow); }

    public function isAllowAfterEvent(?bool $isAllow = null) :bool|static
    { return parent::isAllowAfterEvent($isAllow); }

    public function setParameters(mixed $parameters) :static
    { return parent::setParameters($parameters); }

    public function getParameters(?Container $container = null, ?array $parameters = null) :array
    { return parent::getParameters($container, $parameters); }

    /**
     * @param callable $instance callable value
     * @param ?array $options [optional]
     * @return $this
     * @throws Exception
     */
    public static function create(mixed $instance, ?array $options = null): static
    { return (new static($options))->setInstance($instance); }

}