<?php
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('TestId', [
    [
        "<?xml version='1.0' standalone='yes'?>
<movies>
    <movie>
        <title>Star Wars</title>
        <starred>True</starred>
        <actor id=\"actorH\" name=\"Harrison Ford\" />
        <actor id=\"actorM\" name=\"Mark Hamill\" />
        <actor id=\"actorC\" name=\"Carrie Fisher\" />
    </movie>
    <movie>
        <title>The Lord Of The Rings</title>
        <starred>false</starred>
    </movie>
</movies>",
        [
            'movie' => [
                0 => [
                    'title' => 'Star Wars',
                    'starred' => true,
                    'actorH' => ['name' => 'Harrison Ford'],
                    'actorM' => ['name' => 'Mark Hamill'],
                    'actorC' => ['name' => 'Carrie Fisher']
                ],
                1 => [
                    'title' => 'The Lord Of The Rings',
                    'starred' => false
                ]
            ]
        ]
    ]
]);
