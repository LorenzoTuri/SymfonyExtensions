<?php

namespace Lturi\SymfonyExtensions\JsonApi\Entity;

use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\JsonApi\Controller\JsonapiController;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class RouteDescriptor {
    public function describe($entities, $entitiesDescription) {
        $routes = new RouteCollection();
        foreach ($entities as $entity) {

            // TODO: on relations, delete/update only for single relation (?)

            $methodsMap = [
                "list" => [
                    "isDetail" => false,
                    "methods" => ['GET'],
                    "alternatives" => [[
                        "name" => "search",
                        "path" => "search",
                        "methods" => ['POST']
                    ]]
                ],
                "get" => [
                    "isDetail" => true,
                    "methods" => ['GET']
                ],
                "create" => [
                    "isDetail" => false,
                    "methods" => ['POST']
                ],
                "update" => [
                    "isDetail" => true,
                    "methods" => ['PUT']
                ],
                "delete" => [
                    "isDetail" => true,
                    "methods" => ['DELETE']
                ],
            ];
            foreach ($methodsMap as $name => $methods) {
                foreach ($entity["versions"] as $version) {
                    $routes->add(
                        $this->generateEntityName($entity, $version, $name),
                        $this->generateRoute(
                            $this->generateEntityPath($entity, $version, $methods["isDetail"]),
                            $this->generateEntityParams($entity, $version, $name),
                            $methods["methods"]
                        )
                    );
                    if (isset($methods["alternatives"])) {
                        foreach ($methods["alternatives"] as $alternative) {
                            $routes->add(
                                $this->generateEntityName($entity, $version, $name.".".$alternative["name"]),
                                $this->generateRoute(
                                    $this->generateEntityPath($entity, $version, $methods["isDetail"], $alternative["path"]),
                                    $this->generateEntityParams($entity, $version, $name),
                                    $alternative["methods"]
                                )
                            );
                        }
                    }
                }
                $this->loadEntityRelationRules($entitiesDescription, $entity, $routes);
            }
        }
        return $routes;
    }

    private function loadEntityRelationRules($entitiesDescription, $entity, RouteCollection $routes) {
        foreach ($entitiesDescription as $entityDescription) {
            if ($entityDescription->getName() == $entity["name"]) {
                foreach ($entity["versions"] as $version) {
                    foreach ($entityDescription->getProperties() as $property) {
                        if ($property->isEntity()) {
                            if ($property->isCollection()) {
                                $routes->add(
                                    $this->generateEntityRelationName($entity, $property, "list"),
                                    $this->generateRoute(
                                        $this->generateEntityRelationPath($entity, $version, $property),
                                        $this->generateEntityRelationParams($entity, $property, $version, "list"),
                                        [ 'GET' ]
                                    )
                                );
                                // Let's add another "search" endpoint to enable filtering by post (it's not properly json-api related
                                // But it's really usefull using APIs)
                                $routes->add(
                                    $this->generateEntityRelationName($entity, $property, "list.search"),
                                    $this->generateRoute(
                                        $this->generateEntityRelationPath($entity, $version, $property, "search"),
                                        $this->generateEntityRelationParams($entity, $property, $version, "list"),
                                        ['POST']
                                    )
                                );
                            } else {
                                $routes->add(
                                    $this->generateEntityRelationName($entity, $property, "get"),
                                    $this->generateRoute(
                                        $this->generateEntityRelationPath($entity, $version, $property),
                                        $this->generateEntityRelationParams($entity, $property, $version, "get"),
                                        [ 'GET' ]
                                    )
                                );
                            }
                            $routes->add(
                                $this->generateEntityRelationName($entity, $property, 'update'),
                                $this->generateRoute(
                                    $this->generateEntityRelationPath($entity, $version, $property),
                                    $this->generateEntityRelationParams($entity, $property, $version, 'update'),
                                    [ 'UPDATE' ]
                                )
                            );
                            $routes->add(
                                $this->generateEntityRelationName($entity, $property, 'delete'),
                                $this->generateRoute(
                                    $this->generateEntityRelationPath($entity, $version, $property),
                                    $this->generateEntityRelationParams($entity, $property, $version, 'delete'),
                                    [ 'DELETE' ]
                                )
                            );
                        }
                    }
                }
            }
        }
    }

    private function generateEntityName($entity, $version, $suffix) {
        return implode(".",[
            "lturi.jsonApi",
            $entity["name"],
            $version,
            $suffix,
        ]);
    }
    private function generateEntityRelationName($entity, $property, $suffix) {
        return implode(".", [
            "lturi.jsonApi",
            $entity["name"],
            $property->getType(),
            $suffix
        ]);
    }
    private function generateEntityPath($entity, $version, bool $isDetail, $append = null) {
        return
            "api/" .
            $version . "/" .
            $entity["name"].
            ($isDetail ? "/{id}" : "").
            ($append ? "/$append" : "").
            "{trailingSlash}";
    }
    private function generateEntityRelationPath($entity, $version, $property, $append = null) {
        return
            "api/" .
            $version . "/" .
            $entity["name"] . "/" .
            "{id}/relationships/" .
            $property->getType().
            ($append ? "/$append" : "").
            "{trailingSlash}";
    }
    private function generateEntityParams($entity, $version, $suffix) {
        return [
            '_controller' => $entity["controller"]."::".$suffix,
            'entity' => $entity["name"],
            'version' => $version,
            'trailingSlash' => '/'
        ];
    }
    private function generateEntityRelationParams($entity, $property, $version, $suffix) {
        return [
            '_controller' => JsonapiController::class."::".$suffix."Relation",
            'entity' => $entity["name"],
            'relatedEntity' => $property->getType(),
            'version' => $version,
            'trailingSlash' => '/'
        ];
    }

    private function generateRoute($path, $params, $methods) {
        return new Route($path, $params, array(), array(), '', array(), $methods);
    }
}