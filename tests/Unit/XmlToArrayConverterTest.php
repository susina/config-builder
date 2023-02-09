<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\Exception\XmlParseBuilderException;
use Susina\ConfigBuilder\XmlToArrayConverter;

beforeEach(function () {
    $this->converter = new XmlToArrayConverter();
});

test('Convert xml', function (string $xml, array $expected) {
    $actual = $this->converter->convert($xml);
    expect($actual)->toBe($expected);
})->with('Xml');

test('Convert xml with inclusion', function (string $xmlLoad, string $xmlInclude, array $expected) {
    vfsStream::newFile('testconvert_include.xml')->at($this->getRoot())->setContent($xmlInclude);
    $actual = $this->converter->convert($xmlLoad);

    expect($actual)->toBe($expected);
})->with('Inclusion');

test('Invalid xml', function () {
    $invalidXml = <<< INVALID_XML
No xml
only plain text
---------
INVALID_XML;
    $this->converter->convert($invalidXml);
})->throws(ConfigurationBuilderException::class, 'Invalid xml content');

test('Errorin xml content', function () {
    $xmlWithError = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</movies>
XML;
    $this->converter->convert($xmlWithError);
})->throws(XmlParseBuilderException::class, 'An error occurred while parsing XML configuration file:');

test('Multiple errors in xml', function () {
    $xmlWithErrors = <<< XML
<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <titles>Star Wars</title>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
 </movie>
</moviess>
XML;
    $this->converter->convert($xmlWithErrors);
})->throws(XmlParseBuilderException::class, 'Some errors occurred while parsing XML configuration file:');

test('Convert with Id', function (string $xml, array $expected) {
    $actual = $this->converter->convert($xml);
    expect($actual)->toBe($expected);
})->with('TestId');
