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
use Susina\ConfigBuilder\Loader\IniFileLoader;
use Susina\ConfigBuilder\Loader\JsonFileLoader;
use Susina\ConfigBuilder\Loader\NeonFileLoader;
use Susina\ConfigBuilder\Loader\PhpFileLoader;
use Susina\ConfigBuilder\Loader\XmlFileLoader;
use Susina\ConfigBuilder\Loader\YamlFileLoader;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Config\Resource\FileResource;

final class ConfigurationBuilder
{
    public const CACHE_FILE = 'susina_config_builder.cache';

    /**
     * @var string[] The configuration files to load.
     */
    private array $files = [];

    /**
     * @var array The directories where to find the configuration files.
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
     * @var array Additional array of parameters to merge BEFORE to load the configuration files.
     */
    private array $beforeParams = [];

    /**
     * @var array Additional array of parameters to merge AFTER to load the configuration files.
     */
    private array $afterParams = [];

    private string $cacheDirectory = '';

    /**
     * @var bool if keep the first xml tag in an xml configuration file
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
     * @param string|SplFileInfo ...$files
     *
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
     * It accepts also an Iterator, so that It's possible to directly pass the result of a finder library
     * (e.g. Symfony Finder)
     *
     * @param array|IteratorAggregate $files
     *
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
     * @param string|SplFileInfo ...$dirs
     *
     * @return $this
     * @throws ConfigurationBuilderException
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
     * Set the name of the directory where to find the configuration files to load.
     * It accepts also an Iterator, so that It's possible to directly pass the result of a finder library
     * (e.g. Symfony Finder)
     *
     * @param array|IteratorAggregate $dirs
     *
     * @return $this
     * @throws ConfigurationBuilderException
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
     * @param ConfigurationInterface $definition
     *
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
     * @param string $configurationClass
     *
     * @return $this
     * @throws ConfigurationBuilderException
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
     * @param string $initMethod
     *
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
     * @param array $beforeParams
     *
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
     * @param array $afterParams
     *
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
     * @throws ConfigurationBuilderException
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
     * Keep also the first tag of a xml configuration.
     *
     * @param bool $keep
     *
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
     * @param object $container The container object
     * @param string $method The container method to add a parameter
     *                       (i.e. `set` for Php-Di or `setParameter` for Symfony Dependency Injection).
     *
     * @return void
     */
    public function populateContainer(object $container, string $method): void
    {
        $config = $this->getConfigurationArray();
        $parameters = [];
        $this->getDotArray($config, $parameters);

        array_map([$container, $method], array_keys($parameters), array_values($parameters));
    }

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

    private function loadParameters(): array
    {
        $fileLocator = new FileLocator($this->directories);
        $loaderResolver = new LoaderResolver([
            new IniFileLoader($fileLocator),
            new JsonFileLoader($fileLocator),
            new NeonFileLoader($fileLocator),
            new PhpFileLoader($fileLocator),
            new XmlFileLoader($fileLocator, $this->keepFirstXmlTag),
            new YamlFileLoader($fileLocator)
        ]);
        $delegatingLoader = new DelegatingLoader($loaderResolver);

        return array_merge_recursive(...array_map([$delegatingLoader, 'load'], $this->files));
    }

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
     * @return array
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
