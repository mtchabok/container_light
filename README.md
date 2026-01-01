# PHP Container Light

A lightweight and fast PHP container that has been designed to operate efficiently while maintaining high flexibility (customization capabilities) in resource consumption. It is a suitable option for resource management in any type of project.

Some of the features of this container:

- Definition of values, functions, classes with name and namespace (namespace.name)
- Possibility of defining a group as an array or file and managing it with a namespace that is processed if necessary
- Definition of before and after events and desired events to control input and output parameters
- Storage of values, objects or function results permanently or temporarily
- Possibility of protecting against unwanted changes to values
- Control of infinite loops at runtime
- Ability to set class and function inputs to create an object or execute a function
- Definition of aliases for main names with the possibility of various settings
- Execution of events along the path of the defined namespace so that events are executed in one path from the root to the destination
- Events are defined based on the namespace, and it is possible to execute each event on multiple sources
- Cache the way objects are created or functions are executed to improve performance and consumption management Resources
- Ability to define resources in a completely flexible way using file
- Versatile access to resources using methods, virtual properties and virtual methods

## Installation
Use the package manager [composer](https://getcomposer.org/) to install mtchabok container light.
```bash
composer require mtchabok/container_light
```


## How To Usage
```php
$container = new \MCL\Container();
$container->add([
    'prepareMessage'=>function(string $message){ return ".:[ $message ]:."; },
    'showMessage'=>function (string $message) use ($container) { echo $container->prepareMessage($message); },
]);
$container->showMessage('test message!');
```
```text
.:[ test message! ]:.
```
### add array resource into container
```php
$container = new \MCL\Container();
$container->add([
    'prepareMessage'=>function(string $message){ return ".:[ $message ]:."; },
    'showMessage'=>function (string $message) use ($container) { echo $container->prepareMessage($message); },
    'test'=>function(){ return "\nHello World!"; },
    'test2'=>fn() => "\nHow Are You?",
    'test3'=>"\nI`m Fine!",
    'uniqid' => \MCL\Resource\Definition::createCallable('uniqid'),
    'uniq' =>  \MCL\Resource\Definition::createAlias('uniqid')->shared(true),
    'p1' => \MCL\Resource\Definition::createValue('good')->protected(true),
    ':params' => function($p,$c,$id){ echo "\n[on params of '$id'] "; },
    ':after' => function($p, $c, $id){ return is_string($p) ?"$p [after on: $id]" :$p; },
]);
```

### add closure resources into container
```php
$container = new \MCL\Container();
$container->add(
    function (\MCL\Resource\Source $source, \MCL\Container $container) {
        return [
            'prepareMessage'=>function(string $message){ return ".:[ $message ]:."; },
            'showMessage'=>function (string $message) use ($container) { echo $container->prepareMessage($message); },
            'test'=>function(){ return "\nHello World!"; },
            'test2'=>fn() => "\nHow Are You?",
            'test3'=>"\nI`m Fine!",
            'uniqid' => $source->callable('uniqid'),
            'uniq' => $source->alias('uniqid')->shared(true),
            'p1' => $source->value('good')->protected(true),
            ':params' => function($p,$c,$id){ echo "\n[on params of '$id'] "; },
            ':after' => function($p, $c, $id){ return is_string($p) ?"$p [after on: $id]" :$p; },
        ];
    }
);
```

### add file resource into container
content of php file : source.php
```php
use MCL\Resource\Source;
/** @var Source $this */
$container = $this->container;
return [
    'prepareMessage'=>function(string $message){ return ".:[ $message ]:."; },
    'showMessage'=>function (string $message) use ($container) { echo $container->prepareMessage($message); },
    'test'=>function(){ return "\nHello World!"; },
    'test2'=>fn() => "\nHow Are You?",
    'test3'=>"\nI`m Fine!",
    'uniqid' => $this->callable('uniqid'),
    'uniq' => $this->alias('uniqid')->shared(true),
    'p1' => $this->value('good')->protected(true),
    ':params' => function($p,$c,$id){ echo "\n[on params of '$id'] "; },
    ':after' => function($p, $c, $id){ return is_string($p) ?"$p [after on: $id]" :$p; },
];
```
and add source file into container
```php
$container = new \MCL\Container();
$container->add('source.php');
```

### share resource
```php
$container = new \MCL\Container();
$container->add(['uniqid'=>\MCL\Resource\Definition::createCallable('uniqid')->shared(true)]);
echo $container->uniqid()."\n";
echo $container->uniqid()."\n";
echo $container->uniqid()."\n";
```
```text
69539660cf1d7
69539660cf1d7
69539660cf1d7

```

### protect resource
```php
$container = new \MCL\Container();
$container->add(['uniqid'=>fn () => 'text unique']);
$container->add(['uniqid'=>\MCL\Resource\Definition::createCallable('uniqid')->protected(true)]);
$container->add(['uniqid'=>'wow']);
echo $container->uniqid()."\n";
```
```text
695397811b56c

```

### customize resource arguments
```php
$container = new \MCL\Container();
$container->add([
    'message' => 'test message!',
    'showMessage'=>\MCL\Resource\Definition::createCallable(function (string $message) use ($container) { return "msg: $message\n"; })
        ->parameters(function ($args) use ($container) {
            return !empty($args) ?$args :[$container->message];
        }),
]);
echo $container->showMessage();
echo $container->showMessage('another message!');
```
```text
msg: test message!
msg: another message!

```

### alias name of resource
```php
$container = new \MCL\Container();
$container->add([
    'uniqid' => \MCL\Resource\Definition::createCallable('uniqid'),
    'uniq' => \MCL\Resource\Definition::createAlias('uniqid')->shared(true),
]);
echo '1. uniqid: '.$container->uniqid()."\n";
echo '2. uniq: '.$container->uniq()."\n";
echo '3. uniqid: '.$container->uniqid()."\n";
echo '4. uniq: '.$container->uniq()."\n";
```
```text
1. uniqid: 6953a0fa81499
2. uniq: 6953a0fa814c4
3. uniqid: 6953a0fa814d7
4. uniq: 6953a0fa814c4

```

### event on resource
```php
$container = new \MCL\Container();
$container->add([
    'uniqid' => \MCL\Resource\Definition::createCallable('uniqid'),
    'uniq' => \MCL\Resource\Definition::createAlias('n1.uniqid')->shared(true),
],'n1');
$container->add([ ':after' => function ($p) { return "$p [2]"; } ],'n1');
$container->add([ ':after' => function ($p) { return "$p [1]"; } ]);
echo '1. uniqid: '.$container->call('n1.uniqid')."\n";
echo '2. uniq: '.$container->call('n1.uniq')."\n";
echo '3. uniqid: '.$container->call('n1.uniqid')."\n";
echo '4. uniq: '.$container->call('n1.uniq')."\n";
```
```text
1. uniqid: 6953a204b37f8 [1] [2]
2. uniq: 6953a204b383a [1] [2] [1] [2]
3. uniqid: 6953a204b3859 [1] [2]
4. uniq: 6953a204b383a [1] [2] [1] [2]

```


## License
MIT License. See the LICENSE file.

