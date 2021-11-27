<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Loader;

use org\bovigo\vfs\vfsStream;
use Susina\CodingStandard\Config;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\IniFileLoader;
use Susina\ConfigBuilder\Tests\TestCase;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

class IniFileLoaderTest extends TestCase
{
    protected IniFileLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new IniFileLoader(new FileLocator($this->getRoot()->url()));
    }

    public function testSupports(): void
    {
        $this->assertTrue($this->loader->supports('foo.ini'), '->supports() returns true if the resource is loadable');
        $this->assertTrue($this->loader->supports('foo.ini.dist'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo'), '->supports() returns true if the resource is loadable');
        $this->assertFalse($this->loader->supports('foo.foo.dist'), '->supports() returns true if the resource is loadable');
    }

    public function testIniFileCanBeLoaded(): void
    {
        $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
        $test = $this->loader->load('parameters.ini');
        $this->assertEquals('bar', $test['foo']);
        $this->assertEquals('baz', $test['bar']);
    }

    public function testIniFileDoesNotExist(): void
    {
        $this->expectException(FileLocatorFileNotFoundException::class);
        $this->expectExceptionMessage("The file \"inexistent.ini\" does not exist (in");

        $this->loader->load('inexistent.ini');
    }

    public function testIniFileHasInvalidContent(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage("The configuration file 'vfs://root/nonvalid.ini' has invalid content.");

        $content = <<<EOF
{not ini content}
only plain
text
EOF;
        vfsStream::newFile('nonvalid.ini')->at($this->getRoot())->setContent($content);
        @$this->loader->load('nonvalid.ini');
    }

    public function testIniFileIsEmpty(): void
    {
        vfsStream::newFile('empty.ini')->at($this->getRoot())->setContent('');
        $actual = $this->loader->load('empty.ini');

        $this->assertEquals([], $actual);
    }

    public function testWithSections(): void
    {
        $content = <<<EOF
[Cartoons]
Dog          = Pluto
Donald[]     = Huey
Donald[]     = Dewey
Donald[]     = Louie
Mickey[love] = Minnie
EOF;
        vfsStream::newFile('section.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('section.ini');

        $this->assertEquals('Pluto', $actual['Cartoons']['Dog']);
        $this->assertEquals('Huey', $actual['Cartoons']['Donald'][0]);
        $this->assertEquals('Dewey', $actual['Cartoons']['Donald'][1]);
        $this->assertEquals('Louie', $actual['Cartoons']['Donald'][2]);
        $this->assertEquals('Minnie', $actual['Cartoons']['Mickey']['love']);
    }

    public function testNestedSections(): void
    {
        $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
        vfsStream::newFile('nested.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('nested.ini');

        $this->assertEquals('foobar', $actual['foo']['bar']['baz']);
        $this->assertEquals('foobabar', $actual['foo']['bar']['babaz']);
        $this->assertEquals('blafoo', $actual['bla']['foo']);
        $this->assertEquals('blabar', $actual['bla']['bar']);
    }

    public function testMixedNestedSections(): void
    {
        $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
        vfsStream::newFile('mixnested.ini')->at($this->getRoot())->setContent($content);
        $actual = $this->loader->load('mixnested.ini');

        $this->assertEquals('foobar', $actual['bla']['foo']['bar']);
        $this->assertEquals('foobarArray', $actual['bla']['foobar'][0]);
        $this->assertEquals('foobaz1', $actual['bla']['foo']['baz'][0]);
        $this->assertEquals('foobaz2', $actual['bla']['foo']['baz'][1]);
    }

    public function testInvalidSectionThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage("Invalid key \".foo\"");

        $content = <<<EOF
.foo = bar
bar = baz
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
        $this->loader->load('parameters.ini');
    }

    public function testInvalidParamThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage("Invalid key \"foo.\"");

        $content = <<<EOF
foo. = bar
bar = baz
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testAlreadyExistentParamThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage("Cannot create sub-key for \"foo\", as key already exists");

        $content = <<<EOF
foo = bar
foo.babar = baz
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);

        $test = $this->loader->load('parameters.ini');
    }

    public function testSectionZero(): void
    {
        $content = <<<EOF
foo = bar
0.babar = baz
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);

        $this->assertEquals(['0' => ['foo' => 'bar', 'babar' => 'baz']], $this->loader->load('parameters.ini'));
    }

    /**
     * @requires OS ^(?!Win.*)
     */
    public function testIniFileNotReadableThrowsException(): void
    {
        $this->expectException(ConfigurationBuilderException::class);
        $this->expectExceptionMessage('Path "vfs://root/notreadable.ini" was expected to be readable.');

        $content = <<<EOF
foo = bar
bar = baz
EOF;
        vfsStream::newFile('notreadable.ini', 200)->at($this->getRoot())->setContent($content);

        $actual = $this->loader->load('notreadable.ini');
        $this->assertEquals('bar', $actual['foo']);
        $this->assertEquals('baz', $actual['bar']);
    }

    public function testTransformStringIntoBool(): void
    {
        $content = <<<EOF
;test ini
foo = true
bar = FALSE
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
        $test = $this->loader->load('parameters.ini');
        $this->assertIsBool($test['foo']);
        $this->assertIsBool($test['bar']);
        $this->assertTrue($test['foo'], '`true` string is converted into boolean');
        $this->assertFalse($test['bar'], 'String to boolean conversion is case insensitive');
    }

    public function testTransformStringIntoIntOrFloat(): void
    {
        $content = <<<EOF
;test ini
foo = 1
bar = 10.42
EOF;
        vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
        $test = $this->loader->load('parameters.ini');
        $this->assertIsInt($test['foo']);
        $this->assertIsFloat($test['bar']);
        $this->assertEquals(1, $test['foo'], 'Numeric string are converted into integer');
        $this->assertEquals(10.42, $test['bar']);
    }
}
