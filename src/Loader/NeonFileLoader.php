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

use Nette\Neon\Neon;
use Symfony\Component\Config\Loader\FileLoader;

/**
 * YamlFileLoader loads configuration parameters from yaml file.
 *
 * @author Cristiano Cinotti
 */
class NeonFileLoader extends FileLoader
{
    /**
     * Loads a Neon file.
     *
     * @param mixed $resource The resource to load.
     * @param string|null $type The resource type.
     * @return array<int|string,mixed>
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        return Neon::decodeFile($this->getLocator()->locate($resource)) ?? [];
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
        return str_ends_with((string) $resource, '.neon') || str_ends_with((string) $resource, '.neon.dist');
    }
}
