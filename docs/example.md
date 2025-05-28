In this example, we'll create a complete configuration subsystem for an imaginary application.

We assume that our application has the following directory structure:

```
example
│
└───app
│    └───config
│    │      example-config.yml
│    │
│    └───resources
│
└───src
│    └───Configuration
│            ExampleConfiguration.php
│
└───var
│    └───log
│    │
│    └───cache
│
│
└───tests
```

- `app/config/example-config.yml` is our configuration file
- `var/cache` is our cache directory
- `src/Configuration/ExampleConfiguration.php` is our definition class

The application namespace is `App` and it points to `src` directory.

We want to manage our configuration via [Dot Access Data](https://github.com/dflydev/dflydev-dot-access-data) library.

## Installation

We need to install:

- `susina/config-builder` (of course!)
- `symfony/yaml` since we decide to use _yaml_ format for our file
- `dflydev/dot-access-data` we love to access the configuration properties via dot syntax

```bash
composer require susina/config-builder symfony/yaml dflydev/dot-access-data
```

## Our configuration file

The configuration file, we'll load and process, is `app/config/example-config.yaml`:

```yaml title="app/config/example-config.yaml"
app:
  database:
    auto_connect: true
    default_connection: pgsql
    connections:
      pgsql:
        host: localhost
        driver: postgresql
        username: user
        password: pass
      sqlite:
        host: localhost
        driver: sqlite
        memory: true
        username: user
        password: pass

  paths:
    template: app/resources
    logger: var/log
```

## The definition class

The class containing the definition, to process the configuration parameters is `App\Configuration\ExampleConfiguration.php`:

```php title="App\Configuration\ExampleConfiguration.php"
<?php declare(strict_types=1);

namespace App\Configuration;

use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class ExampleConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('app');
        $treeBuilder->getRootNode()
            ->append($this->addDatabaseNode())
            ->append($this->addPathsNode())
        ;
    }

    public function addDatabaseNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('database');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('auto_connect')->defaultTrue()->end()
                ->scalarNode('default_connection')->defaultValue('default')->end()
                ->fixXmlConfig('connection')
                ->children()
                    ->arrayNode('connections')
                        ->arrayPrototype()
                            ->children()
                                ->scalarNode('driver')->end()
                                ->scalarNode('host')->end()
                                ->scalarNode('username')->end()
                                ->scalarNode('password')->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    public function addPathsNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('paths');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('template')->required()->end()
                ->scalarNode('logger')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}

```

## Let's go

Let's use the `ConfigurationBuilder` to load and process our file and to instantiate the class, to manage the configuration:

```php
<?php declare(strict_types=1);

use App\Configuration\ExampleConfiguration;
use Dflydev\DotAccessData\Data;


$config = ConfigurationBuilder::create()
    ->addDirectory('app/config')
    ->addFile('example-config.yml')
    ->setDefinition(ExampleConfiguration::class)
    ->setConfigurationClass(Data::class)
    ->setCacheDirectory('var/log')
    ->getConfiguration()
;

//Now we ca use our configuration object
$connection = new Connection(
    $config->get('database.user'),
    $config->get('database.pass')
);

$template = new Template();
$template->setDir($config->get('paths.template'));
```
