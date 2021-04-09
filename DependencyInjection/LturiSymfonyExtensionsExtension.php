<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Exception;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntitiesDescriptor;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class LturiSymfonyExtensionsExtension extends Extension
{
    protected $container;

    /**
     * @param array            $configs
     * @param ContainerBuilder $container
     *
     * @throws Exception
     */
	public function load(array $configs, ContainerBuilder $container)
	{
	    $this->container = $container;

		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);
        $loader->load('services.yaml');

		// Process bundle specific configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $config = $this->completeConfig($config);

        $container->setParameter(Constants::API_PATH, $config['api']['path']);
        $container->setParameter(Constants::LOAD_ROUTES, $config['api']['load_routes']);
        $container->setParameter(Constants::LOAD_TRANSLATIONS, $config['api']['load_translations']);
        $container->setParameter("jsonApiEntities", $config['jsonApi']);
        $container->setParameter("restApiEntities", $config['restApi']);
        $container->setParameter("commandApiEntities", $config['commandApi']);
        $container->setParameter("graphQLApiEntities", $config['graphQLApi']);
	}

	private function completeConfig(array $config): array
    {
        foreach ($config["jsonApi"] as $index => $class) {
            $config["jsonApi"][$index] = EntitiesDescriptor::describeEntity($class);
        }
        foreach ($config["restApi"] as $index => $class) {
            $config["restApi"][$index] = EntitiesDescriptor::describeEntity($class);
        }
        foreach ($config["commandApi"] as $index => $class) {
            $config["commandApi"][$index] = EntitiesDescriptor::describeEntity($class);
        }
        foreach ($config["graphQLApi"] as $index => $class) {
            $config["graphQLApi"][$index] = EntitiesDescriptor::describeEntity($class);
        }
        return $config;
    }
}