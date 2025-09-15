# PHP Dependency Injection Container Light
A lightweight, fast, and flexible container for managing resources (values, functions, objects, etc.)
- fast, flexible and powerfull
- class and closure autowire support (auto dependencies to inject)
- alias support (alias for any resource with diffrence config)
- share resource control (once create object of class or once execute closure)
- protect resource support (protect on change resource)
- execute resource loop control
- resource event support (after, before and any event control on [class|closure|alias] resources)
- flexible load resources [file or array] (control with name space and load resources with match resource name and source name space)
- run anonymous closure (with call method)
- support create object from class without bind (with make method)
- multiple dependency injection support (class|closure|alias)
  - auto injection
  - define closure for dependencies
  - direct array pass to resource
- optimize use of server resources [cpu,ram] (load resources)
- flexible access to resources (__get, __call method)

## Installation
Use the package manager [composer](https://getcomposer.org/) to install mtchabok container light.
```bash
composer require mtchabok/container_light
```

## How To Usage
```php
$container = new \MCL\Container();
$container->bind(function(){ return "\nHello World!"; }, 'test');
$container->bind(fn() => "\nHow Are You?", 'test2');
$container->bind("\nI`m Fine!", 'test3');

echo $container->call('test');
echo $container->get('test1', "\ndefault value");
echo $container->test2();
echo $container->test3;
```
```text
Hello World!
default value
How Are You?
I`m Fine!
```
### Usage alias and share
```php
$c = new \MCL\Container();
$c->binds([
    'uniqid' => 'uniqid',
    'unique' => fn (\MCL\Container $c) => $c->get('uniqid'),
    'uniqueAlias' => ['type'=>\MCL\Resource\AliasResource::class, 'aliasOf'=>'unique', 'isShared'=>true],
    'uniqueMessage' => fn (\MCL\Container $c) => "\nUnique ID: ".$c->get('unique'),
    'uniqueAliasMessage' => fn (\MCL\Container $c) => "\nUnique alias ID: ".$c->get('uniqueAlias'),
]);
echo $c->get('uniqueMessage');
echo $c->get('uniqueAliasMessage');
echo $c->get('uniqueMessage');
echo $c->get('uniqueAliasMessage');
echo $c->get('uniqueAliasMessage1', "\nnot found!");
echo $c->get('uniqueMessage');
```
```text
Unique ID: 68c7c2a42a610
Unique alias ID: 68c7c2a42a65b
Unique ID: 68c7c2a42a675
Unique alias ID: 68c7c2a42a65b
not found!
Unique ID: 68c7c2a42a69b
```
