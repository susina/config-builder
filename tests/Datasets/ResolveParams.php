<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

dataset('resolveParams', [
    [
        ['foo'],
        ['foo'],
    ],
    [
        ['foo' => 'bar', 'I\'m a %foo%'],
        ['foo' => 'bar', 'I\'m a bar'],
    ],
    [
        ['foo' => 'bar', '%foo%' => '%foo%'],
        ['foo' => 'bar', 'bar' => 'bar'],
    ],
    [
        ['foo' => 'bar', '%foo%' => ['%foo%' => ['%foo%' => '%foo%']]],
        ['foo' => 'bar', 'bar' => ['bar' => ['bar' => 'bar']]],
    ],
    [
        ['foo' => 'bar', 'I\'m a %%foo%%'],
        ['foo' => 'bar', 'I\'m a %foo%'],
    ],
    [
        ['foo' => 'bar', 'I\'m a %foo% %%foo %foo%'],
        ['foo' => 'bar', 'I\'m a bar %foo bar'],
    ],
    [
        ['foo' => ['bar' => ['ding' => 'I\'m a bar %%foo %%bar']]],
        ['foo' => ['bar' => ['ding' => 'I\'m a bar %foo %bar']]],
    ],
    [
        ['foo' => 'bar', 'baz' => '%%%foo% %foo%%% %%foo%% %%%foo%%%'],
        ['foo' => 'bar', 'baz' => '%bar bar% %foo% %bar%'],
    ],
    [
        ['baz' => '%%s?%%s', '%baz%'],
        ['baz' => '%s?%s', '%s?%s'],
    ],
    [
        ['host' => 'foo.bar', 'port' => 1337, '%host%:%port%'],
        ['host' => 'foo.bar', 'port' => 1337, 'foo.bar:1337'],
    ],
    [
        ['foo' => 'bar', '%foo%'],
        ['foo' => 'bar', 'bar'],
    ],
    [
        ['foo' => 'bar', '% foo %'],
        ['foo' => 'bar', '% foo %'],
    ],
    [
        ['foo' => 'bar', '{% set my_template = "foo" %}'],
        ['foo' => 'bar', '{% set my_template = "foo" %}'],
    ],
    [
        ['foo' => 'bar', '50% is less than 100%'],
        ['foo' => 'bar', '50% is less than 100%'],
    ],
    [
        ['foo' => ['bar' => 'baz', '%bar%' => 'babar'], 'babaz' => '%foo%'],
        ['foo' => ['bar' => 'baz', 'baz' => 'babar'], 'babaz' => ['bar' => 'baz', 'baz' => 'babar']],
    ],
    [
        ['foo' => ['bar' => 'baz'], 'babaz' => '%foo%'],
        ['foo' => ['bar' => 'baz'], 'babaz' => ['bar' => 'baz']],
    ]
]);
