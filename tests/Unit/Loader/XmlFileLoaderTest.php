<?php

declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Unit\Loader;

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Loader\XmlFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Susina\XmlToArray\Exception\ConverterException;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class XmlFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new XmlFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.xml"));
        $this->assertTrue($this->loader->supports("foo.xml.dist"));
        $this->assertFalse($this->loader->supports('foo.bar'));
        $this->assertFalse($this->loader->supports('foo.bar.dist'));
    }

    public function testLoadXmlFile(): void
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        vfsStream::newFile('parameters.xml')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.xml');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentXmlFileThrowsException(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.xml" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.xml');
    }

    public function testLoadXmlFileWithInvalidContentThrowsException(): void
    {
        $this->expectException(ConverterException::class);
        $this->expectExceptionMessageIsOrContains("An error occurred while parsing XML string:");

        $content = <<<EOF
not xml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.xml')->at($this->root)->setContent($content);
        $this->loader->load('nonvalid.xml');
    }

    public function testLoadEmptyXmlFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.xml')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.xml');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadableXmlFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs("The configuration file 'notreadable.xml' is not readable.");

        $content = <<<XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
        vfsStream::newFile('notreadable.xml', 200)->at($this->root)->setContent($content);
        $actual = $this->loader->load('notreadable.xml');
    }
}
