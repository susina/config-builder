The Configuration Builder class can be set up via the following methods:

## addFile

!!! Example "Signature"
    `#!php-inline public function addFile(string|SplFileInfo ...$files): self`

The parameters can contain:

-  the name of the configuration file to load
-  the full path name of the configuration file to load
-  SplFileInfo object representing the configuration file to load


Use this method to add one or more elements to the list of configuration files to load. I.e.:

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();

$builder->addFile('my-project-config.yaml.dist', 'my-project-config-yml');
```


## setFiles

!!! example "Signature"
    `#!php-inline public function setFiles(array|IteratorAggregate $files): self`

This method receive an array of strings or SplFileInfo objects and set the list of the configuration files to load.
This method __removes__ all the files previously added.

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();
$configFiles = ['my-project.dist.xml', 'my-project.xml'];

$builder->setFiles($configFiles);
```

This method can also accept an iterator, containing strings or SplFileInfo, so you can pass also an instance of a finder object, i.e. [Symfony Finder](https://symfony.com/doc/current/components/finder.html):

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;
use Symfony\Component\Finder\Finder;

$builder = new ConfigurationBuilder();
$finder = new Finder();

$finder->in('app/config')->name('*.json')->files();

$builder->setFiles($finder);
```


## addDirectory

!!! example "Signature"
    `#!php-inline public function addDirectory(string|SplFileInfo ...$dirs): self`

Add one or more directories where to find the configuration files.

The parameters can contain:

-  the full path name of the directory
-  SplFileInfo object representing a directory where to find the configuration files

This method check if the passed directories are existent and readable, otherwise throws a `ConfigurationBuilderException`.


```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();

$builder->addDirectory(__DIR__ . '/app/config', getcwd());
```


## setDirectories

!!! example "Signature"
    `#!php-inline public function setDirectories(array|IteratorAggregate $dirs): self`

This method receive an array of strings or SplFileInfo objects and set the list of the directories where to find the configuration files to load.
This method __removes__ all the directory previously added.

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new ConfigurationBuilder();
$dirs = [__DIR__ . '/app/config', getcwd()];

$builder->setDirectories($dirs);
```

This method can also accept an iterator, containing strings or SplFileInfo, so you can pass also an instance of a finder object, i.e. [Symfony Finder](https://symfony.com/doc/current/components/finder.html):

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;
use Symfony\Component\Finder\Finder;

$builder = new ConfigurationBuilder();
$finder = new Finder();

$finder->in(getcwd())->name('config')->directories();

$builder->setDirectories($dirs);
```


## setDefinition

!!! example "Signature"
    `#!php-inline public function setDefinition(ConfigurationInterface $definition): self`

Add an instance of `Symfony\Component\Config\Definition\ConfigurationInterface` to process the configuration parameters.

For further information about Symfony Config and how to define a `ConfigurationInterface` class, please see the [official Symfony documentation](https://symfony.com/doc/current/components/config/definition.html).


## setConfigurationClass

!!! example "Signature"
    `#!php-inline public function setConfigurationClass(string $configurationClass): self`

Set the configuration class to populate with the processed parameters. If the class does not exist, a `ConfigurationBuilderException` is thrown.
The method expects to pass an array of parameters to the class constructor.


## setInitMethod

!!! example "Signature"
    `#!php-inline public function setInitMethod(string $initMethod): self`

The configuration class, set via [setConfigurationClass](#set-configuration-class) method, could be populated via its constructor or via an _initialization_ method, expecting an array as parameter.
With `setInitMethod` we set the method to use to populate the configuration class.


Suppose you have a configuration class, like the following:

```php
<?php declare(strict_types=1);

namespace MyApp\MyNamespace;

class ConfigurationManager {
    
    public function setParameters(array $params): void
    {
        //some operations with $params
        ...........
    }

    //some methods
    ...................
}
```

The set up of your `ConfigurationBuilder` should be:

```php
<?php declare(strict_types=1);

use MyApp\MyNamespace\ConfigManager;
use Susina\ConfigBuilder\ConfigurationBuilder;

$config = Configurationuilder::create()
    ..............
    ->setConfigurationClass(ConfigurationManager::class)
    ->setInitMethod('setParameters')
    ;
```


## setBeforeParams

!!! example "Signature"
    `#!php-inline public function setBeforeParams(array $beforeParams): self`

Set an array of parameters to merge into your configuration __before__ loading the files.

Note that the value of this parameters _could be overwritten_ by the ones loaded from the configuration files.


## setAfterParams

!!! example "Signature"
    `#!php-inline public function setAfterParams(array $afterParams): self`

Set an array of parameters to merge into your configuration __after__ loading the files.

Note that the value of this parameters _could overwrite_ the ones loaded from the configuration files.

## setCacheDirectory

!!! example "Signature"
    `#!php-inline public function setCacheDirectory(string $cache): self`

Set the directory where to save the cache files (see [Cache](usage.md#cache)).

## populateContainer

!!! example "Signature"
`#!php-inline public function populateContainer(object $container, string $method): void`

Populate a dependency injection container `$container` with the loaded configuration parameters.
You can retrieve each parameter with a _dot acces_ key (i.e. database.connection.dsn).

## keepFirstXmlTag

!!! example "Signature"
`#!php-inline public function keepFirstXmlTag(bool $keep = true): self`

When loading XML files, keep the first xml tag as part of the configuration.

Consider the following xml:

```xml
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
```

it usually results in the following array:

```php
<?php
    [
        'foo' => 'bar', 
        'bar' => 'baz'
    ];
```

If you call `keepFirstXmTag` then the resulted array is the following:

```php
<?php
    [
        'properties' => [
            'foo' => 'bar', 
            'bar' => 'baz'
        ]
    ];
```
