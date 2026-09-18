<?php

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 *
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE file distributed
 * with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Unit;

use Dflydev\DotAccessData\Data;
use org\bovigo\vfs\vfsStream;
use ReflectionObject;
use SplFileInfo;
use Susina\ConfigBuilder\ConfigurationBuilder;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Tests\Fixtures\ConfigurationConstructor;
use Susina\ConfigBuilder\Tests\Fixtures\DatabaseConfiguration;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Finder\Finder;

class ConfigurationBuilderTest extends TestCase
{
    public function testAddFile(): void
    {
        $expected = ['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'];

        $builder = new ConfigurationBuilder();
        $builder->addFile($this->distFile, $this->configFile);
        $files = new ReflectionObject($builder)->getProperty('files')->getValue($builder);

        $this->assertCount(2, $files);
        $this->assertSame($expected, $files);
    }

    public function testAddSplFileinfo(): void
    {
        $expected = ['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'];

        $conf = new SplFileInfo($this->configFile);
        $dist = new SplFileInfo($this->distFile);
        $builder = new ConfigurationBuilder();
        $builder->addFile($dist, $conf);
        $files = new ReflectionObject($builder)->getProperty('files')->getValue($builder);

        $this->assertCount(2, $files);
        $this->assertSame($expected, $files);
    }

    public function testSetFiles(): void
    {
        $expected = ['vfs://root/config_builder.neon.dist', 'vfs://root/config_builder.neon'];

        $array[] = $this->distFile;
        $array[] = $this->configFile;

        $builder = new ConfigurationBuilder();
        $builder->setFiles($array);
        $files = new ReflectionObject($builder)->getProperty('files')->getValue($builder);

        $this->assertCount(2, $files);
        $this->assertSame($expected, $files);
    }

    public function testSetFilesPassingIterator(): void
    {
        $expected = ['vfs://root/config_builder.neon', 'vfs://root/config_builder.neon.dist'];

        $builder = new ConfigurationBuilder();
        $finder = new Finder();
        $finder->in($this->root->url())->name('config_builder.*')->files();
        $builder->setFiles($finder);
        $files = new ReflectionObject($builder)->getProperty('files')->getValue($builder);

        $this->assertCount(2, $files);
        $this->assertSame($expected, $files);

    }

    public function testSetFilesOverwriteExistingFiles(): void
    {
        $builder = new ConfigurationBuilder();

        $finder1 = new Finder();
        $finder1->in($this->root->url())->files();
        $builder->setFiles($finder1);
        $files1 = new ReflectionObject($builder)->getProperty('files')->getValue($builder);
        $this->assertCount(6, $files1);

        $finder2 = new Finder();
        $finder2->in($this->root->url())->name('config_builder.*')->files();
        $builder->setFiles($finder2);
        $files2 = new ReflectionObject($builder)->getProperty('files')->getValue($builder);
        $this->assertCount(2, $files2);
    }

    public function testAddDirectory(): void
    {
        $expected = ['vfs://root/cache_dir', 'vfs://root/test_dir'];

        $builder = new ConfigurationBuilder();
        $builder->addDirectory("{$this->root->url()}/cache_dir", "{$this->root->url()}/test_dir");
        $dirs = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);

        $this->assertCount(2, $dirs);
        $this->assertSame($expected, $dirs);
    }

    public function testAddDirectoriesPassingSplFileinfo(): void
    {
        $expected = ['vfs://root/cache_dir', 'vfs://root/test_dir'];

        $dir1 = new SplFileInfo("{$this->root->url()}/cache_dir");
        $dir2 = new SplFileInfo("{$this->root->url()}/test_dir");

        $builder = new ConfigurationBuilder();
        $builder->addDirectory($dir1, $dir2);
        $dirs = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);

        $this->assertCount(2, $dirs);
        $this->assertSame($expected, $dirs);
    }

    public function testAddNotExistentDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('Path "fake_dir" was expected to be a directory.');

        ConfigurationBuilder::create()->addDirectory('fake_dir');
    }

    public function testAddNotReadableDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('Path "vfs://root/test_config" was expected to be readable.');

        $dir = vfsStream::newDirectory('test_config', 200)->at($this->root);
        ConfigurationBuilder::create()->addDirectory($dir->url());
    }

    public function testSetDirectories(): void
    {
        $expected = ['vfs://root/cache_dir', 'vfs://root/test_dir'];

        $builder = new ConfigurationBuilder();
        $builder->setDirectories($expected);
        $dirs = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);

        $this->assertCount(2, $dirs);
        $this->assertSame($expected, $dirs);
    }

    public function testSetDirectoriesPassingIterator(): void
    {
        $expected = ['vfs://root/cache_dir', 'vfs://root/test_dir'];

        $builder = new ConfigurationBuilder();
        $finder = new Finder();
        $finder->in($this->root->url())->directories();
        $builder->setDirectories($finder);
        $dirs = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);

        $this->assertCount(2, $dirs);
        $this->assertSame($expected, $dirs);
    }

    public function testSetDirectoriesOverwriteExistingOnes(): void
    {
        vfsStream::newDirectory('first')->at($this->root);
        vfsStream::newDirectory('second')->at($this->root);

        $builder = new ConfigurationBuilder();
        $finder1 = new Finder();
        $finder1->in($this->root->url())->directories();
        $builder->setDirectories($finder1);
        $dirs1 = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);
        $this->assertCount(4, $dirs1);

        $finder2 = new Finder();
        $finder2->in($this->root->url())->name('*_dir')->directories();
        $builder->setDirectories($finder2);
        $dirs2 = new ReflectionObject($builder)->getProperty('directories')->getValue($builder);
        $this->assertCount(2, $dirs2);
    }

    public function testSetDefinition(): void
    {
        $def = new DatabaseConfiguration();
        $builder = ConfigurationBuilder::create()->setDefinition($def);
        $definition = new ReflectionObject($builder)->getProperty('definition')->getValue($builder);

        $this->assertInstanceOf(DatabaseConfiguration::class, $definition);
        $this->assertSame($definition, $def);
    }

    public function testSetConfigurationClass(): void
    {
        $builder = ConfigurationBuilder::create()
            ->setConfigurationClass(ConfigurationConstructor::class);
        $configClass = new ReflectionObject($builder)->getProperty('configurationClass')->getValue($builder);

        $this->assertSame($configClass, ConfigurationConstructor::class);
    }

    public function testInvalidConfigurationClassThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('Class "Susina\ConfigBuilder\Tests\FakeClass" does not exist.');

        ConfigurationBuilder::create()->setConfigurationClass('Susina\ConfigBuilder\Tests\FakeClass');
    }

    public function testSetCacheDirectory(): void
    {
        $builder = ConfigurationBuilder::create()->setCacheDirectory("{$this->root->url()}/cache_dir");
        $dir = new ReflectionObject($builder)->getProperty('cacheDirectory')->getValue($builder);

        $this->assertSame('vfs://root/cache_dir', $dir);
    }

    public function testNotExistentCacheDirectoryThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('Path "vfs://root/fake_dir" was expected to be a directory.');

        ConfigurationBuilder::create()->setCacheDirectory('vfs://root/fake_dir');
    }

    public function testNotReadableCacheDiresctoryThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped('Cannot set a directory not readable under Windows');
        }

        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('Path "vfs://root/config_cache" was expected to be readable.');

        $cacheDir = vfsStream::newDirectory('config_cache', 200)->at($this->root);
        $builder = ConfigurationBuilder::create()->setCacheDirectory($cacheDir->url());
    }

    public function testDefaultConfigurationClass(): void
    {
        $object = ConfigurationBuilder::create()->setDefinition(new DatabaseConfiguration())->getConfiguration();

        $this->assertInstanceOf(Data::class, $object);
    }

    public function testNoDefinitionObjectThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs('No definition class. Please, set one via `setDefinition` method.');

        ConfigurationBuilder::create()->getConfiguration();
    }
}
