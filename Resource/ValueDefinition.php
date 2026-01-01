<?php
namespace MCL\Resource;
class ValueDefinition extends Definition
{
    use DefinitionEventTrait;

    public function __construct(mixed $value)
    { parent::__construct(static::TYPE_VALUE, $value); }

    /**
     * @param bool $isProtected
     * @return $this
     */
    public function protected(bool $isProtected) :static
    { $this->protected = $isProtected; return $this; }

}