<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Susina\ConfigBuilder\Exception\ConfigurationBuilderException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\FileLoader;

beforeEach(function () {
    $this->loader = new TestableFileLoader(new FileLocator());
});

test('Resolve parameters', function () {
    putenv('host=127.0.0.1');
    putenv('user=root');

    $config = [
        'HoMe' => 'myHome',
        'project' => 'myProject',
        'subhome' => '%HoMe%/subhome',
        'property1' => 1,
        'property2' => false,
        'directories' => [
            'project' => '%HoMe%/projects/%project%',
            'conf' => '%project%',
            'schema' => '%project%/schema',
            'template' => '%HoMe%/templates',
            'output%project%' => '/build',
        ],
        '%HoMe%' => 4,
        'host' => '%env.host%',
        'user' => '%env.user%',
    ];

    $expected = [
        'HoMe' => 'myHome',
        'project' => 'myProject',
        'subhome' => 'myHome/subhome',
        'property1' => 1,
        'property2' => false,
        'directories' => [
            'project' => 'myHome/projects/myProject',
            'conf' => 'myProject',
            'schema' => 'myProject/schema',
            'template' => 'myHome/templates',
            'outputmyProject' => '/build',
        ],
        'myHome' => 4,
        'host' => '127.0.0.1',
        'user' => 'root',
    ];

    expect($expected)->toBe($this->loader->resolveParams($config));

    //cleanup environment
    putenv('host');
    putenv('user');
});

test('Resolve values', function (array $conf, array $expected) {
    expect($expected)->toBe($this->loader->resolveParams($conf));
})->with('resolveParams');

test('Replacing values are not cast to strings', function () {
    $conf = $this->loader->resolveParams(['foo' => true, 'expfoo' => '%foo%', 'bar' => null, 'expbar' => '%bar%']);

    expect($conf['expfoo'])->toBeTrue()->and($conf['expbar'])->toBeNull();
});

test('Invalid placeholder', fn () => $this->loader->resolveParams(['foo' => 'bar', '%baz%']))
    ->throws(ConfigurationBuilderException::class, "Parameter 'baz' not found in configuration file.");

test('Non existent placeholder', fn () => $this->loader->resolveParams(['foo %foobar% bar']))
    ->throws(ConfigurationBuilderException::class, "Parameter 'foobar' not found in configuration file.");

test('Simple circular reference', fn () => $this->loader->resolveParams(['foo' => '%bar%', 'bar' => '%foobar%', 'foobar' => '%foo%']))
    ->throws(ConfigurationBuilderException::class, "Circular reference detected for parameter 'bar'.");

test('Complex circular reference', fn () => $this->loader->resolveParams(['foo' => 'a %bar%', 'bar' => 'a %foobar%', 'foobar' => 'a %foo%']))
    ->throws(ConfigurationBuilderException::class, "Circular reference detected for parameter 'bar'.");

test('Environment variable parameters', function () {
    putenv('home=myHome');
    putenv('schema=mySchema');
    putenv('isBoolean=true');
    putenv('integer=1');

    $config = [
        'home' => '%env.home%',
        'property1' => '%env.integer%',
        'property2' => '%env.isBoolean%',
        'direcories' => [
            'projects' => '%home%/projects',
            'schema' => '%env.schema%',
            'template' => '%home%/templates',
            'output%env.home%' => '/build',
        ],
    ];

    $expected = [
        'home' => 'myHome',
        'property1' => '1',
        'property2' => 'true',
        'direcories' => [
            'projects' => 'myHome/projects',
            'schema' => 'mySchema',
            'template' => 'myHome/templates',
            'outputmyHome' => '/build',
        ],
    ];

    expect($this->loader->resolveParams($config))->toBe($expected);

    //cleanup environment
    putenv('home');
    putenv('schema');
    putenv('isBoolean');
    putenv('integer');
});

test('Resolve empty environment variable', function () {
    putenv('home=');

    $config = [
        'home' => '%env.home%',
    ];

    $expected = [
        'home' => '',
    ];

    expect($expected)->toBe($this->loader->resolveParams($config));

    //cleanup environment
    putenv('home');
});

test('Not existent environment variable', function () {
    putenv('home=myHome');

    $config = [
        'home' => '%env.home%',
        'property1' => '%env.foo%',
    ];

    $this->loader->resolveParams($config);
})->throws(ConfigurationBuilderException::class, "Environment variable 'foo' is not defined.");

test('Parameter type is not string or number', function () {
    $config = [
        'foo' => 'a %bar%',
        'bar' => [],
        'baz' => '%foo%',
    ];

    $this->loader->resolveParams($config);
})->throws(ConfigurationBuilderException::class, 'A string value must be composed of strings and/or numbers.');

test('Resolve param twice', function () {
    $config = [
        'foo' => 'bar',
        'baz' => '%foo%',
    ];

    expect(['foo' => 'bar', 'baz' => 'bar'])->toBe($this->loader->resolveParams($config))
        ->and([])->toBe($this->loader->resolveParams($config));
});

class TestableFileLoader extends FileLoader
{
    public function load($resource, string $type = null)
    {
    }

    public function supports($resource, string $type = null)
    {
    }
}
