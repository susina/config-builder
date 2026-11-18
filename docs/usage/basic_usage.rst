Basic Usage
===========

.. _ConfigurationBuilder: classes/Susina-ConfigBuilder-ConfigurationBuilder.html
.. _Api Reference: namespaces/default.html


Building a configuration is a three-step process:

1. load the parameters from some configuration files
2. process the loaded parameters to normalize and validate them
3. return an array of cleaned parameters or a configuration object

.. Note::
    The configuration builder returns an instance of `Dflydev\\DotAccessData\\Data <https://github.com/dflydev/dflydev-dot-access-data/blob/main/src/Data.php>`_ by default.
    Please, see `https://github.com/dflydev/dflydev-dot-access-data`_ repository for further information.

We ship loaders for the following file formats:

- **.json** via PHP json extension
- **.neon** via `Nette Neon <https://github.com/nette/neon>`_ library
- **.php**
- **.toml** via `PHP Toml <https://php-collective.github.io/toml/>`_
- **.xml** via `Susina xml to array <https://github.com/susina/xml-to-array>`_ library
- **.yml** via `Symfony Yaml <https://symfony.com/doc/current/components/yaml.html>`_ component

The Configuration Builder can populate any object whose constructor, or any other initialize method, takes an array as a parameter.


Create Your Configuration Definition
------------------------------------

The first step to take is writing a ``Symfony\Component\Config\Definition\ConfigurationInterface`` class,
to define the structure of your configuration.

Please, read the `official Symfony documentation <https://symfony.com/doc/current/components/config/definition.html>`_
about defining and processing configuration values, if you are not familiar with it.


Set Up The Builder
------------------

The second step is creating an instance of the `ConfigurationBuilder`_ class and setting it up, via its fluent api.
Let's suppose we want to look for the configuration files into ``app/config`` directory, the name of the definition class is ``MyProjectConfiguration`` and the file to load is ``my-project-config.yml``:

.. code:: php
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $builder = ConfigurationBuilder::create()
       ->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ;

You can set up the ``ConfigurationBuilder`` via all the methods explained into `Api Reference`_ document.


Get The Configuration as an Array
---------------------------------

Once you have your builder set up, you can get an array of loaded and processed parameters, by calling `getConfigurationArray <https://github.com/susina/config-builder/blob/master/src/ConfigurationBuilder.php#L286>`_ method:

.. code:: php
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $builder = new Configurationuilder();

   $builder
       ->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ;

   $array = $builder->getConfigurationArray();

or you can do it in one-line, thanks to the fluent api:

.. code:: php
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $array = ConfigurationBuilder::create()
       ->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ->getConfigurationArray()
       ;


Get The Configuration as an Object
----------------------------------

Configuration Builder can return an object, of a given class, populated with your configuration values.
This class should accept an array to the constructor or have an initialization method, accepting the same array as parameter.

You can set up your configuration class via ``setConfigurationClass`` method and, if the class has an initialization method, you can use ``setInitMethod``.

Let's suppose you have the following configuration class:

.. code:: php
   class MessConfiguration
   {
       private array $parameters = [];

       public function __construct(array $parameters)
       {
           $this->parameters = $parameters;
       }

       public function getParameters(): array
       {
           return $this->parameters;
       }
   }


Actually, this class is a true mess to manage configurations but it's ok for our example.

As you can see, the initialization happens via constructor.
So, you can use it as following:

.. code:: php
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $builder = new Configurationuilder();

   $builder->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ->setConfigurationClass(MessConfiguration::class)
       ;

   $config = $builder->getConfiguration();

   // $config is an instance of MessConfiguration class and you ca use it

   $config->getParameters();

.. note::
    The Configuration Builder returns a `dflydev/dot-access-data <https://github.com/dflydev/dflydev-dot-access-data>`_ as default configuration class.


   So, you can write the following:

.. code:: php
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $builder = new Configurationuilder();

   $builder->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ;

   $config = $builder->getConfiguration();

   //$config is an instance of Dflydev\DotAccessData\Data
   echo $config->get('database.connection');
   ```

Now, suppose you have a configuration class, like the following:

.. code:: php
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

As you can see, the initialization happens via ``init`` method, receiving an array of parameters.
So, you can set up the configuration builder to use the ``init()`` method, by calling ``ConfigurationBuilder::setInitMethod``:

.. code:: php
   use MyApp\MyNamespace\ConfigManager;
   use Susina\ConfigBuilder\ConfigurationBuilder;

   $config = Configurationuilder::create()
       ->addDirectory('app/config')
       ->addFile('my-project-config.yml')
       ->setDefinition(MyProjectConfiguration::class)
       ->setConfigurationClass(ConfigManager::class)
       ->setInitMethod('init')
       ->getConfiguration()
       ;


Cache
-----

``ConfigurationBuilder`` has a cache system based on `Symfony Config Cache <https://symfony.com/doc/current/components/config/caching.html>`_.

If you set your cache directory, via ``setCacheDirectory`` method, after the first request, the parameters are taken from cache instead of
loading from the configuration files and processing them.

The cache is invalidated when one of the following events happen:

1. one of the configuration files is changed
2. the ``ConfigurationBuilder`` set up is changed
3. one of the cache files is deleted

In example, let's suppose you have an application and your bootstrap file looks something like this:

.. code:: php
   // bootstrap.php

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

When the first request occurs, the builder loads ``app/config/my-app-config.yml`` file, process the parameters (via ``MyAppConfiguration`` definition), 
populates and returns a ``ConfigurationManager`` class.

For all the subsequent requests, the builder get the parameters from the cache.
