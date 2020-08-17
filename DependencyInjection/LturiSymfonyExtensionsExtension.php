<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Config\FileLocator;

class LturiSymfonyExtensionsExtension extends Extension
{
	public function loadInternal(array $mergedConfig, ContainerBuilder $container)
	{
	    // Add overriden configuration (?) TODO:
		$loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);
		$loader->load('services.yaml');

		// Process bundle specific configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
	}
}