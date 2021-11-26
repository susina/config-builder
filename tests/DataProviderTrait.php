<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests;

trait DataProviderTrait
{
    public function providerForResolveParams(): array
    {
        return [
            [
                ['foo'],
                ['foo'],
                '->resolve() returns its argument unmodified if no placeholders are found',
            ],
            [
                ['foo' => 'bar', 'I\'m a %foo%'],
                ['foo' => 'bar', 'I\'m a bar'],
                '->resolve() replaces placeholders by their values',
            ],
            [
                ['foo' => 'bar', '%foo%' => '%foo%'],
                ['foo' => 'bar', 'bar' => 'bar'],
                '->resolve() replaces placeholders in keys and values of arrays',
            ],
            [
                ['foo' => 'bar', '%foo%' => ['%foo%' => ['%foo%' => '%foo%']]],
                ['foo' => 'bar', 'bar' => ['bar' => ['bar' => 'bar']]],
                '->resolve() replaces placeholders in nested arrays',
            ],
            [
                ['foo' => 'bar', 'I\'m a %%foo%%'],
                ['foo' => 'bar', 'I\'m a %foo%'],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => 'bar', 'I\'m a %foo% %%foo %foo%'],
                ['foo' => 'bar', 'I\'m a bar %foo bar'],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => ['bar' => ['ding' => 'I\'m a bar %%foo %%bar']]],
                ['foo' => ['bar' => ['ding' => 'I\'m a bar %foo %bar']]],
                '->resolve() supports % escaping by doubling it',
            ],
            [
                ['foo' => 'bar', 'baz' => '%%%foo% %foo%%% %%foo%% %%%foo%%%'],
                ['foo' => 'bar', 'baz' => '%bar bar% %foo% %bar%'],
                '->resolve() replaces params placed besides escaped %',
            ],
            [
                ['baz' => '%%s?%%s', '%baz%'],
                ['baz' => '%s?%s', '%s?%s'],
                '->resolve() is not replacing greedily',
            ],
            [
                ['host' => 'foo.bar', 'port' => 1337, '%host%:%port%'],
                ['host' => 'foo.bar', 'port' => 1337, 'foo.bar:1337'],
                '',
            ],
            [
                ['foo' => 'bar', '%foo%'],
                ['foo' => 'bar', 'bar'],
                'Parameters must be wrapped by %.',
            ],
            [
                ['foo' => 'bar', '% foo %'],
                ['foo' => 'bar', '% foo %'],
                'Parameters should not have spaces.',
            ],
            [
                ['foo' => 'bar', '{% set my_template = "foo" %}'],
                ['foo' => 'bar', '{% set my_template = "foo" %}'],
                'Twig-like strings are not parameters.',
            ],
            [
                ['foo' => 'bar', '50% is less than 100%'],
                ['foo' => 'bar', '50% is less than 100%'],
                'Text between % signs is allowed, if there are spaces.',
            ],
            [
                ['foo' => ['bar' => 'baz', '%bar%' => 'babar'], 'babaz' => '%foo%'],
                ['foo' => ['bar' => 'baz', 'baz' => 'babar'], 'babaz' => ['bar' => 'baz', 'baz' => 'babar']],
                '',
            ],
            [
                ['foo' => ['bar' => 'baz'], 'babaz' => '%foo%'],
                ['foo' => ['bar' => 'baz'], 'babaz' => ['bar' => 'baz']],
                '',
            ],
        ];
    }

    public function providerForXmlToArrayConverter(): array
    {
        $moviesXml = <<<EOF
<?xml version='1.0' standalone='yes'?>
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
EOF;

        $loggerXml = <<<EOF
<?xml version="1.0" encoding="utf-8"?>
<config>
  <log>
    <logger name="defaultLogger">
      <type>stream</type>
      <path>/var/log/default.log</path>
      <level>300</level>
    </logger>
    <logger name="bookstore">
      <type>stream</type>
      <path>/var/log/bookstore.log</path>
    </logger>
  </log>
</config>
EOF;

        return [
            [
                $moviesXml,
                ['movie' => [0 => ['title' => 'Star Wars', 'starred' => true, 'percentage' => 32.5], 1 => ['title' => 'The Lord Of The Rings', 'starred' => false]]],
            ],
            [
                $loggerXml, [
                'log' => [
                    'logger' => [
                        [
                            'type' => 'stream',
                            'path' => '/var/log/default.log',
                            'level' => '300',
                            'name' => 'defaultLogger',
                        ],
                        [
                            'type' => 'stream',
                            'path' => '/var/log/bookstore.log',
                            'name' => 'bookstore',
                        ],
                    ],
                ]],
            ]
        ];
    }

    public function providerForXmlToArrayConverterXmlInclusions(): array
    {
        $xmlOne = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<database name="named" defaultIdMethod="native">
    <xi:include xmlns:xi="http://www.w3.org/2001/XInclude"
                href="vfs://root/testconvert_include.xml"
                xpointer="xpointer( /database/* )"
               />
</database>
EOF;
        $xmlTwo = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<database name="mixin" defaultIdMethod="native">
    <table name="book" phpName="Book"/>
</database>
EOF;
        $array = [
            'table' => [
                'name' => 'book',
                'phpName' => 'Book',
            ],
        ];

        return [
            [
                $xmlOne,
                $xmlTwo,
                $array,
            ],
        ];
    }

    public function providerForFixXml(): array
    {
        $moviesXml = <<<EOF
<?xml version='1.0' standalone='yes'?>
<movies>
    <movie>
        <title>Star Wars</title>
        <starred>True</starred>
        <actor name="Harrison Ford" />
        <actor name="Mark Hamill" />
        <actor name="Carrie Fisher" />
    </movie>
    <movie>
        <title>The Lord Of The Rings</title>
        <starred>false</starred>
    </movie>
</movies>
EOF;
        $fixes = ['movie' => 'movies', 'actor' => 'actors'];
        $array = [
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
        ];

        return [[$moviesXml, $fixes, $array]];
    }

    public function providerForTestId(): array
    {
        $moviesXml = <<<EOF
<?xml version='1.0' standalone='yes'?>
<movies>
    <movie>
        <title>Star Wars</title>
        <starred>True</starred>
        <actor id="actorH" name="Harrison Ford" />
        <actor id="actorM" name="Mark Hamill" />
        <actor id="actorC" name="Carrie Fisher" />
    </movie>
    <movie>
        <title>The Lord Of The Rings</title>
        <starred>false</starred>
    </movie>
</movies>
EOF;

        $expected = [
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
        ];

        return [[$moviesXml, $expected]];
    }
}
