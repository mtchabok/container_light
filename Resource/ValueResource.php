<?php
namespace MCL\Resource;
use MCL\Container;
class ValueResource extends Resources
{

    /**
     * @param mixed $value [optional]
     * @return mixed|$this
     */
    public function value(mixed $value = null) :mixed
    {
        if(null === $value) return $this->instance;
        $this->instance = $value;
        return $this;
    }

    /**
     * @param mixed $instance
     * @return $this
     */
    public function setInstance(mixed $instance):static
    {
        $this->instance = $instance;
        return $this;
    }

    protected function factory(Container $container, string $id, mixed $parameters = null, mixed $default = null): mixed
    { return $this->instance ?? $default; }
}