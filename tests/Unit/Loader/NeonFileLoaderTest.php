<?php

declare(strict_types=1);

/*
 * Copyright (c) Cristiano Cinotti
 *
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE file distributed
 * with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Unit\Loader;

use JsonException;
use Nette\Neon\Exception;
use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Loader\NeonFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class NeonFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new NeonFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.neon"));
        $this->assertTrue($this->loader->supports("foo.neon.dist"));
        $this->assertFalse($this->loader->supports('foo.bar'));
        $this->assertFalse($this->loader->supports('foo.bar.dist'));
    }

    public function testLoadNeonFile(): void
    {
        $content = <<<EOF
#test
foo: bar
bar: baz
EOF
        ;
        $expected = ['foo' => 'bar', 'bar' => 'baz'];

        vfsStream::newFile('parameters.neon')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.neon');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentNeonFile(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.neon" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.neon');
    }

    public function testLoadNeonFileWithInvalidContentThrowsException(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageIsOrContains("Unexpected 'only plain' on line 2 at column 1");

        $content = <<<EOF
not json content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.neon')->at($this->root)->setContent($content);
        $this->loader->load('nonvalid.neon');
    }

    public function testLoadEmptyNeonFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.neon')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.neon');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadableNeonFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(Exception::class);
        $this->expectExceptionMessageIsOrContains("Unable to read file 'vfs://root/notreadable.neon'");

        $content = <<<EOF
foo: bar
bar: baz
EOF
        ;
        vfsStream::newFile('notreadable.neon', 200)->at($this->root)->setContent($content);
        $actual = $this->loader->load('notreadable.neon');
    }
}
