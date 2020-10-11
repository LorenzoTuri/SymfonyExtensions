<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('lturi_symfony_extensions');

        $treeBuilderChildren = $treeBuilder
            ->getRootNode()
            ->ignoreExtraKeys(false)
                ->children();

        $this->injectEntityConfig($treeBuilderChildren);
        $this->injectApiConfig($treeBuilderChildren);

        $treeBuilderChildren
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function injectEntityConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            // Entity configuration for EntityNormalizer
            ->arrayNode('entity')
                ->info('Set configuration for environment entities')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('namespace')
                        ->info('What is the entity namespace? (used to detect if a class is an entity')
                        ->example('App\\Entity\\')
                        ->defaultValue("App\\Entity\\")
                    ->end()
                ->end()
            ->end();
    }

    private function injectApiConfig(NodeBuilder $treeNodeChildren) {
        $treeNodeChildrenInternal = $treeNodeChildren
            ->arrayNode('api')
                ->info('Configuration for API path (used to detect if a request is api or not')
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('path')
                        ->info('Url sub path for api routes')
                        ->example('/api/')
                        ->defaultValue('/api/')
                    ->end()
                    ->booleanNode('load_routes')
                        ->info('Activate the routes endpoint?')
                        ->defaultValue(true)
                    ->end()
                    ->booleanNode('load_translations')
                        ->info('Activate the translations endpoint?')
                        ->defaultValue(true)
                    ->end()
                    ->booleanNode('load_entities')
                        ->info('Activate the entities endpoint?')
                        ->defaultValue(true)
                    ->end();

        $this->injectApiEntitiesConfig($treeNodeChildrenInternal);

        return $treeNodeChildrenInternal
                ->end()
            ->end();
    }

    private function injectApiEntitiesConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            ->arrayNode('entities')
                ->arrayPrototype()
                    ->children()
                        ->scalarNode('name')
                            ->info('Name used or this entity')
                        ->end()
                        ->arrayNode('path')
                            ->scalarPrototype()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}