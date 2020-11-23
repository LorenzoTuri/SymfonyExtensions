<?php

namespace Lturi\SymfonyExtensions\JsonApi\Entity;

use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\JsonApi\Controller\JsonapiController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

// TODO: what does the $path param do?
class RouteDescriptor {
    public function describe($entities, $entitiesDescription) {
        $routes = new RouteCollection();
        foreach ($entities as $entity) {
            foreach ($entity["path"] as $path) {
                $routes->add(
                    $this->generateEntityName($entity, $path),
                    $this->generateRoute(
                        $this->generateEntityPath($entity, $path),
                        $this->generateEntityParams($entity, $path),
                        $this->getMethods($path["name"])
                    )
                );
                switch ($path["name"]) {
                    case "list":
                    case "get":
                    case "delete":
                    case "update":
                    case "create":
                        $this->loadEntityRelationRules($entitiesDescription, $entity, $path, $routes);
                        break;
                }
            }
        }
        return $routes;
    }

    private function loadEntityRelationRules($entitiesDescription, $entity, $path, RouteCollection $routes) {
        foreach ($entitiesDescription as $entityDescription) {
            if ($entityDescription->getName() == $entity["name"]) {
                foreach ($entityDescription->getProperties() as $property) {
                    if ($property->isEntity()) {
                        if ($this->isDetailPath($path)) {
                            $routes->add(
                                $this->generateEntityRelationName($entity, $path, $property),
                                $this->generateRoute(
                                    $this->generateEntityRelationPath($entity, $path, $property),
                                    $this->generateEntityRelationParams($entity, $path, $property),
                                    $this->getMethods($path["name"])
                                )
                            );
                        }
                    }
                }
            }
        }
    }

    private function generateEntityName($entity, $path) {
        return implode(".",[
            Constants::ENTITIES_NAME,
            $path["name"],
            $entity["name"],
        ]);
    }
    private function generateEntityRelationName($entity, $path, $property) {
        return implode(".", [
            Constants::ENTITIES_NAME,
            $path["name"],
            $entity["name"],
            $property->getType()
        ]);
    }
    private function generateEntityPath($entity, $path) {
        return implode("/", [
            Constants::ENTITIES_PATH,
            $entity["name"]
        ])."/".($this->isDetailPath($path) ? "{id}":"");
    }
    private function generateEntityRelationPath($entity, $path, $property) {
        return
            Constants::ENTITIES_PATH."/".
            $entity["name"].
            "/{id}/relationships/".
            $property->getType();
    }
    private function generateEntityParams($entity, $path) {
        return [
            '_controller' => $path["controller"],
            'entity' => $entity["name"]
        ];
    }
    private function generateEntityRelationParams($entity, $path, $property) {
        return [
            '_controller' => JsonapiController::class."::dispatchRelationRequest",
            'entity' => $entity["name"],
            'relatedEntity' => $property->getType()
        ];
    }

    private function getMethods($name) {
        if ($name == "list") return ["GET"];
        if ($name == "create") return ["CREATE"];
        if ($name == "get") return ["GET"];
        if ($name == "delete") return ["DELETE"];
        if ($name == "update") return ["PATCH"];
        return [];
    }

    private function isDetailPath($path) {
        return in_array($path["name"], ["get", "delete", "update"]);
    }

    private function generateRoute($path, $params, $methods) {
        return new Route($path, $params, array(), array(), '', array(), $methods);
    }
}