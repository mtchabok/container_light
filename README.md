# container_light
A lightweight, fast, and flexible container for managing resources (values, functions, objects, etc.)

## Installation
Use the package manager [composer](https://getcomposer.org/) to install mtchabok container light.
```bash
composer require mtchabok/container_light
```

## Usage
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
