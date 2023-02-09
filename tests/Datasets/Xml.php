<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('Xml', [
    [
        "<?xml version='1.0' standalone='yes'?>
<movies>
 <movie>
  <title>Star Wars</title>
  <starred>True</starred>
  <percentage>32.5</percentage>
 </movie>
 <movie>
  <title>The Lord Of The Rings</title>
  <starred>false</starred>
 </movie>
</movies>
"
        ,
        ['movie' => [0 => ['title' => 'Star Wars', 'starred' => true, 'percentage' => 32.5], 1 => ['title' => 'The Lord Of The Rings', 'starred' => false]]],
    ],
    [
        "<?xml version=\"1.0\" encoding=\"utf-8\"?>
<config>
  <log>
    <logger name=\"defaultLogger\">
      <type>stream</type>
      <path>/var/log/default.log</path>
      <level>300</level>
    </logger>
    <logger name=\"bookstore\">
      <type>stream</type>
      <path>/var/log/bookstore.log</path>
    </logger>
  </log>
</config>", [
        'log' => [
            'logger' => [
                [
                    'type' => 'stream',
                    'path' => '/var/log/default.log',
                    'level' => 300,
                    'name' => 'defaultLogger',
                ],
                [
                    'type' => 'stream',
                    'path' => '/var/log/bookstore.log',
                    'name' => 'bookstore',
                ],
            ],
        ]]
    ],
    [
        "<config>
    <database name=\"TestDb\">
        <table name=\"table1\"></table>
        <table name=\"table2\"></table>
    </database>
</config>", [
        'database' => [
            'table' => [
                0 => ['name' => 'table1'],
                1 => ['name' => 'table2']
            ],
            'name' => 'TestDb'
        ]
    ]
    ]
]);
