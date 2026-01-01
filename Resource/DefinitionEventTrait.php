<?php
namespace MCL\Resource;
trait DefinitionEventTrait
{
    /**
     * @param array $allowEvents
     * @return $this
     */
    public function allowEvents(array $allowEvents) :static
    { $this->allowEvents = $allowEvents; return $this; }

    /**
     * @param string|array $eventName
     * @return $this
     */
    public function allowEvent(string|array $eventName) :static
    { return $this->allowEvents( array_keys( array_merge( array_flip($this->allowEvents)
        , array_flip(is_string($eventName) ?[$eventName] :$eventName) ) ) ); }

    /**
     * @return $this
     */
    public function allowParamsEvent() :static
    { return $this->allowEvent('params'); }

    /**
     * @return $this
     */
    public function allowAfterEvent() :static
    { return $this->allowEvent('after'); }

    /**
     * @param string|array $eventName
     * @return $this
     */
    public function disAllowEvent(string|array $eventName) :static
    { return $this->allowEvents( array_keys(array_diff_key(array_flip($this->allowEvents)
        , array_flip(is_string($eventName) ?[$eventName] :$eventName) ) ) ); }

}