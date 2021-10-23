<?php

namespace Lturi\SymfonyExtensions\RestApi\Entity;

use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteDescriptor {
    public function describe($entities): RouteCollection
    {
        $routes = new RouteCollection();
        foreach ($entities as $entity) {
            foreach ($entity["versions"] as $version) {
                $routes->add(
                    $this->generateEntityName($entity, $version, "list"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, false),
                        $this->generateEntityParams($entity, $version, "list"),
                        ["GET"]
                    )
                );
                $routes->add(
                    $this->generateEntityName($entity, $version, "list.search"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, false, "search"),
                        $this->generateEntityParams($entity, $version, "list"),
                        ["POST"]
                    )
                );
                $routes->add(
                    $this->generateEntityName($entity, $version, "get"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, true),
                        $this->generateEntityParams($entity, $version, "get"),
                        ["GET"]
                    )
                );
                $routes->add(
                    $this->generateEntityName($entity, $version, "create"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, false),
                        $this->generateEntityParams($entity, $version, "create"),
                        ["POST"]
                    )
                );
                $routes->add(
                    $this->generateEntityName($entity, $version, "update"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, true),
                        $this->generateEntityParams($entity, $version, "update"),
                        ["PUT"]
                    )
                );
                $routes->add(
                    $this->generateEntityName($entity, $version, "delete"),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $version, true),
                        $this->generateEntityParams($entity, $version, "delete"),
                        ["DELETE"]
                    )
                );
            }
        }
        return $routes;
    }

    private function generateEntityName($entity, $version, $suffix): string
    {
        return implode(".",[
            "lturi.restApi",
            $entity["name"],
            $version,
            $suffix,
        ]);
    }
    private function generateEntityPath($entity, $version, bool $isDetail, $append = null): string
    {
        return
            "api/rest/" .
            $version . "/" .
            $entity["name"].
            ($isDetail ? "/{id}" : "").
            ($append ? "/$append" : "").
            "{trailingSlash}";
    }

    #[ArrayShape(['_controller' => "string", 'entity' => "mixed", 'version' => "", 'trailingSlash' => "string"])]
    private function generateEntityParams($entity, $version, $suffix): array
    {
        return [
            '_controller' => $entity["rest-controller"]."::".$suffix,
            'entity' => $entity["name"],
            'version' => $version,
            'trailingSlash' => '/'
        ];
    }

    private function generateRoute($path, $params, $methods): Route
    {
        return new Route($path, $params, array(), array(), '', array(), $methods);
    }
}