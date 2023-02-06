<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

use Susina\ConfigBuilder\ConfigurationBuilder;

trait ReflectionTrait
{
    /**
     * @throws \ReflectionException
     */
    public function getProperty(ConfigurationBuilder $builder, string $name): mixed
    {
        $class = new \ReflectionObject($builder);
        $property = $class->getProperty($name);
        $property->setAccessible(true);

        return $property->getValue($builder);
    }
}
