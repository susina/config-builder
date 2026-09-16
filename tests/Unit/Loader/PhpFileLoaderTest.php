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
use Susina\ConfigBuilder\Loader\PhpFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;
use Symfony\Component\Config\FileLocator;

class PhpFileLoaderTest extends TestCase
{
    private $loader;

    public function setUp(): void
    {
        $this->loader = new PhpFileLoader(new FileLocator($this->root->url()));
    }

    public function testSupportedExtensions(): void
    {
        $this->assertTrue($this->loader->supports("foo.php"));
        $this->assertTrue($this->loader->supports("foo.php.dist"));
        $this->assertFalse($this->loader->supports('foo.bar'));
        $this->assertFalse($this->loader->supports('foo.bar.dist'));
    }

    public function testLoadPhpFile(): void
    {
        $expected = [
            'foo' => 'bar',
            'bar' => 'baz',
        ];

        $content = "<?php return ['foo' => 'bar', 'bar' => 'baz'];";

        vfsStream::newFile('parameters.php')->at($this->root)->setContent($content);
        $actual = $this->loader->load('parameters.php');

        $this->assertSame($expected, $actual);
    }

    public function testLoadNotExistentPhpFileThrowsException(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessageIs('The file "inexistent.php" does not exist (in: "vfs://root").');

        $this->loader->load('inexistent.php');
    }

    public function testLoadPhpFileWithInvalidContentThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIsOrContains("The configuration file 'nonvalid.php' has invalid content.");

        $content = <<<EOF
not php content
only plain
text
EOF;
        vfsStream::newFile('nonvalid.php')->at($this->root)->setContent($content);
        $this->loader->load('nonvalid.php');
    }

    public function testLoadEmptyPhpFileReturnsEmptyArray(): void
    {
        vfsStream::newFile('empty.php')->at($this->root)->setContent('');
        $actual = $this->loader->load('empty.php');

        $this->assertIsArray($actual);
        $this->assertEmpty($actual);
    }

    public function testLoadNotReadablePhpFileThrowsException(): void
    {
        if ($this->runnungOnWindows()) {
            $this->markTestSkipped("Changing file permission doesn't work on Windows");
        }

        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessageIs("The configuration file 'notreadable.php' does not exist or is not readable.");

        $content = "<?php return array('foo' => 'bar', 'bar' => 'baz');";
        vfsStream::newFile('notreadable.php', 200)->at($this->root)->setContent($content);

        $actual = $this->loader->load('notreadable.php');
    }
}
