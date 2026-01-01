<?php
namespace MCL\Resource;
class ClassDefinition extends Definition
{
    use DefinitionParametersTrait, DefinitionEventTrait, DefinitionOptionsTrait;

    public function __construct(string|object|null $classname = null)
    { parent::__construct(static::TYPE_CLASS, $classname); }

}