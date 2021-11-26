<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder;

use Assert\AssertionFailedException;
use SimpleXMLElement;
use Stringable;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\Exception\XmlParseException;

/**
 * Class to convert an xml string to array
 */
class XmlToArrayConverter
{
    /**
     * Create a PHP array from an XML string
     *
     * @param string $xmlToParse The XML to parse
     *
     * @return array
     *
     * @throws AssertionFailedException|ConfigurationException If invalid content
     * @throws XmlParseException If errors while parsing XML
     */
    public function convert(string $xmlToParse): array
    {
        if ($xmlToParse === '') {
            return [];
        }
        Assertion::startsWith($xmlToParse, '<', 'Invalid xml content.');

        $currentInternalErrors = libxml_use_internal_errors(true);

        $xml = simplexml_load_string($xmlToParse);
        if ($xml instanceof SimpleXMLElement) {
            dom_import_simplexml($xml)->ownerDocument->xinclude();
        }

        $errors = libxml_get_errors();

        libxml_clear_errors();
        libxml_use_internal_errors($currentInternalErrors);

        if (count($errors) > 0) {
            throw new XmlParseException($errors);
        }

        return $this->simpleXmlToArray($xml);
    }

    /**
     * Recursive function that converts an SimpleXML object into an array.
     *
     * @author Christophe VG (based on code form php.net manual comment)
     *
     * @param \SimpleXMLElement $xml SimpleXML object.
     *
     * @return array Array representation of SimpleXML object.
     */
    private function simpleXmlToArray(SimpleXMLElement $xml): array
    {
        $ar = [];
        foreach ($xml->children() as $k => $v) {
            // recurse the child
            $child = $this->simpleXmlToArray($v);

            // if it's not an array, then it was empty, thus a value/string
            if ($child === []) {
                $child = $this->getConvertedXmlValue($v);
            }

            // add the children attributes as if they where children
            foreach ($v->attributes() as $ak => $av) {
                if ($ak === 'id') {
                    // special exception: if there is a key named 'id'
                    // then we will name the current key after that id
                    $k = (string)$av;
                    if (ctype_digit($k)) {
                        $k = (int)$k;
                    }
                } else {
                    // otherwise, just add the attribute like a child element
                    if (is_string($child)) {
                        $child = [];
                    }
                    $child[$ak] = $this->getConvertedXmlValue($av);
                }
            }

            // if the $k is already in our children list, we need to transform
            // it into an array, else we add it as a value
            if (!array_key_exists($k, $ar)) {
                $ar[$k] = $child;
            } else {
                // (This only applies to nested nodes that do not have an @id attribute)

                // if the $ar[$k] element is not already an array, then we need to make it one.
                // this is a bit of a hack, but here we check to also make sure that if it is an
                // array, that it has numeric keys.  this distinguishes it from simply having other
                // nested element data.
                if (!is_array($ar[$k]) || !isset($ar[$k][0])) {
                    $ar[$k] = [$ar[$k]];
                }

                $ar[$k][] = $child;
            }
        }

        return $ar;
    }

    /**
     * Process XML value, handling boolean, if appropriate.
     *
     * @param \SimpleXMLElement $valueElement The simplexml value object.
     *
     * @return bool|float|int|string string or boolean value
     */
    private function getConvertedXmlValue(SimpleXMLElement $valueElement): float|bool|int|string
    {
        $value = (string)$valueElement; // convert from simplexml to string

        //handle numeric values
        if (is_numeric($value)) {
            if (ctype_digit($value)) {
                return (int)$value;
            }

            return (float)$value;
        }

        return match (strtolower($value)) {
            'false' => false,
            'true' => true,
            default => $value
        };
    }
}
