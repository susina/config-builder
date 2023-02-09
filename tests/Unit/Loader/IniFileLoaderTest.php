<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\IniFileLoader;
use Susina\ConfigBuilder\Tests\VfsTrait;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new IniFileLoader(new FileLocator($this->getRoot()->url()));
});

test('Supported `ini` extensions', function () {
    expect($this->loader->supports('foo.ini'))->toBeTrue()
        ->and($this->loader->supports('foo.ini.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.foo'))->toBeFalse()
        ->and($this->loader->supports('foo.foo.dist'))->toBeFalse()
    ;
});

test('Load ini file', function () {
    $content = <<<EOF
;test ini
foo = bar
bar = baz
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.ini');

    expect($test['foo'])->toBe('bar')
        ->and($test['bar'])->toBe('baz')
    ;
});

test('Ini file does not exist', function () {
    $this->loader->load('inexistent.ini');
})->throws(FileLocatorFileNotFoundException::class, "The file \"inexistent.ini\" does not exist (in");

test('Ini invalid content', function () {
    $content = <<<EOF
{not ini content}
only plain
text
EOF;
    vfsStream::newFile('nonvalid.ini')->at($this->getRoot())->setContent($content);
    @$this->loader->load('nonvalid.ini');
})->throws(ConfigurationBuilderException::class, "The configuration file 'vfs://root" . DIRECTORY_SEPARATOR . "nonvalid.ini' has invalid content.");

test('Empty ini file', function () {
    vfsStream::newFile('empty.ini')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.ini');

    expect($actual)->toBe([]);
});

test('Ini sections', function () {
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

    expect($actual['Cartoons']['Dog'])->toBe('Pluto')
        ->and($actual['Cartoons']['Donald'][0])->toBe('Huey')
        ->and($actual['Cartoons']['Donald'][1])->toBe('Dewey')
        ->and($actual['Cartoons']['Donald'][2])->toBe('Louie')
        ->and($actual['Cartoons']['Mickey']['love'])->toBe('Minnie')
    ;
});

test('Nested ini sections', function () {
    $content = <<<EOF
foo.bar.baz   = foobar
foo.bar.babaz = foobabar
bla.foo       = blafoo
bla.bar       = blabar
EOF;
    vfsStream::newFile('nested.ini')->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('nested.ini');

    expect($actual['foo']['bar']['baz'])->toBe('foobar')
        ->and($actual['foo']['bar']['babaz'])->toBe('foobabar')
        ->and($actual['bla']['foo'])->toBe('blafoo')
        ->and($actual['bla']['bar'])->toBe('blabar')
    ;
});

test('Mixed nested ini sections', function () {
    $content = <<<EOF
bla.foo.bar = foobar
bla.foobar[] = foobarArray
bla.foo.baz[] = foobaz1
bla.foo.baz[] = foobaz2

EOF;
    vfsStream::newFile('mixnested.ini')->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('mixnested.ini');

    expect($actual['bla']['foo']['bar'])->toBe('foobar')
        ->and($actual['bla']['foobar'][0])->toBe('foobarArray')
        ->and($actual['bla']['foo']['baz'][0])->toBe('foobaz1')
        ->and($actual['bla']['foo']['baz'][1])->toBe('foobaz2')
    ;
});

test('Invalid section', function () {
    $content = <<<EOF
.foo = bar
bar = baz
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $this->loader->load('parameters.ini');
})->throws(ConfigurationBuilderException::class, "Invalid key \".foo\"");

test('Inavlid parameter', function () {
    $content = <<<EOF
foo. = bar
bar = baz
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.ini');
})->throws(ConfigurationBuilderException::class, "Invalid key \"foo.\"");

test('Param already defined', function () {
    $content = <<<EOF
foo = bar
foo.babar = baz
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.ini');
})->throws(ConfigurationBuilderException::class, "Cannot create sub-key for \"foo\", as key already exists");

test('Section 0', function () {
    $content = <<<EOF
foo = bar
0.babar = baz
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);

    expect($this->loader->load('parameters.ini'))->toBe(['0' => ['foo' => 'bar', 'babar' => 'baz']]);
});

test('Ini file not readable', function () {
    $content = <<<EOF
foo = bar
bar = baz
EOF;
    vfsStream::newFile('notreadable.ini', 200)->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('notreadable.ini');
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.ini" was expected to be readable.')
    ->skip(running_on_windows(), "Not executable on Windows");

test('Transform string into boolean', function () {
    $content = <<<EOF
;test ini
foo = true
bar = FALSE
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.ini');

    expect($test['foo'])->toBeBool()
        ->and($test['bar'])->toBeBool()
        ->and($test['foo'])->toBeTrue()
        ->and($test['bar'])->toBeFalse()
    ;
});

test('Tranform string into integer or float', function () {
    $content = <<<EOF
;test ini
foo = 1
bar = 10.42
EOF;
    vfsStream::newFile('parameters.ini')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.ini');

    expect($test['foo'])->toBeInt()
        ->and($test['bar'])->toBeFloat()
        ->and($test['foo'])->toBe(1)
        ->and($test['bar'])->toBe(10.42)
    ;
});
