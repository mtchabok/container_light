<?php
namespace MCL\Resource;
trait DefinitionParametersTrait
{
    /**
     * @param mixed $parameters
     * @return $this
     */
    public function parameters(mixed $parameters) :static
    {
        if(is_callable($parameters)){
            $this->parameters = $parameters;
            $this->isCallableParameters = true;
        }else {
            $this->parameters = (!is_array($parameters)) ? [$parameters] : $parameters;
            $this->isCallableParameters = false;
        }
        return $this;
    }
}