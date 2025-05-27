<?php declare(strict_types=1);
/*
 * This file is part of susina/config-builder-builder package,
 * released under the APACHE-2 license.
 * For the full copyright and license information,
 * please view the LICENSE file, distributed with this source code.
 */

namespace Susina\ConfigBuilder;

use IteratorAggregate;
use SplFileInfo;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Loader\JsonFileLoader;
use Susina\ConfigBuilder\Loader\NeonFileLoader;
use Susina\ConfigBuilder\Loader\PhpFileLoader;
use Susina\ConfigBuilder\Loader\XmlFileLoader;
use Susina\ConfigBuilder\Loader\YamlFileLoader;
use Susina\ParamResolver\ParamResolver;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;

/**
 * Class ConfigurationBuilder.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
final class ConfigurationBuilder
{
    /**
     * @var string The name of the cache file.
     */
    public const CACHE_FILE = 'susina_config_builder.cache';

    /**
     * @var string[] The configuration files to load.
     */
    private array $files = [];

    /**
     * @var string[] The directories where to find the configuration files.
     */
    private array $directories = [];

    /**
     * @var ConfigurationInterface|null The definition object to process the configuration parameters.
     */
    private ?ConfigurationInterface $definition = null;

    /**
     * @var string The configuration class to build.
     */
    private string $configurationClass = '';

    /**
     * @var string The name of the method to initialize the configuration object. If empty, the builder
     *             passes the array of parameters to the constructor.
     */
    private string $initMethod = '';

    /**
     * @var array<string, mixed> Additional array of parameters to merge BEFORE loading the configuration files.
     */
    private array $beforeParams = [];

    /**
     * @var array<string,mixed> Additional array of parameters to merge AFTER loading the configuration files.
     */
    private array $afterParams = [];

    /**
     * @var array<string,string> An array of key => values elements to replace into the configuration, before parameters resolving and validating.
     */
    private array $replaces = [];

    /**
     * @string The cache directory.
     */
    private string $cacheDirectory = '';

    /**
     * @var bool If keep the first xml tag in an xml configuration file.
     */
    private bool $keepFirstXmlTag = false;

    /**
     * Static constructor.
     *
     * @return static
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add one or more file names to the array of configuration files to load.
     *
     * The parameters can contain:
     * -  the name of the configuration file to load
     * -  the full path name of the configuration file to load
     * -  SplFileInfo object representing the configuration file to load
     *
     * Use this method to add one or more elements to the list of configuration files to load. I.e.:
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $builder = new ConfigurationBuilder();
     * $builder->addFile('my-project-config.yaml.dist', 'my-project-config-yml');
     * ```
     *
     * @param string|SplFileInfo ...$files The files to load.
     * @return $this
     */
    public function addFile(string|SplFileInfo ...$files): self
    {
        $this->files = array_merge(
            $this->files,
            array_map(
                fn ($element): string => $element instanceof SplFileInfo ? $element->getPathname() : $element,
                $files
            )
        );

        return $this;
    }

    /**
     * Set the name of the configuration files to load.
     *
     * This method receives an array of strings or SplFileInfo objects and sets the list of the configuration files to load.
     * It __removes__ all the files previously added.
     *
     * ```php
     * <?php declare(strict_types=1);
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $builder = new ConfigurationBuilder();
     * $configFiles = ['my-project.dist.xml', 'my-project.xml'];
     * $builder->setFiles($configFiles);
     * ```
     *
     * This method can also accept an iterator, containing strings or SplFileInfo,
     * so you can pass also an instance of a finder object, i.e. [Symfony Finder](https://symfony.com/doc/current/components/finder.html):
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     * use Symfony\Component\Finder\Finder;
     *
     * $builder = new ConfigurationBuilder();
     *
     * $finder = new Finder();
     * $finder->in('app/config')->name('*.json')->files();
     *
     * $builder->setFiles($finder);
     * ```
     *
     * @param array|IteratorAggregate $files The files to add.
     * @return $this
     */
    public function setFiles(array|IteratorAggregate $files): self
    {
        $this->files = [];
        if ($files instanceof IteratorAggregate) {
            $files = iterator_to_array($files, false);
        }

        return $this->addFile(...$files);
    }

    /**
     * Add one or more directories where to find the configuration files.
     *
     * Add one or more directories where to find the configuration files.
     * The parameters can contain:
     *
     * -  the full path name of the directory
     * -  SplFileInfo object representing a directory where to find the configuration files
     *
     * This method check if the passed directories are existent and readable,
     * otherwise throws a `ConfigurationBuilderException`.
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $builder = new ConfigurationBuilder();
     * $builder->addDirectory(__DIR__ . '/app/config', getcwd());
     * ```
     *
     * @param string|SplFileInfo ...$dirs The directories to add.
     * @return $this
     * @throws ConfigurationBuilderException If a directory does not exist or it's not writeable.
     */
    public function addDirectory(string|SplFileInfo ...$dirs): self
    {
        $this->directories = array_merge(
            $this->directories,
            array_map(
                function (string|SplFileInfo $dir): string {
                    $dirName = $dir instanceof SplFileInfo ? $dir->getPathname() : $dir;
                    if (!is_dir($dirName)) {
                        throw new ConfigurationBuilderException("Path \"$dirName\" was expected to be a directory.");
                    }
                    if (!is_readable($dirName)) {
                        throw new ConfigurationBuilderException("Path \"$dirName\" was expected to be readable.");
                    }

                    return $dirName;
                },
                $dirs
            )
        );

        return $this;
    }

    /**
     * Set the name of the directories where to find the configuration files to load.
     *
     * This method receives an array of strings or SplFileInfo objects and sets the list of the directories
     * where to find the configuration files to load. It  __removes__ all the previously added directories.
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $builder = new ConfigurationBuilder();
     * $dirs = [__DIR__ . '/app/config', getcwd()];
     * $builder->setDirectories($dirs);
     * ```
     *
     * This method can also accept an iterator, containing strings or SplFileInfo,
     * so you can pass also an instance of a finder object, i.e. [Symfony Finder](https://symfony.com/doc/current/components/finder.html):
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     * use Symfony\Component\Finder\Finder;
     *
     * $builder = new ConfigurationBuilder();
     * $finder = new Finder();
     * $finder->in(getcwd())->name('config')->directories();
     *
     * $builder->setDirectories($dirs);
     * ```
     *
     * @param array|IteratorAggregate $dirs Se the entire directories array.
     * @return $this
     * @throws ConfigurationBuilderException If a directory does not exist or it's not writeable.
     */
    public function setDirectories(array|IteratorAggregate $dirs): self
    {
        $this->directories = [];
        if ($dirs instanceof IteratorAggregate) {
            $dirs = iterator_to_array($dirs, false);
        }

        return $this->addDirectory(...$dirs);
    }

    /**
     * Set the object to process the configuration parameters.
     *
     * Add an instance of `Symfony\Component\Config\Definition\ConfigurationInterface` to process the configuration parameters.
     * For further information about Symfony Config and how to define a `ConfigurationInterface` class,
     * please see the [official Symfony documentation](https://symfony.com/doc/current/components/config/definition.html).
     *
     * @param ConfigurationInterface $definition The configuration definition object.
     * @return $this
     * @see https://symfony.com/doc/current/components/config/definition.html
     */
    public function setDefinition(ConfigurationInterface $definition): self
    {
        $this->definition = $definition;

        return $this;
    }

    /**
     * Set the full class name of the configuration object to instantiate.
     *
     * Set the configuration class to populate with the processed parameters. If the class does not exist,
     * a `ConfigurationBuilderException` is thrown. This method expects to pass an array of parameters to the class constructor.
     *
     * @param string $configurationClass The configuration full classname.
     *
     * @return $this
     * @throws ConfigurationBuilderException If the class does not exist.
     */
    public function setConfigurationClass(string $configurationClass): self
    {
        if (!class_exists($configurationClass)) {
            throw new ConfigurationBuilderException("Class \"$configurationClass\" does not exist.");
        }
        $this->configurationClass = $configurationClass;

        return $this;
    }

    /**
     * Set the method to use to initialize the configuration object.
     *
     * The configuration class, set via [setConfigurationClass](#set-configuration-class) method, could be populated
     * via its constructor or via an _initialization_ method, expecting an array as parameter.
     * With `setInitMethod` we set the method to use to populate the configuration class.
     *
     * Suppose you have a configuration class, like the following:
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * namespace MyApp\MyNamespace;
     *
     * class ConfigurationManager
     * {
     *      public function setParameters(array $params): void
     *      {
     *          //some operations with $params
     *          ...........
     *      }
     *
     *      // some othe methods
     *      ................
     * }
     * ```
     *
     * The set up of your `ConfigurationBuilder` should be:
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use MyApp\MyNamespace\ConfigManager;
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $config = Configurationuilder::create()
     *  ->setConfigurationClass(ConfigurationManager::class)
     *  ->setInitMethod('setParameters');
     * ```
     *
     * @param string $initMethod The name of the method.
     * @return $this
     */
    public function setInitMethod(string $initMethod): self
    {
        $this->initMethod = $initMethod;

        return $this;
    }

    /**
     * Set an array of additional parameters to merge before loading the configuration files.
     *
     * Set an array of parameters to merge into your configuration __before__ loading the files.
     * These parameters are processed and validated via Symfony\Config, so they must be compatible with
     * the definition class specified in `$definition` property.
     *
     * > The value of these parameters __could be overwritten__ by the ones loaded from the configuration files.
     *
     * @param array<string,mixed> $beforeParams The array of parameters to be merged.
     * @return $this
     */
    public function setBeforeParams(array $beforeParams): self
    {
        $this->beforeParams = $beforeParams;

        return $this;
    }

    /**
     * Set an array of additional parameters to merge after loading the configuration files.
     *
     * Set an array of parameters to merge into your configuration __after__ loading the files.
     * These parameters are processed and validated via Symfony\Config, so they must be compatible with
     * the definition class specified in `$definition` property.
     *
     * > The value of these parameters __could overwrite__ the ones loaded from the configuration files.
     *
     * @param array<string,mixed> $afterParams The array of parameters to be merged.
     * @return $this
     */
    public function setAfterParams(array $afterParams): self
    {
        $this->afterParams = $afterParams;

        return $this;
    }

    /**
     * Set the cache directory or the CacheInterface object
     *
     * @return $this
     * @throws ConfigurationBuilderException If the directory does not exist or it's not readable.
     */
    public function setCacheDirectory(string $cache): self
    {
        if (!is_dir($cache)) {
            throw new ConfigurationBuilderException("Path \"$cache\" was expected to be a directory.");
        }
        if (!is_readable($cache)) {
            throw new ConfigurationBuilderException("Path \"$cache\" was expected to be readable.");
        }
        $this->cacheDirectory = $cache;

        return $this;
    }

    /**
     * Set an array of parameters to replace.
     *
     * This values are useful for parameters replacing and they're removed before processing and validating
     * the configuration. In this way, you can write some "standard" parameters in your configuration. I.e.:
     *
     * ```yaml
     * cache:
     *  path: %kernel_dir%/cache/my_app.cache
     * ```
     *
     * Now,you can inject the `kernel_dir` parameter to replace it:
     *
     * ```php
     * <?php declare(strict_types=1);
     *
     * use Susina\ConfigBuilder\ConfigurationBuilder;
     *
     * $config = Configurationuilder::create()
     *  ->setReplaces(['kernel_dir' => '/my/absolute/path])
     *  ->getConfigurationArray();
     *
     * // $config = [
     * //   'cache' => [
     * //       'path' =>  '/my/absolute/path/my_app.cache'
     * //   ]
     * //]
     * ```
     *
     * Note that, after replacing, the `kernel_dir` parameter is removed from the configuration.
     *
     * @param array<string, mixed> $params The parameters to replace
     * @return $this
     */
    public function setReplaces(array $params): self
    {
        $this->replaces = $params;

        return $this;
    }

    /**
     * Keep also the first tag of a xml configuration.
     *
     * When loading XML files, it keeps the first xml tag as part of the configuration. Consider the following xml:
     *
     * ```xml
     * <?xml version='1.0' standalone='yes'?>
     * <properties>
     *   <foo>bar</foo>
     *   <bar>baz</bar>
     * </properties>
     * ```
     *
     * it usually results in the following array:
     * ```php
     * <?php
     *     [
     *         'foo' => 'bar',
     *         'bar' => 'baz'
     *     ];
     * ```
     *
     * If you call `keepFirstXmTag` then the resulted array is the following:
     *
     * ```php
     * <?php
     *     [
     *         'properties' => [
     *             'foo' => 'bar',
     *             'bar' => 'baz'
     *         ]
     *     ];
     * ```
     *
     * @param bool $keep
     * @return $this
     */
    public function keepFirstXmlTag(bool $keep = true): self
    {
        $this->keepFirstXmlTag = $keep;

        return $this;
    }

    /**
     * Return a populated configuration object.
     *
     * @return object
     * @throws ConfigurationBuilderException If not set any configuration classtoinstantiate.
     */
    public function getConfiguration(): object
    {
        if ($this->configurationClass === '') {
            throw new ConfigurationBuilderException(
                'No configuration class to instantiate. Please, set it via `setConfigurationClass` method.'
            );
        }

        $parameters = $this->getConfigurationArray();

        if ($this->initMethod === '') {
            return new $this->configurationClass($parameters);
        }

        $configuration = new $this->configurationClass();
        $configuration->{$this->initMethod}($parameters);

        return $configuration;
    }

    /**
     * Return the loaded and processed configuration parameters as an associative array.
     *
     * @return array
     */
    public function getConfigurationArray(): array
    {
        return $this->cacheDirectory !== '' ? $this->loadFromCache() : $this->loadConfiguration();
    }

    /**
     * Populate a container object with the configuration values.
     *
     * This method populates a dependency injection container `$container` with the loaded configuration parameters.
     * You can acces the loaded parameters _dot acces_ key reference (i.e. database.connection.dsn).
     *
     * @param object $container The container object
     * @param string $method The container method to add a parameter (i.e. `set` for Php-Di or `setParameter` for Symfony Dependency Injection).
     * @return void
     */
    public function populateContainer(object $container, string $method): void
    {
        $config = $this->getConfigurationArray();
        $parameters = [];
        $this->getDotArray($config, $parameters);

        array_map([$container, $method], array_keys($parameters), array_values($parameters));
    }

    /**
     * Transform an array in dotted notation.
     * Useful to populate a di-container.
     *
     * @param array $parameters The array to translate in dotted notation.
     * @param array &$output The array to return.
     * @param string $affix The optional affix to add to the dotted key.
     */
    private function getDotArray(array $parameters, array &$output, string $keyAffix = ''): void
    {
        foreach ($parameters as $key => $value) {
            $key = $keyAffix !== '' ? "$keyAffix.$key" : $key;
            if (is_array($value)) {
                $this->getDotArray($value, $output, $key);
            } else {
                $output[$key] = $value;
            }
        }
    }

    /**
     * Load parameters from the configuration files.
     *
     * @psalm-suppress NamedArgumentNotAllowed
     */
    private function loadParameters(): array
    {
        $fileLocator = new FileLocator($this->directories);
        $loaderResolver = new LoaderResolver([
            new JsonFileLoader($fileLocator),
            new NeonFileLoader($fileLocator),
            new PhpFileLoader($fileLocator),
            new XmlFileLoader($fileLocator, $this->keepFirstXmlTag),
            new YamlFileLoader($fileLocator)
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        $parameters = array_merge_recursive(...array_map([$delegatingLoader, 'load'], $this->files));

        //Add replaces to the array...
        foreach ($this->replaces as $key => $value) {
            $parameters[$key] = $value;
        }

        //Param resolver do the job...
        $parameters = ParamResolver::create()->resolve($parameters);

        //Remove replaces from the array.
        foreach ($this->replaces as $key => $value) {
            unset($parameters[$key]);
        }

        return $parameters;
    }

    /**
     * Process and validate the configuration.
     *
     * @throws ConfigurationBuilderException If the definition file is not set.
     */
    private function loadConfiguration(): array
    {
        $processor = new Processor();

        if ($this->definition === null) {
            throw new ConfigurationBuilderException('No definition class. Please, set one via `setDefinition` method.');
        }

        return $processor->processConfiguration(
            $this->definition,
            [$this->beforeParams, $this->loadParameters(), $this->afterParams]
        );
    }

    /**
     * Load the configuration from cache.
     *
     * @return array The configuration
     *
     * @psalm-suppress PossiblyInvalidArgument FileLocator::locate() returns a string
     *                                         if the 3rd function argument is not set to false
     * @psalm-suppress UnresolvableInclude
     */
    private function loadFromCache(): array
    {
        $cacheFile = $this->cacheDirectory . DIRECTORY_SEPARATOR . self::CACHE_FILE;
        $cache = new ConfigCache($cacheFile, true, $this);

        if (!$cache->isFresh()) {
            $params = $this->loadConfiguration();
            $resources = array_map(
                fn (string $file): FileResource => new FileResource($file),
                array_map([new FileLocator($this->directories), 'locate'], $this->files)
            );
            $code = "<?php declare(strict_types=1);\n\nreturn " . var_export($params, true) . ';';

            $cache->write($code, $resources);
            file_put_contents(
                $this->cacheDirectory . DIRECTORY_SEPARATOR . 'config_builder.serial',
                serialize($this)
            );
        }

        return include $cacheFile;
    }
}
