<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Exception;
use Lturi\SymfonyExtensions\Classes\Constants;
use Lturi\SymfonyExtensions\Classes\Entities\Entity;
use Lturi\SymfonyExtensions\Classes\Entities\EntityPath;
use Lturi\SymfonyExtensions\Controller\Api\JsonapiController;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;
use function Symfony\Component\String\u;

class LturiSymfonyExtensionsExtension extends Extension
{
    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
	public function load(array $configs, ContainerBuilder $container)
	{
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);
        $loader->load('services.yaml');

		// Process bundle specific configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $config = $this->completeConfig($config);

        $container->setParameter(Constants::ENTITY_NAMESPACE, $config['entity']['namespace']);
        $container->setParameter(Constants::API_PATH, $config['api']['path']);
        $container->setParameter(Constants::LOAD_ROUTES, $config['api']['load_routes']);
        $container->setParameter(Constants::LOAD_TRANSLATIONS, $config['api']['load_translations']);
        $container->setParameter(Constants::LOAD_ENTITIES, $config['api']['load_entities']);
        $container->setParameter(Constants::ENTITIES, $config['api']['entities']);
	}

	private function completeConfig(array $config) {
	    if (!isset($config["api"]["entities"])) $config["api"]["entities"] = [];
	    foreach ($config["api"]["entities"] as $class => $entityConfig) {
	        $entityConfig["class"] = $class;
            if (!isset($entityConfig["name"])) $entityConfig["name"] = u($class)->camel();
            if (!isset($entityConfig["path"])) $entityConfig["path"] = [];
            if (!isset($entityConfig["path"]["list"])) $entityConfig["path"]["list"] = $this->buildEntityController($entityConfig);
            if (!isset($entityConfig["path"]["get"])) $entityConfig["path"]["get"] = $this->buildEntityController($entityConfig);
            if (!isset($entityConfig["path"]["create"])) $entityConfig["path"]["create"] = $this->buildEntityController($entityConfig);
            if (!isset($entityConfig["path"]["delete"])) $entityConfig["path"]["delete"] = $this->buildEntityController($entityConfig);
            if (!isset($entityConfig["path"]["update"])) $entityConfig["path"]["update"] = $this->buildEntityController($entityConfig);

            $entityPaths = [];
            foreach ($entityConfig["path"] as $key => $controller) {
                $entityPaths[] = [
                    "name" => $key,
                    "controller" => $controller
                ];
            }
            $entityConfig["path"] = $entityPaths;

            $config["api"]["entities"][$class] = $entityConfig;
        }
        return $config;
    }

    private function buildEntityController($entityConfig) {
	    return JsonapiController::class.'::dispatchRequest';
    }
}