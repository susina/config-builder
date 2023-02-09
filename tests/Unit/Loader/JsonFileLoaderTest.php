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
use Susina\ConfigBuilder\Loader\JsonFileLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new JsonFileLoader(new FileLocator($this->getRoot()->url()));
});

test('Supported extensions', function () {
    expect($this->loader->supports('foo.json'))->toBeTrue()
        ->and($this->loader->supports('foo.json.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.bar'))->toBeFalse()
        ->and($this->loader->supports('foo.bar.dist'))->toBeFalse()
    ;
});

test('Load json file', function () {
    $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
    vfsStream::newFile('parameters.json')->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('parameters.json');

    expect($actual['foo'])->toBe('bar')
        ->and($actual['bar'])->toBe('baz');
});

test('Load not existent json file', function () {
    $this->loader->load('inexistent.json');
})->throws(FileLocatorFileNotFoundException::class, 'The file "inexistent.json" does not exist (in: "vfs://root").');

test('Load file withinvalid content', function () {
    $content = <<<EOF
not json content
only plain
text
EOF;
    vfsStream::newFile('nonvalid.json')->at($this->getRoot())->setContent($content);
    $this->loader->load('nonvalid.json');
})->throws(\JsonException::class, 'Syntax error');

test('Empty json file', function () {
    vfsStream::newFile('empty.json')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.json');

    expect($actual)->toBeEmpty();
});

test('Load not readable file', function () {
    $content = <<<EOF
{
  "foo": "bar",
  "bar": "baz"
}
EOF;
    vfsStream::newFile('notreadable.json', 200)->at($this->getRoot())->setContent($content);
    $actual = $this->loader->load('notreadable.json');
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.json" was expected to be readable.')
    ->skip(running_on_windows());
