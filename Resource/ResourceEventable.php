<?php
namespace MCL\Resource;
interface ResourceEventable
{
    /**
     * @param ?array $allowEvents [optional]
     * @return array|$this
     */
    public function allowEvents(?array $allowEvents = null) :array|static;

    /**
     * @param string|array $eventName
     * @param ?bool $isAllow [optional]
     * @return bool|$this
     */
    public function isAllowEvents(string|array $eventName, ?bool $isAllow = null) :bool|static;

    /**
     * @param ?bool $isAllow
     * @return bool|$this
     */
    public function isAllowBeforeEvent(?bool $isAllow = null) :bool|static;

    /**
     * @param ?bool $isAllow
     * @return bool|$this
     */
    public function isAllowAfterEvent(?bool $isAllow = null) :bool|static;
}