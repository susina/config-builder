<?php

declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class YamlFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     * @return array<int|string,mixed>
     * @throws ConfigurationBuilderException If an error occurs while reading the file.
     * @throws ParseException If something goes wrong in parsing the file.
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $content = Yaml::parseFile($this->getLocator()->locate($resource));

        return match (true) {
            $content === null => [],
            !is_array($content) => throw new ConfigurationBuilderException("Invalid YAML content: configuration file '$resource'."),
            default => $content,
        };
    }

    /**
     * Returns true if this class supports the given resource.
     * Both 'yml' and 'yaml' extensions are accepted.
     *
     * @param string $resource A resource.
     * @param string|null $type The resource type.
     * @return bool true If this class supports the given resource, false otherwise.
     */
    public function supports($resource, $type = null): bool
    {
        return str_ends_with((string) $resource, '.yml') || str_ends_with((string) $resource, '.yml.dist')
            || str_ends_with((string) $resource, '.yaml') || str_ends_with((string) $resource, '.yaml.dist')
        ;
    }
}
