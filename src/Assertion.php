<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder;

use Assert\Assertion as BaseAssertion;
use Susina\ConfigBuilder\Exception\ConfigurationException;

class Assertion extends BaseAssertion
{
    protected static $exceptionClass = ConfigurationException::class;

    public static function intOrFloatOrString(mixed $value, ?string $message = null, string $propertyPath = null): bool
    {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            $message = \sprintf(
                static::generateMessage($message ?: 'Value "%s" must be an int or float or string.'),
                static::stringify($value)
            );

            throw static::createException($value, $message, static::VALUE_NOT_EMPTY, $propertyPath);
        }

        return true;
    }

    public static function notStrictlyFalse(mixed $value, ?string $message = null, string $propertyPath = null): bool
    {
        if (false === $value) {
            $message = \sprintf(
                static::generateMessage($message ?: 'Value "%s" must not be false.'),
                static::stringify($value)
            );

            throw static::createException($value, $message, static::VALUE_NOT_EMPTY, $propertyPath);
        }

        return true;
    }
}
