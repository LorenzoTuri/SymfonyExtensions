<?php

namespace Lturi\SymfonyExtensions\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Lturi\SymfonyExtensions\Services\Response\ApiResponse;
use Lturi\SymfonyExtensions\Classes\ViewModels\RouteViewModel;
use Symfony\Component\Routing\RouterInterface;

class RoutesController
{
    /**
     * @param Router      $router
     * @param ApiResponse $response
     *
     * @return ApiResponse
     */
    public function routeAction(RouterInterface $router, ApiResponse $response): ApiResponse
    {
        $collection = $router->getRouteCollection();
        $allRoutes = $collection->all();

        $routes = array(
            "api" => array(),
            "controllers" => array()
        );

        foreach ($allRoutes as $route => $params) {
            $defaults = $params->getDefaults();
            $version = "v1";

            if (isset($defaults['_controller'])) {
                $controllerAction = explode(':', $defaults['_controller']);
                $controller = $controllerAction[0];
                $method = $params->getMethods();
                if (empty($method)) {
                    $method = "GET";
                } elseif (is_array($method)) {
                    $method = $method[0];
                }

                $requirements = $params->getRequirements();

                if (stripos($route, "api") === 0) {
                    if (stripos($route, "api_v") === 0) {
                        [$context, $version, $trueRoute] = explode("_", $route, 3);
                    } else {
                        [$context, $trueRoute] = explode("_", $route, 2);
                    }

                    if (!isset($routes["api"][$trueRoute])) {
                        $routes["api"][$trueRoute] =
                            $this->convertController(
                                $route,
                                $method,
                                $controller,
                                $params->getPath(),
                                $version,
                                $requirements
                            );
                    } elseif ($routes["api"][$trueRoute]->getVersion() < $version) {
                        $routes["api"][$trueRoute] =
                            $this->convertController(
                                $route,
                                $method,
                                $controller,
                                $params->getPath(),
                                $version,
                                $requirements
                            );
                    }
                } elseif (stripos($route, "_") !== 0) {
                    $routes["controllers"][$route] =
                        $this->convertController(
                            $route,
                            $method,
                            $controller,
                            $params->getPath(),
                            $version,
                            $requirements
                        );
                }
            }
        }

        return $response->setResponse($routes);
    }

    /**
     * @param string $route
     * @param string $method
     * @param string $controller
     * @param string $path
     * @param string $version
     * @param array<string, mixed> $requirements
     * @return RouteViewModel
     */
    private function convertController(
        string $route = "",
        string $method = "",
        string $controller = "",
        string $path = "",
        string $version = "v1",
        array $requirements = []
    ): RouteViewModel {

        return (new RouteViewModel())
            ->setName($route)
            ->setMethods($method)
            ->setController($controller)
            ->setPath($path)
            ->setVersion($version)
            ->setRequirements($requirements);
    }
}
