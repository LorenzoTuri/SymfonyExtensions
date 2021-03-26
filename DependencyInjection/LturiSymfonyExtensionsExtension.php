<?php

namespace Lturi\SymfonyExtensions\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Exception;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\JsonApi\Annotation\Entity;
use Lturi\SymfonyExtensions\JsonApi\Controller\JsonapiController;
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

        $container->setParameter(Constants::API_PATH, $config['api']['path']);
        $container->setParameter(Constants::LOAD_ROUTES, $config['api']['load_routes']);
        $container->setParameter(Constants::LOAD_TRANSLATIONS, $config['api']['load_translations']);
        $container->setParameter("jsonApiEntities", $config['jsonApi']);
        $container->setParameter("restApiEntities", $config['restApi']);
        $container->setParameter("commandApiEntities", $config['commandApi']);
        $container->setParameter("graphQLApiEntities", $config['graphQLApi']);
	}

	private function completeConfig(array $config) {
        $reader = new AnnotationReader();
        foreach ($config["jsonApi"] as $index => $class) {
            $reflectionClass = new \ReflectionClass($class);
            /** @var Entity $entityDescription */
            $entityDescription = $reader->getClassAnnotation($reflectionClass, Entity::class);

            $entityConfig["class"] = $class;
            $entityConfig["name"] = $entityDescription->name ?? u($class)->camel()->toString();
            $entityConfig["path"] = str_replace("_","-", $entityDescription->path ?? u($class)->snake()->toString());
            $entityConfig["controller"] = $entityDescription->controller ?? JsonapiController::class;
            $entityConfig["versions"] = $entityDescription->versions ?? ['v1'];

            $config["jsonApi"][$index] = $entityConfig;
        }
        foreach ($config["restApi"] as $index => $class) {
            $reflectionClass = new \ReflectionClass($class);
            /** @var Entity $entityDescription */
            $entityDescription = $reader->getClassAnnotation($reflectionClass, Entity::class);

            $entityConfig["class"] = $class;
            $entityConfig["name"] = $entityDescription->name ?? u($class)->camel()->toString();
            $entityConfig["path"] = str_replace("_","-", $entityDescription->path ?? u($class)->snake()->toString());
            $entityConfig["controller"] = $entityDescription->controller ?? JsonapiController::class;
            $entityConfig["versions"] = $entityDescription->versions ?? ['v1'];

            $config["restApi"][$index] = $entityConfig;
        }
        foreach ($config["commandApi"] as $index => $class) {
            $reflectionClass = new \ReflectionClass($class);
            /** @var Entity $entityDescription */
            $entityDescription = $reader->getClassAnnotation($reflectionClass, Entity::class);

            $entityConfig["class"] = $class;
            $entityConfig["name"] = $entityDescription->name ?? u($class)->camel()->toString();
            $entityConfig["path"] = str_replace("_","-", $entityDescription->path ?? u($class)->snake()->toString());
            $entityConfig["controller"] = $entityDescription->controller ?? JsonapiController::class;
            $entityConfig["versions"] = $entityDescription->versions ?? ['v1'];

            $config["commandApi"][$index] = $entityConfig;
        }
        foreach ($config["graphQLApi"] as $index => $class) {
            $reflectionClass = new \ReflectionClass($class);
            /** @var Entity $entityDescription */
            $entityDescription = $reader->getClassAnnotation($reflectionClass, Entity::class);

            $entityConfig["class"] = $class;
            $entityConfig["name"] = $entityDescription->name ?? u($class)->camel()->toString();
            $entityConfig["path"] = str_replace("_","-", $entityDescription->path ?? u($class)->snake()->toString());
            $entityConfig["controller"] = $entityDescription->controller ?? JsonapiController::class;
            $entityConfig["versions"] = $entityDescription->versions ?? ['v1'];

            $config["graphQLApi"][$index] = $entityConfig;
        }
        return $config;
    }
}