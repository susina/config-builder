<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Symfony\Component\Config\FileLocator as BaseFileLocator;

class FileLocator extends BaseFileLocator
{
    public function locate(string $name, ?string $currentPath = null, bool $first = true): string|array
    {
        $output = parent::locate($name, $currentPath, $first);
        if (!is_array($output)) {
            $output = [$output];
        }

        array_map(
            function (string $element): string {
                if (!is_readable($element)) {
                    throw new ConfigurationBuilderException("Path \"$element\" was expected to be readable.");
                }

                return $element;
            },
            $output
        );

        return $first ? $output[0] : $output;
    }
}
