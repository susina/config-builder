<?php

declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use PhpCollective\Toml\Toml;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * TomlFileLoader loads configuration parameters from toml file.
 *
 * @author Cristiano Cinotti
 */
class TomlFileLoader extends FileLoader
{
    /**
     * Loads a Toml file.
     *
     * @param mixed $resource The resource to load.
     * @param string|null $type The resource type.
     * @return array<int|string,mixed>
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        return Toml::decodeFile($this->getLocator()->locate($resource));
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource.
     * @param string|null $type The resource type.
     * @return bool true If this class supports the given resource, false otherwise.
     */
    public function supports($resource, $type = null): bool
    {
        return str_ends_with((string) $resource, '.toml') || str_ends_with((string) $resource, '.toml.dist');
    }
}
