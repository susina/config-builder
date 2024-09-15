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
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;
use Susina\ConfigBuilder\Tests\ReflectionTrait;
use Symfony\Component\Finder\Finder;

uses(ReflectionTrait::class);

test('Add file', function () {
    $builder = new ConfigurationBuilder();
    $builder->addFile($this->getConfigurationDistFile()->url(), $this->getConfigurationFile()->url());
    $files = $this->getProperty($builder, 'files');

    expect($files)->toHaveCount(2)
        ->and($files)->toBe(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon']);
});

test('Add SplFileinfo', function () {
    $builder = new ConfigurationBuilder();
    $this->populate();
    $finder = new Finder();
    $finder->in($this->getRoot()->url())->files();
    foreach ($finder as $file) {
        $builder->addFile($file);
    }
    $files = $this->getProperty($builder, 'files');

    expect($files)->toHaveCount(2)
        ->and($files)->toBe([
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config_builder.neon.dist',
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config_builder.neon'
        ]);
});

test('Set files', function () {
    $builder = new ConfigurationBuilder();
    $array[] = $this->getConfigurationDistFile()->url();
    $array[] = $this->getConfigurationFile()->url();
    $builder->setFiles($array);
    $files = $this->getProperty($builder, 'files');

    expect($files)->toHaveCount(2)
        ->and($files)->toBe(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon']);
});

test('Set file passing iterator', function () {
    $builder = new ConfigurationBuilder();
    $this->populate();
    $finder = new Finder();
    $finder->in($this->getRoot()->url())->files();
    $builder->setFiles($finder);
    $files = $this->getProperty($builder, 'files');

    expect($files)->toHaveCount(2)
        ->and($files)->toBe(
            [
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config_builder.neon.dist',
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config_builder.neon'
            ]
        );
});

test('Add directory', function () {
    $builder = new ConfigurationBuilder();
    $builder->addDirectory(getcwd(), sys_get_temp_dir());
    $dirs = $this->getProperty($builder, 'directories');

    expect($dirs)->toHaveCount(2)
        ->and($dirs)->toBe([getcwd(), sys_get_temp_dir()]);
});

test('Add directories passing SplFileinfo', function () {
    vfsStream::newDirectory('config')->at($this->getRoot());
    vfsStream::newDirectory('test_config')->at($this->getRoot());

    $builder = new ConfigurationBuilder();
    $finder = new Finder();
    $finder->in($this->getRoot()->url())->directories();

    foreach ($finder as $dir) {
        $builder->addDirectory($dir);
    }
    $files = $this->getProperty($builder, 'directories');

    expect($files)->toHaveCount(2)
        ->and($files)->toBe(
            [
                $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config',
                $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'test_config'
            ]
        );
});

test('Add not existent directory', function () {
    ConfigurationBuilder::create()->addDirectory('fake_dir');
})->throws(ConfigurationBuilderException::class, 'Path "fake_dir" was expected to be a directory.');

test('Add not readable directory', function () {
    $dir = vfsStream::newDirectory('test_config', 200)->at($this->getRoot());
    ConfigurationBuilder::create()->addDirectory($dir->url());
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/test_config" was expected to be readable.')
    ->skipOnWindows();

test('Set directories', function () {
    $builder = new ConfigurationBuilder();
    $builder->setDirectories([getcwd(), sys_get_temp_dir()]);
    $dirs = $this->getProperty($builder, 'directories');

    expect($dirs)->toHaveCount(2)
        ->and($dirs)->toBe([getcwd(), sys_get_temp_dir()]);
});

test('Set directories passing iterator', function () {
    vfsStream::newDirectory('config')->at($this->getRoot());
    vfsStream::newDirectory('test_config')->at($this->getRoot());

    $builder = new ConfigurationBuilder();
    $finder = new Finder();
    $finder->in($this->getRoot()->url())->directories();
    $builder->setDirectories($finder);
    $dirs = $this->getProperty($builder, 'directories');

    expect($dirs)->toHaveCount(2)
        ->and($dirs)->toBe([
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'config',
            $this->getRoot()->url() . DIRECTORY_SEPARATOR . 'test_config'
        ]);
});

test('Set definition', function () {
    $def = new DatabaseConfiguration();
    $builder = ConfigurationBuilder::create()->setDefinition($def);
    $definition = $this->getProperty($builder, 'definition');

    expect($definition)->toBeInstanceOf(DatabaseConfiguration::class)
        ->and($definition)->toBe($def);
});

test('Set configuration class', function () {
    $builder = ConfigurationBuilder::create()
        ->setConfigurationClass(ConfigurationConstructor::class);
    $configClass = $this->getProperty($builder, 'configurationClass');

    expect($configClass)->toBe(ConfigurationConstructor::class);
});

test('Invalid configuration class', function () {
    ConfigurationBuilder::create()->setConfigurationClass('Susina\ConfigBuilder\Tests\FakeClass');
})->throws(ConfigurationBuilderException::class, 'Class "Susina\ConfigBuilder\Tests\FakeClass" does not exist.');

test('Set cache directory', function () {
    $cacheDir = vfsStream::newDirectory('config_cache')->at($this->getRoot());
    $builder = ConfigurationBuilder::create()->setCacheDirectory($cacheDir->url());

    expect($this->getProperty($builder, 'cacheDirectory'))->toBe($cacheDir->url());
});

test('Not existent cache directory', function () {
    ConfigurationBuilder::create()->setCacheDirectory(__DIR__ . DIRECTORY_SEPARATOR . 'fake_dir');
})->throws(
    ConfigurationBuilderException::class,
    'Path "' . __DIR__ . DIRECTORY_SEPARATOR . 'fake_dir" was expected to be a directory.'
);

test('Not readable cache directory', function () {
    $cacheDir = vfsStream::newDirectory('config_cache', 200)->at($this->getRoot());
    $builder = ConfigurationBuilder::create()->setCacheDirectory($cacheDir->url());
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/config_cache" was expected to be readable.')
    ->skipOnWindows();

test('Test forgot configuration class', function () {
    ConfigurationBuilder::create()->getConfiguration();
})->throws(ConfigurationBuilderException::class, 'No configuration class to instantiate. Please, set it via `setConfigurationClass` method.');

test('Forgot definition object', function () {
    ConfigurationBuilder::create()
        ->setConfigurationClass(ConfigurationConstructor::class)
        ->getConfiguration()
    ;
})->throws(ConfigurationBuilderException::class, 'No definition class. Please, set one via `setDefinition` method.');
