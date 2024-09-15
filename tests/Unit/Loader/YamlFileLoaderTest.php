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
use Susina\ConfigBuilder\Loader\YamlFileLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new YamlFileLoader(new FileLocator($this->getRoot()->url()));
});

test("Supported yaml files", function () {
    expect($this->loader->supports('foo.yaml'))->toBeTrue()
        ->and($this->loader->supports('foo.yml'))->toBeTrue()
        ->and($this->loader->supports('foo.yaml.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.yml.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.bar'))->toBeFalse()
        ->and($this->loader->supports('foo.bar.dist'))->toBeFalse()
    ;
});

test("Load yaml file", function () {
    $content = <<<EOF
#test ini
foo: bar
bar: baz
EOF;
    vfsStream::newFile('parameters.yaml')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.yaml');

    expect($test['foo'])->toBe('bar')
        ->and($test['bar'])->toBe('baz');
});

test("Non existent yaml file", function () {
    $this->loader->load('inexistent.yaml');
})->throws(FileLocatorFileNotFoundException::class, 'The file "inexistent.yaml" does not exist (in: "vfs://root").');

test("Invalid yaml content", function () {
    $content = <<<EOF
not yaml content
only plain
text
EOF;
    vfsStream::newFile('invalid.yaml')->at($this->getRoot())->setContent($content);
    $this->loader->load('invalid.yaml');
})->throws(ConfigurationBuilderException::class, 'Unable to parse the configuration file: wrong yaml content.');

test("Empty yaml file", function () {
    vfsStream::newFile('empty.yaml')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.yaml');

    expect($actual)->toBe([]);
});

test("Yaml file not readable", function () {
    $content = <<<EOF
foo: bar
bar: baz
EOF;
    vfsStream::newFile('notreadable.yaml', 200)->at($this->getRoot())->setContent($content);

    $this->loader->load('notreadable.yaml');
})
    ->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.yaml" was expected to be readable.')
    ->skipOnWindows();
