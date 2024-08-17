<?php declare(strict_types=1);
/*
 * Apache-2 License.
 * This file is part of susina/config-builder package, release under the APACHE-2 license.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Susina\ConfigBuilder\Tests\Fixtures;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class DatabaseConfigurationWithFirstTag implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('susina');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('database')
                    ->children()
                        ->scalarNode('name')->end()
                        ->booleanNode('auto_connect')->defaultTrue()->end()
                        ->scalarNode('default_connection')->defaultValue('default')->end()
                        ->arrayNode('connections')
                            ->arrayPrototype()
                                ->children()
                                    ->scalarNode('driver')->end()
                                    ->scalarNode('host')->end()
                                    ->scalarNode('username')->end()
                                    ->scalarNode('password')->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
