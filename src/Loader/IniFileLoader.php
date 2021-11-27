<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;

/**
 * IniFileLoader loads parameters from INI files.
 *
 * This class is heavily inspired to Zend\Config component ini reader.
 * http://framework.zend.com/manual/2.1/en/modules/zend.config.reader.html
 *
 * @author Cristiano Cinotti
 */
class IniFileLoader extends FileLoader
{
    /**
     * Separator for nesting levels of configuration data identifiers.
     *
     * @var string
     */
    private string $nestSeparator = '.';

    /**
     * Returns true if this class supports the given resource.
     *
     * @param mixed  $resource A resource
     * @param string|null $type The resource type
     *
     * @return bool true if this class supports the given resource, false otherwise
     */
    public function supports(mixed $resource, string $type = null): bool
    {
        return str_ends_with((string) $resource, '.ini') || str_ends_with((string) $resource, '.ini.dist');
    }

    /**
     * Loads a resource, merge it with the default configuration array and resolve its parameters.
     *
     * @param mixed $resource The resource
     * @param string|null $type The resource type
     *
     * @return array  The configuration array
     *
     * @throws ConfigurationBuilderException
     */
    public function load(mixed $resource, ?string $type = null): array
    {
        /** @var string $file */
        $file = $this->getLocator()->locate($resource);
        $ini = parse_ini_file($file, true, INI_SCANNER_RAW);

        if ($ini === false) {
            throw new ConfigurationBuilderException("The configuration file '$file' has invalid content.");
        }

        $ini = $this->parse($ini); //Parse for nested sections

        return $this->resolveParams($ini); //Resolve parameter placeholders (%name%)
    }

    /**
     * Parse data from the configuration array, to transform nested sections into associative arrays
     * and to fix int/float/bool typing
     * @param  array $data
     * @return array
     */
    private function parse(array $data): array
    {
        $config = [];

        foreach ($data as $section => $value) {
            if (is_array($value)) {
                if (str_contains($section, $this->nestSeparator)) {
                    $sections = explode($this->nestSeparator, $section);
                    $config = array_merge_recursive($config, $this->buildNestedSection($sections, $value));
                } else {
                    $config[$section] = $this->parseSection($value);
                }
            } else {
                $this->parseKey($section, $value, $config);
            }
        }

        return $config;
    }

    /**
     * Process a nested section
     *
     * @param  array $sections
     * @param  mixed $value
     * @return array
     */
    private function buildNestedSection(array $sections, mixed $value): array
    {
        if (count($sections) == 0) {
            return $this->parseSection($value);
        }

        $nestedSection = [];

        $first = array_shift($sections);
        $nestedSection[$first] = $this->buildNestedSection($sections, $value);

        return $nestedSection;
    }

    /**
     * Parse a section.
     *
     * @param  array $section
     * @return array
     */
    private function parseSection(array $section): array
    {
        $config = [];

        foreach ($section as $key => $value) {
            $this->parseKey((string) $key, $value, $config);
        }

        return $config;
    }

    /**
     * Process a key.
     *
     * @param string $key
     * @param mixed $value
     * @param array  $config
     *
     * @throws ConfigurationBuilderException
     */
    private function parseKey(string $key, mixed $value, array &$config): void
    {
        if (str_contains($key, $this->nestSeparator)) {
            $pieces = explode($this->nestSeparator, $key, 2);

            if (!strlen($pieces[0]) || !strlen($pieces[1])) {
                throw new ConfigurationBuilderException(sprintf('Invalid key "%s"', $key));
            } elseif (!isset($config[$pieces[0]])) {
                if ($pieces[0] === '0' && !empty($config)) {
                    $config = [$pieces[0] => $config];
                } else {
                    $config[$pieces[0]] = [];
                }
            } elseif (!is_array($config[$pieces[0]])) {
                throw new ConfigurationBuilderException(sprintf(
                    'Cannot create sub-key for "%s", as key already exists',
                    $pieces[0]
                ));
            }

            $this->parseKey($pieces[1], $value, $config[$pieces[0]]);
        } elseif (is_string($value) && in_array(strtolower($value), ["true", "false"])) {
            $config[$key] = (strtolower($value) === "true");
        } elseif ($value === (string)(int) $value) {
            $config[$key] = (int) $value;
        } elseif ($value === (string)(float) $value) {
            $config[$key] = (float) $value;
        } else {
            $config[$key] = $value;
        }
    }
}
