<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;

/**
 * PhpFileLoader loads configuration values from a PHP file.
 *
 * The configuration values are expected to be in form of array. I.e.
 * <code>
 *     <?php
 *         return array(
 *                    'property1' => 'value1',
 *                    .......................
 *                );
 * </code>
 *
 * @author Cristiano Cinotti
 */
class PhpFileLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     *
     *
     * @return array
     * @throws ConfigurationBuilderException
     *
     * @psalm-suppress UnresolvableInclude $path contains a path resolved by FileLocator
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        /** @var string $path */
        $path = $this->getLocator()->locate($resource);

        //empty file must return []
        if (file_get_contents($path) === '') {
            return [];
        }

        //Use output buffering because in case $file contains invalid non-php content (i.e. plain text), include() function
        //write it on stdoutput
        ob_start();
        $content = include $path;
        ob_end_clean();

        if (!is_array($content)) {
            throw new ConfigurationBuilderException("The configuration file '$resource' has invalid content.");
        }

        return $this->resolveParams($content); //Resolve parameter placeholders (%name%)
    }

    /**
     * Returns true if this class supports the given resource.
     * It supports both .php and .inc extensions.
     *
     * @param mixed $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports(mixed $resource, $type = null): bool
    {
        return str_ends_with((string)$resource, '.php') || str_ends_with((string)$resource, '.php.dist');
    }
}
