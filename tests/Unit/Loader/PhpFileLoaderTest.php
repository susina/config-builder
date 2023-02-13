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
use Susina\ConfigBuilder\Loader\PhpFileLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new PhpFileLoader(new FileLocator($this->getRoot()->url()));
});

test('Supported php extensions', function () {
    expect($this->loader->supports('foo.php'))->toBeTrue()
        ->and($this->loader->supports('foo.php.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.foo'))->toBeFalse()
        ->and($this->loader->supports('foo.foo.dist'))->toBeFalse()
    ;
});

test('Load php file', function () {
    $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
    vfsStream::newFile('parameters.php')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.php');

    expect($test['foo'])->toBe('bar')
        ->and($test['bar'])->toBe('baz');
});

test('Load not existent php file', function () {
    $this->loader->load('inexistent.php');
})->throws(FileLocatorFileNotFoundException::class, 'The file "inexistent.php" does not exist (in: "vfs://root").');

test('Load php file with invalid conten', function () {
    $content = <<<EOF
not php content
only plain
text
EOF;
    vfsStream::newFile('nonvalid.php')->at($this->getRoot())->setContent($content);
    $this->loader->load('nonvalid.php');
})->throws(ConfigurationBuilderException::class, "The configuration file 'nonvalid.php' has invalid content.");

test('Load empty php file', function () {
    vfsStream::newFile('empty.php')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.php');

    expect($actual)->toBe([]);
});

test('Load not readable php file', function () {
    $content = <<<EOF
<?php

    return array('foo' => 'bar', 'bar' => 'baz');

EOF;
    vfsStream::newFile('notreadable.php', 200)->at($this->getRoot())->setContent($content);
    $this->loader->load('notreadable.php');
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.php" was expected to be readable.')
    ->skip(running_on_windows());
