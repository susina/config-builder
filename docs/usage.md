## Create Your Configuration Definition

The first step to take is writing a `Symfony\Component\Config\Definition\ConfigurationInterface` class, 
to define the structure of your configuration.

Please, read the [official Symfony documentation](https://symfony.com/doc/current/components/config/definition.html)
about defining and processing configuration values, if you are not familiar with it.

## Set Up The Builder

The second step is creating an instance of the [ConfigurationBuilder](https://github.com/susina/config-builder/blob/master/src/ConfigurationBuilder.php) class and setting it up, via its fluent api.
Let's suppose we want to look for the configuration files into `app/config` directory, the name of the definition class is `MyProjectConfiguration` and the file to load is `my-project-config.yml`:

```php
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = ConfigurationBuilder::create()
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ;
```

You can set up the `ConfigurationBuilder` via all the methods explained into [Api Reference](api/index.html) document.

## Get The Configuration as an Array

Once you have your builder set up, you can get an array of loaded and processed parameters, by calling [getConfigurationArray](https://github.com/susina/config-builder/blob/master/src/ConfigurationBuilder.php#L286) method:

```php 
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$builder = new Configurationuilder();

$builder
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ;

$array = $builder->getConfigurationArray();
```

or you can do it in one-line, thanks to the fluent api:

```php 
<?php declare(strict_types=1);

use Susina\ConfigBuilder\ConfigurationBuilder;

$array = ConfigurationBuilder::create()
    ->addDirectory('app/config')
    ->addFile('my-project-config-yml')
    ->setDefinition(MyProjectConfiguration::class)
    ->getConfigurationArray()
    ;
```

## Get The Configuration as an Object

Configuration Builder can return an object, of a given class, populated with your configuration values.
This class should accept an array to the constructor or have an initialization method, accepting the same array as parameter.

You can set up your configuration class via `setConfigurationClass` method and, if the class has an initialization method, you can use `setInitMethod`.

Suppose you want to use a [dflydev/dot-access-data](https://github.com/dflydev/dflydev-dot-access-data) as configuration class (`Dflydev\DotAccessData\Data` class accept an array of parameters to the constructor):

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

You can set up the configuration builder to use the `init()` method, by calling `ConfigurationBuilder::setInitMethod`:

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

## Cache

`ConfigurationBuilder` has a cache system based on [Symfony Config Cache](https://symfony.com/doc/current/components/config/caching.html).

If you set your cache directory, via `setCacheDirectory` method, after the first request, the parameters are taken from cache instead of loading from the configuration files and processing them.

The cache is invalidated when one of the following events occurs:

1.  one of the configuration files is changed
2.  the `ConfigurationBuilder` set up is changed
3.  one of the cache files is deleted

In example, let's suppose you have an application and your bootstrap file looks something like this:

```php
// bootstrap.php
<?php declare(strict_type=1);

$configuration = ConfigurationBuilder::create()
    ->addDirectory('app/config')
    ->setCacheDirectory('app/cache')
    ->addFile('my-app-config.yml')
    ->setConfigurationClass(ConfigurationManager::class)
    ->setDefinition(new MyAppConfiguration())
    ->getConfiguration()
;

// some other bootstrap instructions
....................................
```

When the first request occurs, the builder loads `app/config/my-app-config.yml` file, process the parameters (via `MyAppConfiguration` definition), populates and returns a `ConfigurationManager` class.

For all the subsequent requests, the builder get the parameters from the cache.
