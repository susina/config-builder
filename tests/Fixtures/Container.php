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

class Container
{
    private array $parameters = [];

    public function set(string $key, mixed $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
