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
use Susina\ConfigBuilder\Loader\YamlFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Exception\ParseException;

class YamlFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new YamlFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.yml"));
        $this->assertTrue($this->loader->supports("foo.yml.dist"));
        $this->assertTrue($this->loader->supports("foo.yaml"));
        $this->assertTrue($this->loader->supports("foo.yaml.dist"));
        $this->assertFalse($this->loader->supports('foo.bar'));
        $this->assertFalse($this->loader->supports('foo.bar.dist'));
    }

    public function testLoadYamlFile(): void
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        vfsStream::newFile('parameters.yaml')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.yaml');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentYamlFileThrowsException(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.yml" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.yml');
    }

    public function testInvalidYamlContentThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs("Invalid YAML content: configuration file 'invalid.yaml'.");

        $content = <<<EOF
not yaml content
only plain
text
EOF;
        vfsStream::newFile('invalid.yaml')->at($this->root)->setContent($content);
        $this->loader->load('invalid.yaml');
    }

    public function testLoadEmptyYamlFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.yaml')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.yaml');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadableJsonFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(ParseException::class);
        $this->expectExceptionMessageIs('File "vfs://root/notreadable.yaml" cannot be read.');

        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        vfsStream::newFile('notreadable.yaml', 200)->at($this->root)->setContent($content);
        $actual = $this->loader->load('notreadable.yaml');
    }
}
