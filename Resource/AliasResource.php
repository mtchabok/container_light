<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;
use ReflectionException;

class AliasResource extends Resources implements ResourceSharable, ResourceEventable, ResourceParameterizable
{
    /** @var string */
    protected mixed $factory = null;
    /** @var mixed */
    protected mixed $instance = null;
    /** @var bool */
    protected bool $isExist = false;


    /**
     * @param ?string $aliasOf [optional]
     * @return string|$this
     * @throws Exception
     */
    public function aliasOf(?string $aliasOf = null) :string|static
    {
        if(null===$aliasOf) return (string) $this->factory;
        return $this->setInstance($aliasOf);
    }


    /**
     * @throws Exception
     */
    protected function factory(Container $container, string $id, mixed $parameters = null, mixed $default = null): mixed
    {
        if(!$this->isExist)
            $this->isExist = !empty($this->factory) && is_string($this->factory) && $container->has($this->factory);
        if($this->isExist) {
            $parameters = $this->getParameters($container, $parameters);
            $instance = $container->get($this->factory, $default, $parameters);
        }else
            return $default;
        return $instance;
    }


    /**
     * @inheritDoc
     * @throws Exception
     */
    public function setInstance(mixed $instance): static
    {
        $this->instance = null;
        $this->isExist = false;
        $this->factory = (is_string($instance) && !empty($instance)) ?$instance :null;
        if(null===$this->factory)
            throw new Exception("Instance resource must have a resource name");
        return $this;
    }




    public function isShared(?bool $isShared = null) :bool|static
    { return parent::isShared($isShared); }

    public function clearInstanceShared() :static
    { return parent::clearInstanceShared(); }

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
     * @param string $instance alias of binding id
     * @param array|null $options
     * @return static
     * @throws Exception|ReflectionException
     */
    public static function create(mixed $instance, ?array $options = null): static
    { return (new static($options))->setInstance($instance); }

}