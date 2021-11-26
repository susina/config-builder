<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Assert\AssertionFailedException;
use Nette\Neon\Neon;
use Susina\ConfigBuilder\Assertion;
use Susina\ConfigBuilder\Exception\ConfigurationException;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class NeonFileLoader extends FileLoader
{
    /**
     * Loads a Yaml file.
     *
     * @param string $resource The resource
     * @param string|null $type The resource type
     *
     * @return array
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $content = Neon::decode(file_get_contents($this->getLocator()->locate($resource)));

        return $content === null ? [] : $this->resolveParams($content);
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
        return str_ends_with((string)$resource, '.neon') || str_ends_with((string)$resource, '.neon.dist');
    }
}
