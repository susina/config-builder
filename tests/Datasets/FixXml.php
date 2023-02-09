<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('fixXml', [
    "<?xml version='1.0' standalone='yes'?>
<movies>
    <movie>
        <title>Star Wars</title>
        <starred>True</starred>
        <actor name=\"Harrison Ford\" />
        <actor name=\"Mark Hamill\" />
        <actor name=\"Carrie Fisher\" />
    </movie>
    <movie>
        <title>The Lord Of The Rings</title>
        <starred>false</starred>
    </movie>
</movies>",
    ['movie' => 'movies', 'actor' => 'actors'],
    [
        'movies' => [
            0 => [
                'title' => 'Star Wars',
                'starred' => true,
                'actors' => [
                    0 => ['name' => 'Harrison Ford'],
                    1 => ['name' => 'Mark Hamill'],
                    2 => ['name' => 'Carrie Fisher']
                ]
            ],
            1 => [
                'title' => 'The Lord Of The Rings',
                'starred' => false
            ]
        ]
    ]
]);
