<?php
namespace MCL\Resource;
class CallableDefinition extends Definition
{
    use DefinitionParametersTrait, DefinitionEventTrait, DefinitionOptionsTrait;

    /**
     * @param callable|array|string $callable
     */
    public function __construct(callable|array|string $callable)
    { parent::__construct(static::TYPE_CALLABLE, $callable); }
}