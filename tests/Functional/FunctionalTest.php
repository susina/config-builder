<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\ConfigurationBuilder;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationConstructor;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationInit;
use Susina\ConfigBuilder\Tests\Fixtures\Container;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfigurationWithFirstTag;

test('Get configuration', function (array $parameters) {
    $config = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->getConfiguration()
    ;

    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config->getParameters())->toBe($parameters);
})->with('Parameters');

test('Omit to set definition', function () {
    ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->getConfiguration()
    ;
})->throws(ConfigurationBuilderException::class, 'No definition class. Please, set one via `setDefinition` method.');

test('Init methood', function (array $expectParams) {
    $config = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationInit::class)
        ->setInitMethod('initialize')
        ->setDefinition(new DatabaseConfiguration())
        ->getConfiguration()
    ;
    expect($config)->toBeInstanceOf(ConfigurationInit::class)
        ->and($config->getParameters())->toBe($expectParams);
})->with('Parameters');

test('Cache parameters', function (array $expectParams) {
    $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());
    $config = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->setCacheDirectory($cacheDir->url())
        ->getConfiguration()
    ;

    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config->getParameters())->toBe($expectParams)
        ->and(vfsStream::url('root/cache_dir/susina_config_builder.cache'))->toBeFile()
        ->and(include(vfsStream::url('root/cache_dir/susina_config_builder.cache')))->toBe($expectParams);
})->with('Parameters');

test('Load from cache', function (array $expectParams) {
    $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());
    $builder = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->setCacheDirectory($cacheDir->url())
    ;

    //First call write the cache
    $builder->getConfiguration();
    expect(vfsStream::url('root/cache_dir/susina_config_builder.cache'))->toBeFile()
        ->and(include(vfsStream::url('root/cache_dir/susina_config_builder.cache')))->toBe($expectParams);

    //Modify the cache
    file_put_contents(
        vfsStream::url('root/cache_dir/susina_config_builder.cache'),
        '<?php return ["Cache"];'
    );

    //second call load from cache
    $config = $builder->getConfiguration();
    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config->getParameters())->toBe(['Cache']);

    //New builder with same configuration loads from cache
    $builder2 = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->setCacheDirectory($cacheDir->url())
    ;
    $config2 = $builder2->getConfiguration();
    expect($config2)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config2->getParameters())->toBe(['Cache']);
})->with('Parameters');

test('Changing builder setup rebuild cache', function (array $expectParams, array $additionalParams) {
    $cacheDir = vfsStream::newDirectory('cache_dir')->at($this->getRoot());
    $builder = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->setCacheDirectory($cacheDir->url());
    $config = $builder->getConfiguration();
    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and(vfsStream::url('root/cache_dir/susina_config_builder.cache'))->toBeFile()
        ->and(include(vfsStream::url('root/cache_dir/susina_config_builder.cache')))->toBe($expectParams);

    $builder->setFiles(['database_config.neon']);
    $config1 = $builder->getConfiguration();
    expect(include(vfsStream::url('root/cache_dir/susina_config_builder.cache')))->toBe($additionalParams);
})->with('AdditionalParameters');

test('Before params', function () {
    $expected = [
        'connections' => [
            'pgsql' => [
                'host' => 'localhost',
                'driver' => 'postgresql',
                'username' => 'user',
                'password' => 'pass'
            ],
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
        ],
        'auto_connect' => true,
        'default_connection' => 'mysql'
    ];

    $config = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
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

    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config->getParameters())->toBe($expected);
});

test('After parameters', function (array $expectedParams) {
    $after = ['connections' => [
        'oracle' => [
            'host' => 'localhost',
            'driver' => 'oracle',
            'username' => 'user',
            'password' => 'pass'
        ]
    ]];
    $expected = array_merge_recursive($expectedParams, $after);

    $config = ConfigurationBuilder::create()
        ->addFile('database_config.yml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfiguration())
        ->setAfterParams($after)
        ->getConfiguration()
    ;

    expect($config)->toBeInstanceOf(ConfigurationConstructor::class)
        ->and($config->getParameters())->toBe($expected);
})->with('Parameters');

test('Populate container', function () {
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
        ->addDirectory(fixtures_dir())
        ->setDefinition(new DatabaseConfiguration())
        ->populateContainer($container, 'set')
    ;

    expect($container->getParameters())->toBe($expected);
});

test('Keep first xml tag', function () {
    $config = ConfigurationBuilder::create()
        ->addFile('database_config.xml')
        ->addDirectory(fixtures_dir())
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->setDefinition(new DatabaseConfigurationWithFirstTag())
        ->keepFirstXmlTag()
        ->getConfigurationArray()
    ;

    expect($config)->toHaveKey('database')
        ->and($config)->toBe(
            [
                'database' => [
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
                    ],
                    'name' => 'database_test'
                ]
            ]
        );
});
