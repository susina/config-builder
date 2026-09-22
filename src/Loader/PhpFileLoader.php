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
use Symfony\Component\Config\Loader\FileLoader;

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
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class PhpFileLoader extends FileLoader
{
    /**
     * Loads a PHP file.
     *
     * @param mixed $resource The resource to load.
     * @param string|null $type The resource type.
     * @return array<int|string,mixed>
     * @throws ConfigurationBuilderException
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        $path = $this->getLocator()->locate($resource);

        $fileContent = @file_get_contents($path);

        if ($fileContent === false) {
            throw new ConfigurationBuilderException("The configuration file '$resource' does not exist or is not readable.");
        }

        //empty file must return []
        if ($fileContent === '') {
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

        return $content;
    }

    /**
     * Returns true if this class supports the given resource.
     * It supports both .php and .inc extensions.
     *
     * @param mixed $resource A resource.
     * @param string|null $type The resource type.
     * @return bool true if this class supports the given resource, false otherwise.
     */
    public function supports(mixed $resource, $type = null): bool
    {
        return str_ends_with((string) $resource, '.php') || str_ends_with((string) $resource, '.php.dist');
    }
}
