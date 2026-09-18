<?php

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 *
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE file distributed
 * with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\XmlToArray\Converter;
use Susina\XmlToArray\Exception\ConverterException;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * XmlFileLoader loads configuration parameters from xml file.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class XmlFileLoader extends FileLoader
{
    /**
     * The xml converter object.
     * @var Converter
     */
    private Converter $converter;

    public function __construct(FileLocatorInterface $fileLocator, bool $keepFirstTag = false)
    {
        $this->converter = new Converter(['preserveFirstTag' => $keepFirstTag]);
        parent::__construct($fileLocator);
    }

    /**
     * Loads a Xml file.
     *
     * @param mixed $resource The resource to load.
     * @param string|null $type The resource type.
     * @return array<int|string,mixed>
     * @throws ConverterException If an error occurs while parsing the xml.
     * @throws ConfigurationBuilderException If an error occurs while reading the xml file.
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $xmlContent = @file_get_contents($this->getLocator()->locate($resource));

        return match ($xmlContent) {
            false => throw new ConfigurationBuilderException("The configuration file '$resource' is not readable."),
            '' => [],
            default => $this->converter->convert($xmlContent),
        };
    }

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed $resource A resource.
     * @param string|null $type The resource type.
     * @return bool true If this class supports the given resource, false otherwise.
     */
    public function supports(mixed $resource, ?string $type = null): bool
    {
        return str_ends_with((string) $resource, '.xml') || str_ends_with((string) $resource, '.xml.dist');
    }
}
