<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

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

                    $this->injectApiConfig($treeBuilderChildren);
                    $this->injectCommandApiConfig($treeBuilderChildren);
                    $this->injectGraphQLApiConfig($treeBuilderChildren);
                    $this->injectJsonApiConfig($treeBuilderChildren);
                    $this->injectRestApiConfig($treeBuilderChildren);

        $treeBuilderChildren
                ->end()
            ->end();

        return $treeBuilder;
    }

    private function injectApiConfig(NodeBuilder $treeNodeChildren) {
        $treeNodeChildrenInternal = $treeNodeChildren
            ->arrayNode('api')
                ->info('Configuration for API path (used to detect if a request is api or not)')
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

        return $treeNodeChildrenInternal
                ->end()
            ->end();
    }

    private function injectCommandApiConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            ->arrayNode('commandApi')
                ->info("Array of entities, to be loaded into the command-api")
                ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    private function injectGraphQLApiConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            ->arrayNode('graphQLApi')
                ->info("Array of entities, to be loaded into the graphQL-api")
                ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    private function injectJsonApiConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            ->arrayNode('jsonApi')
                ->info("Array of entities, to be loaded into the json-api")
                ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end();
    }

    private function injectRestApiConfig(NodeBuilder $treeNodeChildren) {
        return $treeNodeChildren
            ->arrayNode('restApi')
                ->info("Array of entities, to be loaded into the rest-api")
                ->scalarPrototype()
                    ->defaultValue([])
                ->end()
            ->end();
    }
}