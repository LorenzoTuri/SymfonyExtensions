<?php

namespace Lturi\SymfonyExtensions\Framework\Service;

use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\Entity\EntitiesDescriptor;
use Lturi\SymfonyExtensions\Framework\Controller\EntitiesController;
use Lturi\SymfonyExtensions\Framework\Controller\RoutesController;
use Lturi\SymfonyExtensions\Framework\Controller\TranslationController;
use Lturi\SymfonyExtensions\JsonApi\Entity\RouteDescriptor;
use Psr\Cache\InvalidArgumentException;
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

    /**
     * @param mixed       $resource
     * @param string|null $type
     *
     * @return RouteCollection
     * @throws InvalidArgumentException
     */
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
                    TranslationController::class.'::getSimpleRequest'
                )
            );
            $routes->add(
                Constants::TRANSLATIONS_FULL_NAME,
                $this->generateRoute(
                    Constants::TRANSLATIONS_FULL_PATH,
                    TranslationController::class.'::getFullRequest'
                )
            );
        }
        $entities = $this->container->getParameter("jsonApiEntities");
        $entitiesDescription = $this->entityDescriptor->describe("cachedJsonApiEntities", $entities);
        $entityRoutes = $this->routeDescriptor->describe($entities, $entitiesDescription);
        $routes->addCollection($entityRoutes);

        // TODO: move some of the default routes to the routes.yaml
        /* Load static routes */
        $routes->addCollection($this->import(
            '@LturiSymfonyExtensionsBundle/Resources/config/routes.yaml',
            'yaml'
        ));

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