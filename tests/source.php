<?php
use MCL\Resource\Source;
/** @var Source $this */
$container = $this->container;
return [
    'prepareMessage'=>function(string $message){ return ".:[ $message ]:."; },
    'showMessage'=>function (string $message) use ($container) { return $container->prepareMessage($message); },

    'test'=>function(){ return "\nHello World!"; },
    'test2'=>fn() => "\nHow Are You?",
    'test3'=>"\nI`m Fine!",

    'uniqid' => $this->callable('uniqid'),
    'uniq' => $this->alias('uniqid')->shared(true),

    'p1' => $this->value('good')->protected(true),

    'myObj' => $this->class('stdClass')->protected(true),

    ':params' => function($p,$c,$id){ echo "\n[on params of '$id'] "; },
    ':after' => function($p, $c, $id){ return is_string($p) ?"$p [after on: $id]" :$p; },
];