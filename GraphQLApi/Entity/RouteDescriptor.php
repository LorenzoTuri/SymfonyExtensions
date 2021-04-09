<?php

namespace Lturi\SymfonyExtensions\GraphQLApi\Entity;

use Lturi\SymfonyExtensions\GraphQLApi\Controller\GraphQLController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteDescriptor {
    public function describe(): RouteCollection
    {
        $routes = new RouteCollection();
        $routes->add(
            "lturi.graphQLApi.controller",
            new Route(
                "api/graphQL/{trailingSlash}",
                [
                    '_controller' => GraphQLController::class."::query",
                    'trailingSlash' => '/'
                ],
                [],
                [],
                '',
                [],
                ["GET", "POST"]
            )
        );
        return $routes;
    }
}