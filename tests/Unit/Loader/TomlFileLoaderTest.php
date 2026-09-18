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
use org\bovigo\vfs\vfsStream;
use PhpCollective\Toml\Exception\ParseException;
use PhpCollective\Toml\Exception\TomlException;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Loader\TomlFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class TomlFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new TomlFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.toml"));
        $this->assertTrue($this->loader->supports("foo.toml.dist"));
        $this->assertFalse($this->loader->supports('foo.yml'));
        $this->assertFalse($this->loader->supports('foo.xml.dist'));
    }

    public function testLoadTomlFile(): void
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $content = <<<EOF
foo = "bar"
bar = "baz"
EOF;
        vfsStream::newFile('parameters.toml')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.toml');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentTomlFileThrowsException(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.toml" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.toml');
    }

    public function testLoadTomlFileWithInvalidContentThrowsException(): void
    {
        $this->expectException(TomlException::class);
        $this->expectExceptionMessageIsOrContains('Expected =');

        $content = <<<EOF
not toml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.toml')->at($this->root)->setContent($content);
        $this->loader->load('nonvalid.toml');
    }

    public function testLoadEmptyTomlFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.toml')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.toml');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadableTomlFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(ParseException::class);
        $this->expectExceptionMessageIs("Cannot read file: vfs://root/notreadable.toml");

        $content = <<<EOF
foo = "bar"
bar = "baz"
EOF
        ;
        vfsStream::newFile('notreadable.toml', 200)->at($this->root)->setContent($content);
        $actual = $this->loader->load('notreadable.toml');
    }
}
