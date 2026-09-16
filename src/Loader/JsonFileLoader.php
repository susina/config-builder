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

/**
 * JsonFileLoader loads configuration parameters from json file.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class JsonFileLoader extends FileLoader
{
    /**
     * Loads a Json file.
     *
     * @param mixed $resource The resource to load.
     * @param string|null $type The resource type.
     * @return array<int|string,mixed>
     * @throws ConfigurationBuilderException if the file does not exist or is not readable.
     * @throws \JsonException If errors while deconding Json.
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $json = @file_get_contents($this->getLocator()->locate($resource));

        return match ($json) {
            false => throw new ConfigurationBuilderException("The configuration file '$resource' is not readable."),
            '' => [],
            default => json_decode($json, true, 512, JSON_THROW_ON_ERROR),
        };
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     * @return bool true If this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return str_ends_with((string) $resource, '.json') || str_ends_with((string) $resource, '.json.dist');
    }
}
