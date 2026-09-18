<?php

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 *
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE file distributed
 * with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Fixtures;

class ConfigurationConstructor
{
    private array $parameters = [];

    public function __construct(array $parameters)
    {
        $this->parameters = $parameters;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
