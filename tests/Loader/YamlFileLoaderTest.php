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
use Susina\ConfigBuilder\Loader\YamlFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    protected YamlFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new YamlFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.yaml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yaml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.yml.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testYamlFileCanBeLoaded(): void
    {
        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        vfsStream::newFile('parameters.yaml')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load('parameters.yaml');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testYamlFileDoesNotExist()
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage('The file "inexistent.yaml" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.yaml');
    }

    public function testYamlFileHasInvalidContent()
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Unable to parse the configuration file: wrong yaml content.');

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.yaml')->at($this->getRoot())->setContent($content);
        $this->loader->load('nonvalid.yaml');
    }

    public function testYamlFileIsEmpty()
    {
        vfsStream::newFile('empty.yaml')->at($this->getRoot())->setContent('');

        $actual = $this->loader->load('empty.yaml');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testYamlFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.yaml" was expected to be readable.');

        $content = <<<EOF
foo: bar
bar: baz
EOF;
        vfsStream::newFile('notreadable.yaml', 200)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load('notreadable.yaml');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
