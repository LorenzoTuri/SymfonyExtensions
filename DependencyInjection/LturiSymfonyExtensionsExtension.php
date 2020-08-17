<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Exception;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

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
	    // Add services configuration (?) TODO:
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);
		$loader->load('services.yaml');

		// Process bundle specific configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('lturi.symfony_extensions.doctrine.entity', $config['entity']['namespace']);
	}
}