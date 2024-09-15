<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ParamResolver\ParamResolver;
use Symfony\Component\Config\Loader\FileLoader;

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

        return ParamResolver::create()->resolve($ini); //Resolve parameter placeholders (%name%)
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
                if (str_contains($section, '.')) {
                    $sections = explode('.', $section);
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
        if (str_contains($key, '.')) {
            $this->parseNested($key, $value, $config);
        } else {
            $config[$key] = match (true) {
                is_string($value) && strtolower($value) === 'true' => true,
                is_string($value) && strtolower($value) === 'false' => false,
                (string)(int) $value === $value => (int) $value,
                (string)(float) $value === $value => (float) $value,
                default => $value
            };
        }
    }

    private function parseNested(string $key, mixed $value, array &$config): void
    {
        if (str_starts_with($key, '.') || str_ends_with($key, '.')) {
            throw new ConfigurationBuilderException("Invalid key \"$key\"");
        }

        $pieces = explode('.', $key, 2);

        if (!isset($config[$pieces[0]])) {
            if ($pieces[0] === '0' && !empty($config)) {
                $config = [$pieces[0] => $config];
            } else {
                $config[$pieces[0]] = [];
            }
        }

        if (!is_array($config[$pieces[0]])) {
            throw new ConfigurationBuilderException("Cannot create sub-key for \"$pieces[0]\", as key already exists");
        }

        $this->parseKey($pieces[1], $value, $config[$pieces[0]]);
    }
}
