<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Loader;

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\JsonFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class JsonFileLoaderTest extends TestCase
{
    protected JsonFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new JsonFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.json'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.json.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testJsonFileCanBeLoaded(): void
    {
        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        vfsStream::newFile('parameters.json')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('parameters.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testJsonFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage('The file "inexistent.json" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.json');
    }

    public function testJsonFileHasInvalidContent(): void
    {
        $this->expectException(\JsonException::class);
        $this->expectExceptionMessage('Syntax error');

        $content = <<<EOF
not json content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.json')->at($this->getRoot())->setContent($content);
        $this->loader->load('nonvalid.json');
    }

    public function testJsonFileIsEmpty(): void
    {
        vfsStream::newFile('empty.json')->at($this->getRoot())->setContent('');
        $actual = $this->loader->load('empty.json');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testJsonFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.json" was expected to be readable.');

        $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
        vfsStream::newFile('notreadable.json', 200)->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('notreadable.json');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
