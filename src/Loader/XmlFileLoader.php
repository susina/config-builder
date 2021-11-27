<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Assert\AssertionFailedException;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\Exception\XmlParseException;
use Susina\ConfigBuilder\XmlToArrayConverter;

/**
 * XmlFileLoader loads configuration parameters from xml file.
 *
 * @author Cristiano Cinotti
 */
class XmlFileLoader extends FileLoader
{
    /**
     * Loads a Xml file.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     *
     * @return array
     *
     * @throws AssertionFailedException|ConfigurationException
     * @throws XmlParseException
     *
     * @psalm-suppress PossiblyInvalidArgument FileLocator::locate() returns string, since 3rd argument isn't false
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $converter = new XmlToArrayConverter();
        $content = $converter->convert(file_get_contents($this->getLocator()->locate($resource)));

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
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return str_ends_with((string)$resource, '.xml') || str_ends_with((string)$resource, '.xml.dist');
    }
}
