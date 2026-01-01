<?php
namespace MCL\Resource;
trait DefinitionOptionsTrait
{
    /**
     * @param bool $isProtected
     * @return $this
     */
    public function protected(bool $isProtected) :static
    { $this->protected = $isProtected; return $this; }

    /**
     * @param bool $isShared
     * @return $this
     */
    public function shared(bool $isShared) :static
    { $this->shared = $isShared; return $this; }

    /**
     * @param bool $isAutoWired
     * @return $this
     */
    public function autoWired(bool $isAutoWired) :static
    { $this->autoWired = $isAutoWired; return $this; }
}