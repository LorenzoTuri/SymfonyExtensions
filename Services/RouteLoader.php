<?php

namespace Lturi\SymfonyExtensions\Services;

use Lturi\SymfonyExtensions\Classes\Constants;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteLoader extends Loader
{
    /** @var ContainerInterface  */
    protected $container;

    public function __construct (ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function load($resource, string $type = null)
    {
        $routes = new RouteCollection();
        if ($this->container->getParameter(Constants::LOAD_ROUTES)) {
            $routes->add(Constants::ROUTES_NAME, new Route(
                Constants::ROUTES_PATH, [
                '_controller' => 'Lturi\\SymfonyExtensions\\Controller\\Api\\RoutesController::routeAction',
            ],
                array(),
                array(),
                '',
                array(),
                array('GET')
            ));
        }
        if ($this->container->getParameter(Constants::LOAD_TRANSLATIONS)) {
            $routes->add(Constants::TRANSLATIONS_NAME, new Route(
                Constants::TRANSLATIONS_PATH, [
                '_controller' => 'Lturi\\SymfonyExtensions\\Controller\\Api\\TranslationController::getAllRequest',
            ],
                array(),
                array(),
                '',
                array(),
                array('GET')
            ));
        }
        return $routes;
    }

    public function supports($resource, string $type = null): bool {
        return $resource == "lturi_symfony_extensions";
    }
}