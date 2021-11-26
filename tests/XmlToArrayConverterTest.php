<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

use org\bovigo\vfs\vfsStream;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\Exception\XmlParseException;
use Susina\ConfigBuilder\XmlToArrayConverter;

class XmlToArrayConverterTest extends TestCase
{
    use DataProviderTrait;

    private XmlToArrayConverter $converter;

    protected function setUp(): void
    {
        $this->converter = new XmlToArrayConverter();
    }

    /**
     * @dataProvider providerForXmlToArrayConverter
     */
    public function testConvert($xml, $expected): void
    {
        $actual = $this->converter->convert($xml);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @dataProvider providerForXmlToArrayConverterXmlInclusions
     */
    public function testConvertWithXmlInclusion($xmlLoad, $xmlInclude, $expected): void
    {
        vfsStream::newFile('testconvert_include.xml')->at($this->getRoot())->setContent($xmlInclude);

        $actual = $this->converter->convert($xmlLoad);
        $this->assertEquals($expected, $actual);
    }

    public function testInvalidXmlThrowsException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('Invalid xml content');

        $invalidXml = <<< INVALID_XML
No xml
only plain text
---------
INVALID_XML;
        $this->converter->convert($invalidXml);
    }

    public function testErrorInXmlThrowsException(): void
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage('An error occurred while parsing XML configuration file:');

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
    }

    public function testMultipleErrorsInXmlThrowsException(): void
    {
        $this->expectException(XmlParseException::class);
        $this->expectExceptionMessage('Some errors occurred while parsing XML configuration file:');

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
    }

    /**
     * @dataProvider providerForTestId
     */
    public function testConvertWithId($xml, $expected): void
    {
        $actual = $this->converter->convert($xml);

        $this->assertEquals($expected, $actual);
    }
}
