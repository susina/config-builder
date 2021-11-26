<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Loader;

use Nette\Neon\Exception;
use Nette\Neon\Neon;
use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\NeonFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class NeonFileLoaderTest extends TestCase
{
    protected NeonFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new NeonFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.neon'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.neon.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.bar.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testNeonFileCanBeLoaded(): void
    {
        $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
        vfsStream::newFile('parameters.neon')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load('parameters.neon');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testNeonFileDoesNotExist()
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage('The file "inexistent.neon" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.neon');
    }

    public function testNeonFileHasInvalidContent()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Unexpected 'only plain' on line 2, column 1.");

        $content = <<<EOF
not neon content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.neon')->at($this->getRoot())->setContent($content);
        $this->loader->load('nonvalid.neon');
    }

    public function testNeonFileIsEmpty()
    {
        vfsStream::newFile('empty.neon')->at($this->getRoot())->setContent('');

        $actual = $this->loader->load('empty.neon');

        $this->assertEquals([], $actual);
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testNeonFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.neon" was expected to be readable.');

        $content = <<<EOF
foo: bar
bar: baz
EOF;
        vfsStream::newFile('notreadable.neon', 200)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load('notreadable.neon');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
