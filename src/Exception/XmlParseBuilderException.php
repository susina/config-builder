<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Exception;

class XmlParseBuilderException extends ConfigurationBuilderException
{
    /**
     * Create an exception based on LibXMLError objects
     *
     * @param \LibXMLError[] $errors Array of LibXMLError objects
     *
     * @see http://www.php.net/manual/en/class.libxmlerror.php
     */
    public function __construct(array $errors)
    {
        $message = (count($errors) === 1 ? 'An error ' : 'Some errors ') .
            "occurred while parsing XML configuration file:\n"
        ;

        foreach ($errors as $error) {
            $message .= ' - ' .
                match ($error->level) {
                    LIBXML_ERR_WARNING => "Warning ",
                    LIBXML_ERR_ERROR => "Error ",
                    LIBXML_ERR_FATAL => "Fatal "
                }
            .
                "$error->code: $error->message"
            ;
        }

        parent::__construct($message);
    }
}
