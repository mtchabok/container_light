<?php
namespace MCL\Resource;
use Exception;
use MCL\Container;

interface ResourceParameterizable
{

    /**
     * @param mixed $parameters
     * @return $this
     * @throws Exception
     */
    public function setParameters(mixed $parameters) :static;

    /**
     * @param ?Container $container [optional]
     * @param ?array $parameters [optional]
     * @return array
     */
    public function getParameters(?Container $container = null, ?array $parameters = null) :array;
}