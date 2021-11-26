<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Loader;

use Susina\CodingStandard\Config;
use Susina\ConfigBuilder\Exception\ConfigurationException;
use Susina\ConfigBuilder\FileLocator;
use Susina\ConfigBuilder\Loader\FileLoader;
use Susina\ConfigBuilder\Tests\DataProviderTrait;
use Susina\ConfigBuilder\Tests\TestCase;

class FileLoaderTest extends TestCase
{
    use DataProviderTrait;

    private TestableFileLoader $loader;

    public function setUp(): void
    {
        $this->loader = new TestableFileLoader(new FileLocator());
    }

    public function testResolveParams(): void
    {
        putenv('host=127.0.0.1');
        putenv('user=root');

        $config = [
            'HoMe' => 'myHome',
            'project' => 'myProject',
            'subhome' => '%HoMe%/subhome',
            'property1' => 1,
            'property2' => false,
            'direcories' => [
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
            'direcories' => [
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

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('host');
        putenv('user');
    }

    /**
     * @dataProvider providerForResolveParams
     */
    public function testResolveValues(array $conf, array $expected, string $message): void
    {
        $this->assertEquals($expected, $this->loader->resolveParams($conf), $message);
    }

    public function testResolveReplaceWithoutCasting(): void
    {
        $conf = $this->loader->resolveParams(['foo' => true, 'expfoo' => '%foo%', 'bar' => null, 'expbar' => '%bar%']);

        $this->assertTrue($conf['expfoo'], '->resolve() replaces arguments that are just a placeholder by their value without casting them to strings');
        $this->assertNull($conf['expbar'], '->resolve() replaces arguments that are just a placeholder by their value without casting them to strings');
    }

    public function testResolveThrowsExceptionIfInvalidPlaceholder(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Parameter 'baz' not found in configuration file.");

        $this->loader->resolveParams(['foo' => 'bar', '%baz%']);
    }

    public function testResolveThrowsExceptionIfNonExistentParameter(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Parameter 'foobar' not found in configuration file.");

        $this->loader->resolveParams(['foo %foobar% bar']);
    }

    public function testResolveThrowsRuntimeExceptionIfCircularReference(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Circular reference detected for parameter 'bar'.");

        $this->loader->resolveParams(['foo' => '%bar%', 'bar' => '%foobar%', 'foobar' => '%foo%']);
    }

    public function testResolveThrowsRuntimeExceptionIfCircularReferenceMixed(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Circular reference detected for parameter 'bar'.");

        $this->loader->resolveParams(['foo' => 'a %bar%', 'bar' => 'a %foobar%', 'foobar' => 'a %foo%']);
    }

    public function testResolveEnvironmentVariable(): void
    {
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

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('home');
        putenv('schema');
        putenv('isBoolean');
        putenv('integer');
    }

    public function testResolveEmptyEnvironmentVariable(): void
    {
        putenv('home=');

        $config = [
            'home' => '%env.home%',
        ];

        $expected = [
            'home' => '',
        ];

        $this->assertEquals($expected, $this->loader->resolveParams($config));

        //cleanup environment
        putenv('home');
    }

    public function testNonExistentEnvironmentVariableThrowsException(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage("Environment variable 'foo' is not defined.");

        putenv('home=myHome');

        $config = [
            'home' => '%env.home%',
            'property1' => '%env.foo%',
        ];

        $this->loader->resolveParams($config);
    }

    public function testParameterIsNotStringOrNumber(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('A string value must be composed of strings and/or numbers.');

        $config = [
            'foo' => 'a %bar%',
            'bar' => [],
            'baz' => '%foo%',
        ];

        $this->loader->resolveParams($config);
    }

    public function testCallResolveParamTwiceReturnsEmpty(): void
    {
        $config = [
            'foo' => 'bar',
            'baz' => '%foo%',
        ];

        $this->assertEquals(['foo' => 'bar', 'baz' => 'bar'], $this->loader->resolveParams($config));
        $this->assertSame([], $this->loader->resolveParams($config));
    }
}

class TestableFileLoader extends FileLoader
{
    public function load($resource, string $type = null)
    {
    }

    public function supports($resource, string $type = null)
    {
    }
}
