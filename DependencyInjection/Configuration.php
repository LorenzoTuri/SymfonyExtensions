<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lturi_symfony_extensions');

        $treeBuilder->getRootNode()
            ->children()
                // Entity configuration for EntityNormalizer
                ->arrayNode('entity')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('namespace')
                        ->defaultValue("App\\Entity\\")
                    ->end()
                ->end()
            ->end() // entity
        ->end();

        return $treeBuilder;
    }
}