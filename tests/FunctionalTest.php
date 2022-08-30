<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\ConfigurationBuilder;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationConstructor;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationInit;
use Susina\ConfigBuilder\Tests\Fixtures\Container;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;

class FunctionalTest extends TestCase
{
    public function testGetConfiguration(): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertEquals($this->getExpectedParameters(), $config->getParameters());
    }

    public function testGetConfigurationWithoutSetDefinitionThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('No definition class. Please, set one via `setDefinition` method.');
        ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->getConfiguration()
        ;
    }

    public function testGetConfigurationWithInitMethod(): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationInit::class)
            ->setInitMethod('initialize')
            ->setDefinition(new DatabaseConfiguration())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationInit::class, $config);
        $this->assertEquals($this->getExpectedParameters(), $config->getParameters());
    }

    public function testGetConfigurationByCache(): void
    {
        $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($cacheDir->url())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertEquals($this->getExpectedParameters(), $config->getParameters());
        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertEquals($this->getExpectedParameters(), include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));
    }

    public function testGetFromCache(): void
    {
        $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());

        $builder = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($cacheDir->url())
        ;

        //First call write the cache
        $builder->getConfiguration();

        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertEquals($this->getExpectedParameters(), include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));

        file_put_contents(
            vfsStream::url('root/cache_dir/susina_config_builder.cache'),
            '<?php return ["Cache"];'
        );

        $config = $builder->getConfiguration();
        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertEquals(['Cache'], $config->getParameters());
    }

    public function testChangingBuilderRebuildCache(): void
    {
        $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());

        $builder = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($cacheDir->url());

        $config = $builder->getConfiguration();

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertEquals($this->getExpectedParameters(), include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));

        $builder->setFiles(['database_config.neon']);

        $config1 = $builder->getConfiguration();

        $this->assertEquals(
            $this->getExpectedAdditionalParameters(),
            include(vfsStream::url('root/cache_dir/susina_config_builder.cache'))
        );
    }

    public function testBeforeParameters(): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setBeforeParams(['connections' => [
                'pgsql' => [
                    'host' => 'localhost',
                    'driver' => 'postgresql',
                    'username' => 'user',
                    'password' => 'pass'
                ]
            ]])
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertEquals($this->getExpectedAdditionalParameters(), $config->getParameters());
    }

    public function testAfterParameters(): void
    {
        $expected = ['connections' => [
            'oracle' => [
                'host' => 'localhost',
                'driver' => 'oracle',
                'username' => 'user',
                'password' => 'pass'
            ]
        ]];

        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setAfterParams($expected)
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertEquals(array_merge_recursive($this->getExpectedParameters(), $expected), $config->getParameters());
    }

    public function testPopulateContainer(): void
    {
        $expected = [
            'auto_connect' => true,
            'default_connection' => 'mysql',
            'connections.mysql.host' => 'localhost',
            'connections.mysql.driver' => 'mysql',
            'connections.mysql.username' => 'user',
            'connections.mysql.password' => 'pass',
            'connections.sqlite.host' => 'localhost',
            'connections.sqlite.driver' => 'sqlite',
            'connections.sqlite.username' => 'user',
            'connections.sqlite.password' => 'pass'
        ];

        $container = new Container();

        ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'Fixtures')
            ->setDefinition(new DatabaseConfiguration())
            ->populateContainer($container, 'set')
        ;

        $this->assertEquals($expected, $container->getParameters());
    }

    private function getExpectedParameters(): array
    {
        return [
            'auto_connect' => true,
            'default_connection' => 'mysql',
            'connections' => [
                'mysql' => [
                    'host' => 'localhost',
                    'driver' => 'mysql',
                    'username' => 'user',
                    'password' => 'pass'
                ],
                'sqlite' => [
                    'host' => 'localhost',
                    'driver' => 'sqlite',
                    'username' => 'user',
                    'password' => 'pass'
                ]
            ]
        ];
    }

    private function getExpectedAdditionalParameters(): array
    {
        return [
            'auto_connect' => true,
            'default_connection' => 'mysql',
            'connections' => [
                'mysql' => [
                    'host' => 'localhost',
                    'driver' => 'mysql',
                    'username' => 'user',
                    'password' => 'pass'
                ],
                'sqlite' => [
                    'host' => 'localhost',
                    'driver' => 'sqlite',
                    'username' => 'user',
                    'password' => 'pass'
                ],
                'pgsql' => [
                    'host' => 'localhost',
                    'driver' => 'postgresql',
                    'username' => 'user',
                    'password' => 'pass'
                ]
            ]
        ];
    }
}
