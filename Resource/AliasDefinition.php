<?php
namespace MCL\Resource;
class AliasDefinition extends Definition
{
    use DefinitionParametersTrait, DefinitionEventTrait, DefinitionOptionsTrait;

    /**
     * @param string $aliasOf
     */
    public function __construct(string $aliasOf)
    { parent::__construct(static::TYPE_ALIAS, $aliasOf); }
}