<?php

namespace Lturi\SymfonyExtensions\Services;

use Lturi\SymfonyExtensions\Classes\Constants;
use Lturi\SymfonyExtensions\Classes\Entities\EntitiesDescriptor;
use Lturi\SymfonyExtensions\Classes\Entities\RouteDescriptor;
use Lturi\SymfonyExtensions\Controller\Api\EntitiesController;
use Lturi\SymfonyExtensions\Controller\Api\JsonapiController;
use Lturi\SymfonyExtensions\Controller\Api\RoutesController;
use Lturi\SymfonyExtensions\Controller\Api\TranslationController;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends Loader
{
    protected $container;
    protected $entityDescriptor;
    protected $routeDescriptor;

    public function __construct (
        ContainerInterface $container,
        EntitiesDescriptor $entitiesDescriptor,
        RouteDescriptor $routeDescriptor
    ) {
        $this->container = $container;
        $this->entityDescriptor = $entitiesDescriptor;
        $this->routeDescriptor = $routeDescriptor;
    }

    public function load($resource, string $type = null)
    {
        $routes = new RouteCollection();
        if ($this->container->getParameter(Constants::LOAD_ROUTES)) {
            $routes->add(
                Constants::ROUTES_NAME,
                $this->generateRoute(
                    Constants::ROUTES_PATH,
                    RoutesController::class.'::routeAction'
                )
            );
        }
        if ($this->container->getParameter(Constants::LOAD_TRANSLATIONS)) {
            $routes->add(
                Constants::TRANSLATIONS_NAME,
                $this->generateRoute(
                    Constants::TRANSLATIONS_PATH,
                    TranslationController::class.'::getAllRequest'
                )
            );
        }
        if ($this->container->getParameter(Constants::LOAD_ENTITIES)) {
            $routes->add(
                Constants::ENTITIES_NAME,
                $this->generateRoute(
                    Constants::ENTITIES_PATH,
                    EntitiesController::class.'::getAllRequest'
                )
            );

            $entities = $this->container->getParameter(Constants::ENTITIES);
            $entitiesDescription = $this->entityDescriptor->describe($entities);
            $entityRoutes = $this->routeDescriptor->describe($entities, $entitiesDescription);
            $routes->addCollection($entityRoutes);
        }
        return $routes;
    }
    public function supports($resource, string $type = null): bool {
        return $resource == "lturi_symfony_extensions";
    }

    private function generateRoute($path, $controller) {
        return new Route(
            $path,
            [ '_controller' => $controller ],
            array(),
            array(),
            '',
            array(),
            array('GET')
        );
    }
}