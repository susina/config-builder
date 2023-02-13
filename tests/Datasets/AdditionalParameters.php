<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('AdditionalParameters', [
[
    [
        'auto_connect' => true,
        'default_connection' => 'mysql',
        'connections' => [
            'mysql' => [
                'host' => 'localhost',
                'driver' => 'mysql',
                'username' => 'user',
                'password' => 'pass'
            ],
            'sqlite' => [
                'host' => 'localhost',
                'driver' => 'sqlite',
                'username' => 'user',
                'password' => 'pass'
            ]
        ]
    ],
    [
        'auto_connect' => true,
        'default_connection' => 'mysql',
        'connections' => [
            'mysql' => [
                'host' => 'localhost',
                'driver' => 'mysql',
                'username' => 'user',
                'password' => 'pass'
            ],
            'sqlite' => [
                'host' => 'localhost',
                'driver' => 'sqlite',
                'username' => 'user',
                'password' => 'pass'
            ],
            'pgsql' => [
                'host' => 'localhost',
                'driver' => 'postgresql',
                'username' => 'user',
                'password' => 'pass'
            ]
        ]
    ]
]
]);
