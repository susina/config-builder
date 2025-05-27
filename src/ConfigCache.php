<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder;

use Symfony\Component\Config\ConfigCache as BaseConfigCache;

/**
 * Specialized class to cache the built configuration.
 * It extends `Symfony\Component\Config\ConfigCache` class.
 *
 * @author Cristiano Cinotti <cristianocinotti@gmail.com>
 */
class ConfigCache extends BaseConfigCache
{
    private string $builderSerial;
    private string $serialFile;

    /**
     * @param string $file The cache file.
     * @param bool $debug start/stop debug environment.
     * @param ConfigurationBuilder $builder The object to save in cache.
     */
    public function __construct(string $file, bool $debug, ConfigurationBuilder $builder)
    {
        parent::__construct($file, $debug);

        $this->builderSerial = serialize($builder);
        $this->serialFile = dirname($this->getPath()) . DIRECTORY_SEPARATOR . 'config_builder.serial';
    }

    /**
     * Check if the cache is fresh.
     *
     * @return bool
     */
    public function isFresh(): bool
    {
        if (!file_exists($this->serialFile) || $this->builderSerial !== file_get_contents($this->serialFile)) {
            return false;
        }

        return parent::isFresh();
    }

    /**
     * Write the data into the cache.
     *
     * @param $content The content to write in cache.
     * @param $metadata An optional array of metadata.
     */
    public function write(string $content, ?array $metadata = null): void
    {
        parent::write($content, $metadata);

        file_put_contents($this->serialFile, $this->builderSerial);
    }
}
