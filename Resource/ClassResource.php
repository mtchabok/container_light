<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;
use MCL\Reflection\ClassReflection;
use ReflectionException;

class ClassResource extends Resources implements ResourceSharable, ResourceEventable, ResourceParameterizable
{
    /** @var callable */
    protected mixed $factory = null;
    /** @var mixed */
    protected mixed $instance = null;



    /**
     * @param ?string $classname [optional]
     * @return string|$this
     * @throws Exception
     */
    public function classname(?string $classname = null) :string|static
    {
        if(null===$classname) return (string) $this->factory;
        return $this->setInstance($classname);
    }


    /**
     * @throws ReflectionException
     */
    protected function factory(Container $container, string $id, mixed $parameters = null, mixed $default = null): mixed
    {
        if(!$this->factory instanceof ClassReflection) {
            if(!empty($this->factory) && class_exists($this->factory))
                $this->factory = new ClassReflection($this->factory);
            else
                return $default;
        }
        return $this->factory->newInstance(
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
        $this->factory = (is_string($instance) && !empty($instance) && class_exists($instance))
            ?$instance :null;
        if(null===$this->factory)
            throw new Exception("Instance resource must have a class name");
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
     * @param string $instance class name
     * @param ?array $options [optional]
     * @return $this
     * @throws Exception
     */
    public static function create(mixed $instance, ?array $options = null): static
    { return (new static($options))->setInstance($instance); }

}