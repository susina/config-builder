<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Nette\Neon\Exception;
use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\NeonFileLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new NeonFileLoader(new FileLocator($this->getRoot()->url()));
});

test('Supported neon extensions', function () {
    expect($this->loader->supports('foo.neon'))->toBeTrue()
        ->and($this->loader->supports('foo.neon.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.bar'))->toBeFalse()
        ->and($this->loader->supports('foo.bar.dist'))->toBeFalse()
    ;
});

test('Load neon file', function () {
    $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
    vfsStream::newFile('parameters.neon')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.neon');

    expect($test['foo'])->toBe('bar')
        ->and($test['bar'])->toBe('baz');
});

test('Load not existent neon file', function () {
    $this->loader->load('inexistent.neon');
})->throws(FileLocatorFileNotFoundException::class, 'The file "inexistent.neon" does not exist (in: "vfs://root").');

test('Neon file with invalid content', function () {
    $content = <<<EOF
not neon content
only plain
text
EOF;
    vfsStream::newFile('nonvalid.neon')->at($this->getRoot())->setContent($content);
    $this->loader->load('nonvalid.neon');
})->throws(Exception::class, "Unexpected 'only plain' on line 2, column 1.");

test('Empty neon file', function () {
    vfsStream::newFile('empty.neon')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.neon');
    expect($actual)->toBeEmpty();
});

test('Load not readable neon file', function () {
    $content = <<<EOF
foo: bar
bar: baz
EOF;
    vfsStream::newFile('notreadable.neon', 200)->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('notreadable.neon');
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.neon" was expected to be readable.')
    ->skip(running_on_windows());
