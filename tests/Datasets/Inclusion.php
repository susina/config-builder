<?php
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('Inclusion', [
    [
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <database name=\"named\" defaultIdMethod=\"native\">
        <xi:include xmlns:xi=\"http://www.w3.org/2001/XInclude\"
                    href=\"vfs://root/testconvert_include.xml\"
                    xpointer=\"xpointer( /database/* )\"
                        />
    </database>",
        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <database name=\"mixin\" defaultIdMethod=\"native\">
        <table name=\"book\" phpName=\"Book\"/>
    </database>",
        [
            'table' => [
                'name' => 'book',
                'phpName' => 'Book',
            ]
        ]
    ]
]);
