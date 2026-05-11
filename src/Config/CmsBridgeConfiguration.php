<?php

declare(strict_types=1);

namespace App\Config;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class CmsBridgeConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tree = new TreeBuilder('cms_bridge');
        $root = $tree->getRootNode();

        $root
            ->children()
                ->arrayNode('adapters')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('base_url')->defaultNull()->end()
                            ->scalarNode('space_id')->defaultNull()->end()
                            ->scalarNode('access_token')->defaultNull()->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('content_types')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('adapter')->isRequired()->end()
                            ->integerNode('cache_ttl')->defaultValue(3600)->end()
                            ->arrayNode('transformers')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                            ->arrayNode('field_map')
                                ->useAttributeAsKey('field')
                                ->scalarPrototype()->end()
                            ->end()
                            ->arrayNode('fallback_adapters')
                                ->scalarPrototype()->end()
                                ->defaultValue([])
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $tree;
    }
}
