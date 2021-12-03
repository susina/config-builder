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
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;
use Symfony\Component\Finder\Finder;

class ConfigurationBuilderTest extends TestCase
{
    use ReflectionTrait;

    public function testAddFile(): void
    {
        $builder = new ConfigurationBuilder();
        $builder->addFile($this->getConfigurationDistFile()->url(), $this->getConfigurationFile()->url());

        $files = $this->getProperty($builder, 'files');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'], $files);
    }

    public function testAddFilePassingSplFileInfo(): void
    {
        $builder = new ConfigurationBuilder();
        $this->populate();
        $finder = new Finder();
        $finder->in($this->getRoot()->url())->files();

        foreach ($finder as $file) {
            $builder->addFile($file);
        }

        $files = $this->getProperty($builder, 'files');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'], $files);
    }

    public function testSetFiles(): void
    {
        $builder = new ConfigurationBuilder();
        $array[] = $this->getConfigurationDistFile()->url();
        $array[] = $this->getConfigurationFile()->url();

        $builder->setFiles($array);

        $files = $this->getProperty($builder, 'files');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'], $files);
    }

    public function testSetFilesPassingIterator(): void
    {
        $builder = new ConfigurationBuilder();
        $this->populate();
        $finder = new Finder();
        $finder->in($this->getRoot()->url())->files();
        $builder->setFiles($finder);

        $files = $this->getProperty($builder, 'files');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'], $files);
    }

    public function testAddDirectory(): void
    {
        $builder = new ConfigurationBuilder();
        $builder->addDirectory(getcwd(), sys_get_temp_dir());

        $dirs = $this->getProperty($builder, 'directories');

        $this->assertCount(2, $dirs);
        $this->assertEquals([getcwd(), sys_get_temp_dir()], $dirs);
    }

    public function testAddDirectoryPassingSplFileInfo(): void
    {
        vfsStream::newDirectory('config')->at($this->getRoot());
        vfsStream::newDirectory('test_config')->at($this->getRoot());

        $builder = new ConfigurationBuilder();
        $finder = new Finder();
        $finder->in($this->getRoot()->url())->directories();

        foreach ($finder as $dir) {
            $builder->addDirectory($dir);
        }

        $files = $this->getProperty($builder, 'directories');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config', 'vfs://root/test_config'], $files);
    }

    public function testAddNonExistentDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "fake_dir" was expected to be a directory.');

        $builder = new ConfigurationBuilder();
        $builder->addDirectory('fake_dir');
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testAddNotReadableDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/test_config" was expected to be readable.');

        $dir = vfsStream::newDirectory('test_config', 200)->at($this->getRoot());
        $builder = new ConfigurationBuilder();
        $builder->addDirectory($dir->url());

        $this->assertEquals(['vfs://root/test_config'], $this->getProperty($builder, 'directories'));
    }

    public function testSetDirectories(): void
    {
        $builder = new ConfigurationBuilder();
        $builder->setDirectories([getcwd(), sys_get_temp_dir()]);

        $dirs = $this->getProperty($builder, 'directories');

        $this->assertCount(2, $dirs);
        $this->assertEquals([getcwd(), sys_get_temp_dir()], $dirs);
    }

    public function testSetDirectoriesPassingIterator(): void
    {
        vfsStream::newDirectory('config')->at($this->getRoot());
        vfsStream::newDirectory('test_config')->at($this->getRoot());

        $builder = new ConfigurationBuilder();
        $finder = new Finder();
        $finder->in($this->getRoot()->url())->directories();
        $builder->setDirectories($finder);

        $files = $this->getProperty($builder, 'directories');

        $this->assertCount(2, $files);
        $this->assertEquals(['vfs://root/config', 'vfs://root/test_config'], $files);
    }

    public function testSetDefinition(): void
    {
        $def = new DatabaseConfiguration();
        $builder = ConfigurationBuilder::create()->setDefinition($def);
        $definition = $this->getProperty($builder, 'definition');

        $this->assertInstanceOf(DatabaseConfiguration::class, $definition);
        $this->assertSame($def, $definition);
    }

    public function testSetConfigurationClass(): void
    {
        $builder = ConfigurationBuilder::create()
            ->setConfigurationClass(ConfigurationConstructor::class)
        ;

        $this->assertEquals(ConfigurationConstructor::class, $this->getProperty($builder, 'configurationClass'));
    }

    public function testSetConfigurationClassWithInvalidClassThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Class "Susina\ConfigBuilder\Tests\FakeClass" does not exist.');

        ConfigurationBuilder::create()->setConfigurationClass('Susina\ConfigBuilder\Tests\FakeClass');
    }

    public function testSetCacheDirectory(): void
    {
        $cacheDir = vfsStream::newDirectory('config_cache')->at($this->getRoot());
        $builder = ConfigurationBuilder::create()->setCacheDirectory($cacheDir->url());

        $this->assertEquals($cacheDir->url(), $this->getProperty($builder, 'cacheDirectory'));
    }

    public function testNotExistentCacheDirectoryThrowsException(): void
    {
        $cacheDir = __DIR__ . DIRECTORY_SEPARATOR . 'fake_dir';
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage("Path \"$cacheDir\" was expected to be a directory.");

        ConfigurationBuilder::create()->setCacheDirectory($cacheDir);
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testNotReadableCacheDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/config_cache" was expected to be readable.');

        $cacheDir = vfsStream::newDirectory('config_cache', 200)->at($this->getRoot());
        $builder = ConfigurationBuilder::create()->setCacheDirectory($cacheDir->url());

        $this->assertEquals($cacheDir->url(), $this->getProperty($builder, 'cacheDirectory'));
    }
}
