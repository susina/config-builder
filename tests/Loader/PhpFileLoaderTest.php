<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Loader;

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\PhpFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class PhpFileLoaderTest extends TestCase
{
    protected PhpFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new PhpFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.php'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.php.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testPhpFileCanBeLoaded(): void
    {
        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
        vfsStream::newFile('parameters.php')->at($this->getRoot())->setContent($content);
        $test = $this->loader->load('parameters.php');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testPhpFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage('The file "inexistent.php" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.php');
    }

    public function testPhpFileHasInvalidContent(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("The configuration file 'nonvalid.php' has invalid content.");

        $content = <<<EOF
not php content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.php')->at($this->getRoot())->setContent($content);
        $this->loader->load('nonvalid.php');
    }

    public function testPhpFileIsEmpty(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("The configuration file 'empty.php' has invalid content.");

        vfsStream::newFile('empty.php')->at($this->getRoot())->setContent('');

        $this->loader->load('empty.php');
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testConfigFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.php" was expected to be readable.');

        $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;

        vfsStream::newFile('notreadable.php', 200)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load('notreadable.php');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }
}
