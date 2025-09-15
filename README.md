# PHP Dependency Injection Container Light
A lightweight, fast, and flexible container for managing resources (values, functions, objects, etc.)
- fast and flexible
- class and closure autowire support (auto dependencies to inject)
- alias support (alias for any resource with diffrence config)
- share resource control (once create object of class or once execute closure)
- protect resource support (protect on change resource)
- execute resource loop control
- resource event support (after, before and any event control on [class|closure|alias] resources)

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
