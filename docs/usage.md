## Create Your Configuration Definition

The first step to take is writing a `Symfony\Component\Config\Definition\ConfigurationInterface` class, 
to define the structure of your configuration.

Please, read the [official Symfony documentation](https://symfony.com/doc/current/components/config/definition.html)
about defining and processing configuration values, if you are not familiar with it.

## Set Up The Builder

The second step is creating an instance of the `ConfigurationBuilder` class and setting it up, via its fluent api.
Let's suppose we want to look for the configuration files into `app/config` directory, the name of the definition class
is `MyProjectConfiguration` and the file to load is `my-project-config.yml`:

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = ConfigurationBuilder::create()
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ;
```

You can set up the `ConfigurationBuilder` via the methods explained into [Reference](reference.md).

## Get The Configuration as Array

Once you have your builder set up, you can get an array of loaded and processed parameters, by calling
`getConfigurationArray` method:

```php 
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new Configurationuilder();
$builder->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ;
    
$array = $builder->getConfigurationArray();
```

or you can do it in one-line, thanks to the fluent api:

```php 
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$array = Configurationuilder::create()
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ->getConfigurationArray()
    ;
```

## Get The Configuration as Object

Configuration Builder can return an object, of a given class, populated with your configuration values.
This class should accept an array to the constructor or have an initialization method, accepting the same
array as parameter.
You can set up your configuration class via `setConfigurationClass` method and, if the class has an initialization method,
you can use `setInitMethod`.

Suppose that you want to use a [dflydev/dot-access-data](https://github.com/dflydev/dflydev-dot-access-data) as 
configuration class (`Dflydev\DotAccessData\Data` class accept an array of parameters to the constructor):

```php 
<?php declare(strict_types=1);

use Dflydev\DotAccessData\Data;
use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new Configurationuilder();
$builder->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ->setConfigurationClass(Data::class)
    ;
    
$config = $builder->getConfiguration();

//Now, you can use your configuration class
echo $config->get('database.connection');
```

Now, suppose you have a configuration class, like the following:

```php
<?php declare(strict_types=1);

namespace MyApp\MyNamespace;

class ConfigManager {
    
    public function init(array $params): void
    {
        //some operations with $params
        ...........
    }

    //some methods
    ...................
}
```

You can set up the configuration builder to use the `init()` method, by calling `ConfigurationBuilder::setInitMethod)`:

```php
<?php declare(strict_types=1);

use MyApp\MyNamespace\ConfigManager;
use Susina\ConfigBuilder\ConfigurationBuilder;

$config = Configurationuilder::create()
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ->setConfigurationClass(ConfigManager::class)
    ->setInitMethod('init')
    ->getConfiguration()
    ;
```
