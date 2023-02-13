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
use Susina\ConfigBuilder\Loader\XmlFileLoader;
use Symfony\Component\Config\Exception\FileLocatorFileNotFoundException;

beforeEach(function () {
    $this->loader = new XmlFileLoader(new FileLocator($this->getRoot()->url()));
});

test('Supported xml extensions', function () {
    expect($this->loader->supports('foo.xml'))->toBeTrue()
        ->and($this->loader->supports('foo.xml.dist'))->toBeTrue()
        ->and($this->loader->supports('foo.yml.dist'))->toBeFalse()
        ->and($this->loader->supports('foo.bar'))->toBeFalse()
        ->and($this->loader->supports('foo.bar.dist'))->toBeFalse()
    ;
});

test('Load xml file', function () {
    $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
    vfsStream::newFile('parameters.xml')->at($this->getRoot())->setContent($content);
    $test = $this->loader->load('parameters.xml');

    expect($test['foo'])->toBe('bar')
        ->and($test['bar'])->toBe('baz');
});

test('Load not existent xml file', function () {
    $this->loader->load('inexistent.xml');
})->throws(FileLocatorFileNotFoundException::class, 'The file "inexistent.xml" does not exist (in: "vfs://root").');

test('Load xmlfile with invalid content', function () {
    $content = <<<EOF
not xml content
only plain
text
EOF;
    vfsStream::newFile('nonvalid.xml')->at($this->getRoot())->setContent($content);
    @$this->loader->load('nonvalid.xml');
})->throws(ConfigurationBuilderException::class, 'Invalid xml content');

test('Empty xml file', function () {
    vfsStream::newFile('empty.xml')->at($this->getRoot())->setContent('');
    $actual = $this->loader->load('empty.xml');

    expect($actual)->toBe([]);
});

test('Load not readable xml file', function () {
    $content = <<< XML
<?xml version='1.0' standalone='yes'?>
<properties>
  <foo>bar</foo>
  <bar>baz</bar>
</properties>
XML;
    vfsStream::newFile('notreadable.xml', 200)->at($this->getRoot())->setContent($content);
    $this->loader->load('notreadable.xml');
})->throws(ConfigurationBuilderException::class, 'Path "vfs://root/notreadable.xml" was expected to be readable.')
    ->skip(running_on_windows());
