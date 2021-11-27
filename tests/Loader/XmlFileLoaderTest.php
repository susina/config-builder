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
use Susina\ConfigBuilder\Exception\XmlParseBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\XmlFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class XmlFileLoaderTest extends TestCase
{
    protected XmlFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new XmlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.xml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.xml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testXmlFileCanBeLoaded(): void
    {
        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        vfsStream::newFile('parameters.xml')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load('parameters.xml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testXmlFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage('The file "inexistent.xml" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.xml');
    }

    public function testXmlFileHasInvalidContent(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Invalid xml content');

        $content = <<<EOF
not xml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.xml')->at($this->getRoot())->setContent($content);

        @$this->loader->load('nonvalid.xml');
    }

    public function testXmlFileIsEmpty()
    {
        vfsStream::newFile('empty.xml')->at($this->getRoot())->setContent('');

        $actual = $this->loader->load('empty.xml');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testXmlFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.xml" was expected to be readable.');

        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;

        vfsStream::newFile('notreadable.xml', 200)->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('notreadable.xml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
