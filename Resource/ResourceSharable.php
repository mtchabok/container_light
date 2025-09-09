<?php
namespace MCL\Resource;
interface ResourceSharable
{
    /**
     * @param ?bool $isShared
     * @return bool|$this
     */
    public function isShared(?bool $isShared = null) :bool|static;

    /**
     * @return $this
     */
    public function clearInstanceShared() :static;
}