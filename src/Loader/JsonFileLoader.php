<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

/**
 * JsonFileLoader loads configuration parameters from json file.
 *
 * @author Cristiano Cinotti
 */
class JsonFileLoader extends FileLoader
{
    /**
     * Loads a Json file.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     *
     * @return array
     * @throws \JsonException
     *
     * @psalm-suppress PossiblyInvalidArgument FileLocator::locate() returns string, since 3rd argument isn't false
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $json = file_get_contents($this->getLocator()->locate($resource));
        if ($json === '') {
            return [];
        }

        $content = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $this->resolveParams($content); //Resolve parameter placeholders (%name%)
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports($resource, $type = null): bool
    {
        return str_ends_with((string)$resource, '.json') || str_ends_with((string)$resource, '.json.dist');
    }
}
