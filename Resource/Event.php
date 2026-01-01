<?php
namespace MCL\Resource;
use Closure;
class Event
{
    protected function __construct(
        public readonly Closure|array|string $callable
    ) {}

    /**
     * @param Closure|array|string $callable
     * @return static
     */
    public static function createEvent(Closure|array|string $callable) :static
    { return new static($callable); }
}