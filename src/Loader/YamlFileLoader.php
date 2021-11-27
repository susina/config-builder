<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     *
     * @return array
     *
     * @throws ConfigurationBuilderException
     * @throws ParseException if something goes wrong in parsing file
     *
     * @psalm-suppress PossiblyInvalidArgument FileLocator::locate() returns string, since 3rd argument isn't false
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $content = Yaml::parse(file_get_contents($this->getLocator()->locate($resource)));

        // config file is empty
        if ($content === null) {
            return [];
        }
        if (!is_array($content)) {
            throw new ConfigurationBuilderException('Unable to parse the configuration file: wrong yaml content.');
        }

        return $this->resolveParams($content);
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return str_ends_with((string)$resource, '.yml') || str_ends_with((string)$resource, '.yml.dist') ||
            str_ends_with((string)$resource, '.yaml') || str_ends_with((string)$resource, '.yaml.dist')
        ;
    }
}
