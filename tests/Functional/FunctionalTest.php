<?php

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 *
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE file distributed
 * with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Functional;

use Dflydev\DotAccessData\Data;
use Generator;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\DataProvider;
use Susina\ConfigBuilder\ConfigurationBuilder;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationConstructor;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationInit;
use Susina\ConfigBuilder\Tests\Fixtures\Container;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfigurationWithFirstTag;
use Susina\ConfigBuilder\Tests\TestCase;

class FunctionalTest extends TestCase
{
    public static function parametersProvider(): Generator
    {
        yield [[
            'auto_connect' => true,
            'default_connection' => 'mysql',
            'connections' => [
                'mysql' => [
                    'host' => 'localhost',
                    'driver' => 'mysql',
                    'username' => 'user',
                    'password' => 'pass',
                ],
                'sqlite' => [
                    'host' => 'localhost',
                    'driver' => 'sqlite',
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ],
        ]];
    }

    public static function additionalParametersProvider(): Generator
    {
        yield [
            [
                'auto_connect' => true,
                'default_connection' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'driver' => 'mysql',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                    'sqlite' => [
                        'host' => 'localhost',
                        'driver' => 'sqlite',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                ],
            ],
            [
                'auto_connect' => true,
                'default_connection' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'driver' => 'mysql',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                    'sqlite' => [
                        'host' => 'localhost',
                        'driver' => 'sqlite',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                    'pgsql' => [
                        'host' => 'localhost',
                        'driver' => 'postgresql',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('parametersProvider')]
    public function testGetConfiguration(array $expected): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertSame($expected, $config->getParameters());
    }

    #[DataProvider('parametersProvider')]
    public function testGetDefaultConfigurationClass(array $expected): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setDefinition(new DatabaseConfiguration())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(Data::class, $config);
        $this->assertSame($expected, $config->export());
    }

    #[DataProvider('parametersProvider')]
    public function testInitMethod(array $expected): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationInit::class)
            ->setInitMethod('initialize')
            ->setDefinition(new DatabaseConfiguration())
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationInit::class, $config);
        $this->assertSame($expected, $config->getParameters());
    }

    #[DataProvider('parametersProvider')]
    public function testCacheParameters(array $expected): void
    {
        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($this->root->url() . '/cache_dir')
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertSame($expected, $config->getParameters());
        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertSame($expected, include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));
    }

    #[DataProvider('parametersProvider')]
    public function testLoadFromCache(array $expected): void
    {
        $builder = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($this->root->url() . '/cache_dir')
        ;

        //First call write the cache
        $builder->getConfiguration();
        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertSame($expected, include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));

        //Modify the cache
        file_put_contents(
            vfsStream::url('root/cache_dir/susina_config_builder.cache'),
            '<?php return ["Cache"];',
        );

        //second call load from cache
        $config = $builder->getConfiguration();
        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertSame(['Cache'], $config->getParameters());

        //New builder with same configuration loads from cache
        $builder2 = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($this->root->url() . '/cache_dir')
        ;
        $config2 = $builder2->getConfiguration();

        $this->assertNotSame($config, $config2);
        $this->assertInstanceOf(ConfigurationConstructor::class, $config2);
        $this->assertSame(['Cache'], $config2->getParameters());
    }

    public function testOmitDefinitionThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('No definition class. Please, set one via `setDefinition` method.');

        ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->getConfiguration();
    }

    #[DataProvider('additionalParametersProvider')]
    public function testChanginBuilderSetupRebuildsCache(array $expectedParams, array $additionalParams): void
    {
        $builder = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setCacheDirectory($this->root->url() . '/cache_dir');
        $config = $builder->getConfiguration();
        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertFileExists(vfsStream::url('root/cache_dir/susina_config_builder.cache'));
        $this->assertSame($expectedParams, include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));

        $builder->setFiles(['database_config.neon']);
        $config1 = $builder->getConfiguration();
        $this->assertSame($additionalParams, include(vfsStream::url('root/cache_dir/susina_config_builder.cache')));
    }

    public function testBeforeParams(): void
    {
        $expected = [
            'connections' => [
                'pgsql' => [
                    'host' => 'localhost',
                    'driver' => 'postgresql',
                    'username' => 'user',
                    'password' => 'pass',
                ],
                'mysql' => [
                    'host' => 'localhost',
                    'driver' => 'mysql',
                    'username' => 'user',
                    'password' => 'pass',
                ],
                'sqlite' => [
                    'host' => 'localhost',
                    'driver' => 'sqlite',
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ],
            'auto_connect' => true,
            'default_connection' => 'mysql',
        ];

        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setBeforeParams(['connections' => [
                'pgsql' => [
                    'host' => 'localhost',
                    'driver' => 'postgresql',
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ]])
            ->getConfiguration()
        ;

        $this->assertInstanceOf(ConfigurationConstructor::class, $config);
        $this->assertSame($expected, $config->getParameters());
    }

    #[DataProvider('parametersProvider')]
    public function testAfterParameters(array $expectedParams): void
    {
        $after = ['connections' => [
            'oracle' => [
                'host' => 'localhost',
                'driver' => 'oracle',
                'username' => 'user',
                'password' => 'pass',
            ],
        ]];
        $expected = array_merge_recursive($expectedParams, $after);

        $config = ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setConfigurationClass(ConfigurationConstructor::class)
            ->setDefinition(new DatabaseConfiguration())
            ->setAfterParams($after)
            ->getConfiguration()
        ;

        $this->assertInstanceof(ConfigurationConstructor::class, $config);
        $this->assertSame($expected, $config->getParameters());
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
            'connections.sqlite.password' => 'pass',
        ];

        $container = new Container();

        ConfigurationBuilder::create()
            ->addFile('database_config.yml')
            ->addDirectory($this->root->url())
            ->setDefinition(new DatabaseConfiguration())
            ->populateContainer($container, 'set')
        ;

        $this->assertSame($expected, $container->getParameters());
    }

    public function testKeepFirstXmlTag(): void
    {
        $expected = [
            'database' => [
                'name' => 'database_test',
                'auto_connect' => true,
                'default_connection' => 'mysql',
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'driver' => 'mysql',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                    'sqlite' => [
                        'host' => 'localhost',
                        'driver' => 'sqlite',
                        'username' => 'user',
                        'password' => 'pass',
                    ],
                ],
            ],
        ];

        $actual = ConfigurationBuilder::create()
            ->addFile('database_config.xml')
            ->addDirectory($this->root->url())
            ->setDefinition(new DatabaseConfigurationWithFirstTag())
            ->keepFirstXmlTag()
            ->getConfigurationArray()
        ;

        $this->assertSame($expected, $actual);
    }

    public function testReplaces(): void
    {
        $expected = [
            'auto_connect' => true,
            'default_connection' => 'mysql',
            'connections' => [
                'mysql' => [
                    'host' => 'localhost',
                    'driver' => 'mysql',
                    'username' => 'user',
                    'password' => 'pass',
                ],
                'sqlite' => [
                    'host' => 'vfs://root/database.sqlite',
                    'driver' => 'sqlite',
                    'username' => 'user',
                    'password' => 'pass',
                ],
            ],
        ];

        $actual = ConfigurationBuilder::create()
            ->addFile('replaces_config.yml')
            ->addDirectory($this->root->url())
            ->setDefinition(new DatabaseConfiguration())
            ->setReplaces(['kernel_dir' => 'vfs://root'])
            ->getConfigurationArray()
        ;

        $this->assertSame($actual, $expected);
    }
}
