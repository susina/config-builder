<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Loader;

use Generator;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Symfony\Component\Config\Loader\FileLoader as BaseFileLoader;

/**
 * Abstract class used by all file-based loaders.
 *
 * The resolve method and correlatives, with parameters between placeholders %name%, are heavily inspired to
 * Symfony\Component\DependencyInjection\ParameterBag class.
 *
 * @author Cristiano Cinotti
 */
abstract class FileLoader extends BaseFileLoader
{
    /**
     * If the configuration array with parameters is resolved.
     */
    private bool $resolved = false;

    /**
     * Configuration values array.
     * It contains the configuration values array to manipulate while resolving parameters.
     * It's useful, in particular, resolve() and get() method.
     *
     * @var array
     */
    private array $config = [];

    /**
     * Replaces parameter placeholders (%name%) by their values for all parameters.
     *
     * @param array $configuration The configuration array to resolve
     *
     * @return array
     */
    public function resolveParams(array $configuration): array
    {
        if ($this->resolved) {
            return [];
        }

        $this->config = $configuration;
        $parameters = [];
        foreach ($configuration as $key => $value) {
            $key = $this->resolveValue($key);
            $value = $this->resolveValue($value);
            $parameters[$key] = $this->unescapeValue($value);
        }

        $this->resolved = true;

        return $parameters;
    }

    /**
     * Replaces parameter placeholders (%name%) by their values.
     *
     * @param mixed $value The value to be resolved
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     */
    private function resolveValue(mixed $value, array $resolving = []): mixed
    {
        if (is_array($value)) {
            $args = [];
            foreach ($value as $k => $v) {
                $args[$this->resolveValue($k, $resolving)] = $this->resolveValue($v, $resolving);
            }

            return $args;
        }

        if (!is_string($value)) {
            return $value;
        }

        return $this->resolveString($value, $resolving);
    }

    /**
     * Resolves parameters inside a string
     *
     * @param string $value The string to resolve
     * @param array $resolving An array of keys that are being resolved (used internally to detect circular references)
     *
     * @return mixed The resolved value
     */
    private function resolveString(string $value, array $resolving = []): mixed
    {
        /*
         * %%: to be unescaped
         * %[^%\s]++%: a parameter
         *         ^ backtracking is turned off
         * when it matches the entire $value, it can resolve to any value.
         * otherwise, it is replaced with the resolved string or number.
         */
        $onlyKey = null;
        $replaced = preg_replace_callback('/%([^%\s]*+)%/', function (array $match) use ($resolving, $value, &$onlyKey) {
            $key = $match[1];
            // skip %%
            if ($key === '') {
                return '%%';
            }

            $env = $this->parseEnvironmentParams($key);
            if (null !== $env) {
                return $env;
            }

            if (isset($resolving[$key])) {
                throw new ConfigurationBuilderException("Circular reference detected for parameter '$key'.");
            }

            if ($value === $match[0]) {
                $onlyKey = $key;

                return $match[0];
            }

            $resolved = $this->get($key);

            if (!(is_numeric($resolved) || is_string($resolved))) {
                throw new ConfigurationBuilderException('A string value must be composed of strings and/or numbers.');
            }

            $resolving[$key] = true;
            $resolved = (string)$resolved;

            return $this->resolveString($resolved, $resolving);
        }, $value);

        if (!isset($onlyKey)) {
            return $replaced;
        }

        $resolving[$onlyKey] = true;

        return $this->resolveValue($this->get($onlyKey), $resolving);
    }

    /**
     * Return unescaped variable.
     *
     * @param mixed $value The variable to unescape
     *
     * @return mixed
     */
    private function unescapeValue(mixed $value): mixed
    {
        if (is_string($value)) {
            return str_replace('%%', '%', $value);
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                $result[$k] = $this->unescapeValue($v);
            }

            return $result;
        }

        return $value;
    }

    /**
     * Return the value correspondent to a given key.
     *
     * @param int|string $propertyKey The key, in the configuration values array, to return the respective value
     *
     * @return mixed
     * @throws ConfigurationBuilderException when non-existent key in configuration array
     *
     */
    private function get(int|string $propertyKey): mixed
    {
        $value = $this->findValue($propertyKey, $this->config);
        if (!$value->valid()) {
            throw new ConfigurationBuilderException("Parameter '$propertyKey' not found in configuration file.");
        }

        return $value->current();
    }

    /**
     * Scan recursively an array to find a value of a given key.
     *
     * @param int|string $propertyKey The array key
     * @param array $config The array to scan
     *
     * @return \Generator The value or null if not found
     */
    private function findValue(int|string $propertyKey, array $config): Generator
    {
        foreach ($config as $key => $value) {
            if ($key === $propertyKey) {
                yield $value;
            }
            if (is_array($value)) {
                yield from $this->findValue($propertyKey, $value);
            }
        }
    }

    /**
     * Check if the parameter contains an environment variable and parse it
     *
     * @param string $value The value to parse
     *
     * @return string|null
     * @throws ConfigurationBuilderException if the environment variable is not set
     *
     */
    private function parseEnvironmentParams(string $value): ?string
    {
        // env.variable is an environment variable
        if (!str_starts_with($value, 'env.')) {
            return null;
        }
        $env = substr($value, 4);
        $envParam = getenv($env);

        if ($envParam === false) {
            throw new ConfigurationBuilderException("Environment variable '$env' is not defined.");
        }

        return $envParam;
    }
}
