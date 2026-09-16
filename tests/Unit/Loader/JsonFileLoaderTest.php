<?php

declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, released under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Unit\Loader;

use JsonException;
use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Loader\JsonFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class JsonFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new JsonFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.json"));
        $this->assertTrue($this->loader->supports("foo.json.dist"));
        $this->assertFalse($this->loader->supports('foo.bar'));
        $this->assertFalse($this->loader->supports('foo.bar.dist'));
    }

    public function testLoadJsonFile(): void
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $content = <<<EOF
{
"foo": "bar",
"bar": "baz"
}
EOF;
        vfsStream::newFile('parameters.json')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.json');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentJsonFileThrowsException(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.json" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.json');
    }

    public function testLoadJsonFileWithInvalidContentThrowsException(): void
    {
        $this->expectException(JsonException::class);
        $this->expectExceptionMessageIsOrContains('Syntax error');

        $content = <<<EOF
not json content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.json')->at($this->root)->setContent($content);
        $this->loader->load('nonvalid.json');
    }

    public function testLoadEmptyJsonFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.json')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.json');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadableJsonFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs("The configuration file 'notreadable.json' is not readable.");

        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF
        ;
        vfsStream::newFile('notreadable.json', 200)->at($this->root)->setContent($content);
        $actual = $this->loader->load('notreadable.json');
    }
}
