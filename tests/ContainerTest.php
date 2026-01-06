<?php
namespace MCL\tests;
use MCL\Container;
use MCL\Resource\Definition;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContainerTest extends TestCase
{
    public function testWithValue()
    {
        $container = new Container();
        $name = 'value1';
        $value = 'val1';
        $container->add([$name => $value]);
//        $container->{$name};
//        var_dump($container);
        $this->assertEquals($value, $container->{$name});
    }

    public function testWithAddSourceData()
    {
        $containerFromArray = new Container();
        $containerFromFile = new Container();

        $containerFromFile->add(__DIR__.'/source.php');
        $containerFromArray->add([
            'prepareMessage'=>function(string $message){ return "\n$message\n"; },
            'showMessage'=>function (string $message) use ($containerFromArray) { echo $containerFromArray->prepareMessage($message); },
            'test'=>function(){ return "\nHello World!"; },
            'test2'=>fn() => "\nHow Are You?",
            'test3'=>"\nI`m Fine!",
            'uniqid' => \MCL\Resource\Definition::createCallable('uniqid'),
            'uniq' =>  \MCL\Resource\Definition::createAlias('uniqid')->shared(true),
            'p1' => \MCL\Resource\Definition::createValue('good')->protected(true),
            ':params' => function($p,$c,$id){ echo "\n[on params of '$id'] "; },
            ':after' => function($p, $c, $id){ return is_string($p) ?"$p [after on: $id]" :$p; },
        ]);

        $this->assertEquals($containerFromArray->test,$containerFromFile->test);
    }

    public function testWithAfterEventControl()
    {
        $container = new Container();
        $container->add(__DIR__.'/source.php');
        echo $container
            ->showMessage('lorem ipsum');

        $this->assertEquals($container
            ->showMessage('lorem ipsum')
            ,'.:[ lorem ipsum ]:. [after on: prepareMessage] [after on: showMessage]');
    }

    public function testProtectedResource()
    {
        $container = new Container();
        $container->add(__DIR__.'/source.php');
        $val1 = $container->p1;
        $container->p1 = ($val2 = 'new Value');
        $this->assertNotEquals($val1,$val2);
    }

    public function testValuesInSource()
    {
        $container = new Container();
        $container->add(__DIR__.'/source.php');
        $this->assertEquals('localhost', $container->config['database']['host']);
    }

}