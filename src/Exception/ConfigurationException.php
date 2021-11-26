<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Exception;

use Assert\AssertionFailedException;
use RuntimeException;

class ConfigurationException extends RuntimeException implements AssertionFailedException
{
    private ?string $propertyPath;
    private mixed $value;
    private array $constraints;

    public function __construct(string $message, int $code = 0, string $propertyPath = null, mixed $value = null, array $constraints = [])
    {
        parent::__construct($message, $code);

        $this->propertyPath = $propertyPath;
        $this->value = $value;
        $this->constraints = $constraints;
    }

    /**
     * User controlled way to define a sub-property causing
     * the failure of a currently asserted objects.
     *
     * Useful to transport information about the nature of the error
     * back to higher layers.
     *
     * @return string|null
     */
    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    /**
     * Get the value that caused the assertion to fail.
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Get the constraints that applied to the failed assertion.
     */
    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
