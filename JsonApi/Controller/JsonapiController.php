<?php

namespace Lturi\SymfonyExtensions\JsonApi\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManagerInterface;
use Fourtwosix\Entity\Site;
use Fourtwosix\Entity\SiteUrl;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityManagerDoctrine;
use Lturi\SymfonyExtensions\Framework\EntityUtility\RouteDescriptor;
use Lturi\SymfonyExtensions\JsonApi\Service\Normalizer\JsonapiNormalizer;
use Lturi\SymfonyExtensions\JsonApi\Service\Response\JsonapiResponse;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use OpenApi\Analysis;
use OpenApi\Annotations\Info;
use OpenApi\Processors\AugmentOperations;
use OpenApi\Processors\AugmentParameters;
use OpenApi\Processors\AugmentProperties;
use OpenApi\Processors\AugmentSchemas;
use OpenApi\Processors\BuildPaths;
use OpenApi\Processors\CleanUnmerged;
use OpenApi\Processors\InheritInterfaces;
use OpenApi\Processors\InheritProperties;
use OpenApi\Processors\InheritTraits;
use OpenApi\Processors\MergeIntoComponents;
use OpenApi\Processors\MergeIntoOpenApi;
use OpenApi\Processors\MergeJsonContent;
use OpenApi\Processors\MergeXmlContent;
use OpenApi\Processors\OperationId;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonapiController
{
    protected $entitiesDescriptor;
    protected $entitiesDescription;
    protected $routeDescriptor;
    protected $routeDescription;
    protected $serializer;
    protected $entityManager;

    protected $entities;

    public function __construct (
        ContainerInterface $container,
        AbstractEntitiesDescriptor $entitiesDescriptor,
        RouteDescriptor $routeDescriptor,
        EntityManagerInterface $entityManager
    ) {
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->routeDescriptor = $routeDescriptor;

        // TODO: find a way to get parameter without container
        $this->entities = $container->getParameter("jsonApiEntities");
        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedJsonApiEntities", $this->entities);
        $this->routeDescription = $this->routeDescriptor->describe($this->entities, $this->entitiesDescription);
        $this->entityManager = new EntityManagerDoctrine($entityManager);

        $encoders = [new JsonEncoder()];
        $normalizers = [
            new JsonapiNormalizer(
                $this->entitiesDescription,
            ),
            new ArrayDenormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
            new ObjectNormalizer(),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * List all entitites corresponding to the selected entity
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function list(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity
    ): JsonResponse {
        $requestContent = $this->loadContent($request);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $entityData = $this->entityManager->list($entity->getClass(), $requestContent, $request);
        $results = $this->serializer->normalize($entityData, "json", [
            JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
        ]);
        return $apiResponse->setSuccess($results);
    }

    /**
     * Get a single entity by id
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function get(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity
    ): JsonResponse {
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $id = $request->attributes->get("id", null);
        $entityData = $this->entityManager->find($entity->getClass(), $id);
        if ($entityData) {
            $results = $this->serializer->normalize($entityData, "json", [
                JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
            ]);
            return $apiResponse->setSuccess($results);
        } else {
            // TODO: not found message should be a single one?
            return $apiResponse->setError([ "Not found" ],null, null, 404);
        }
    }

    /**
     * Create a single entity
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function create(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity
    ): JsonResponse {
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $entityClass = $entity->getClass();
        $requestContent = $this->loadContent($request);
        $entityData = $this->serializer->denormalize(
            $requestContent,
            $entityClass,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => new $entityClass(),
                JsonapiNormalizer::ENTITY_MANAGER => $this->entityManager,
                JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
            ]
        );
        $this->entityManager->save($entityData);
        die("what should i do now here?? The response of the create should be...");
        $results = $this->serializer->normalize($entityData, "json", [
            JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
        ]);
        return $apiResponse->setSuccess("", "", "", 204);
    }

    /**
     * Update a single entity
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function update(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity
    ): JsonResponse {
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $id = $request->attributes->get("id", null);
        $entityClass = $entity->getClass();
        $requestContent = $this->loadContent($request);
        $entityData = $this->entityManager->find($entityClass, $id);
        if ($entityData) {
            $entityData = $this->serializer->denormalize(
                $requestContent,
                $entityClass,
                'json',
                [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $entityData,
                    JsonapiNormalizer::ENTITY_MANAGER => $this->entityManager,
                    JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
                ]
            );
            $this->entityManager->save($entityData);
            return $apiResponse->setSuccess("", "", "", 204);
        } else {
            // TODO: not found message should be a single one?
            return $apiResponse->setError([ "Not found" ],null, null, 404);
        }
    }

    /**
     * Delete a single entity
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     */
    public function delete(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity
    ): JsonResponse {
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $id = $request->attributes->get("id", null);
        $entityData = $this->entityManager->find($entity->getClass(), $id);
        if ($entityData) {
            $entityData = $this->entityManager->delete($entity->getClass(), $id);
            return $apiResponse->setSuccess("", "", "", 204);
        } else {
            // TODO: not found message should be a single one?
            return $apiResponse->setError([ "Not found" ],null, null, 404);
        }
    }

    /**
     * Get the relation of an entity, only for single entities
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @param $relatedEntity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function getRelation(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity,
        $relatedEntity
    ): JsonResponse {
        $id = $request->attributes->get("id", null);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);

        $entityData = $this->entityManager->find($entity->getClass(), $id);

        $results = null;
        if ($entityData) {
            /** @var EntityPropertyViewModel $property */
            foreach ($entity->getProperties() as $property) {
                if ($property->getType() == $relatedEntity) {
                    $method = "get".$property->getName();
                    if (method_exists($entityData, $method)) {
                        $results = $this->serializer->normalize(call_user_func([$entityData, $method]), "json", [
                            JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
                        ]);
                    }
                }
            }
        }
        if ($results) {
            return $apiResponse->setSuccess($results);
        } else {
            // TODO: not found message should be a single one?
            return $apiResponse->setError([ "Not found" ],null, null, 404);
        }
    }

    /**
     * Get the relation of an entity, only for collections
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @param $relatedEntity
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function listRelation(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity,
        $relatedEntity
    ): JsonResponse {
        $requestContent = $this->loadContent($request);
        $id = $request->attributes->get("id", null);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);
        $entityData = $this->entityManager->find($entity->getClass(), $id);

        $results = null;
        if ($entityData) {
            /** @var EntityPropertyViewModel $property */
            foreach ($entity->getProperties() as $property) {
                // TODO: this shoud fail in case the entity is related multiple times to another entity,
                // or if the property name is not properly named (?)
                if ($property->getType() == $relatedEntity) {
                    $method = "get".$property->getName();
                    if (method_exists($entityData, $method)) {
                        $results = $this->entityManager->listRelation($entityData, $requestContent, $method, $request);
                    }
                }
            }
        }
        if ($results) {
            $results = $this->serializer->normalize($results, "json", [
                JsonapiNormalizer::ROUTE_DESCRIPTION => $this->routeDescriptor->describe($this->entities, $this->entitiesDescription)
            ]);
            return $apiResponse->setSuccess($results);
        } else {
            // TODO: not found message should be a single one?
            return $apiResponse->setError([ "Not found" ],null, null, 404);
        }
    }

    public function updateRelation(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity,
        $relatedEntity
    ): JsonResponse {
        // TODO: implement -> update ONLY relation data, trigger errors etc...
    }

    public function deleteRelation(
        Request $request,
        JsonapiResponse $apiResponse,
        $entity,
        $relatedEntity
    ): JsonResponse {
        // TODO: implement -> delete ONLY relation data, trigger errors etc...
    }

    /**
     * Detect the correct entity description, given entity name and descriptions
     * @param $entitiesDescription
     * @param $entity
     *
     * @return EntityViewModel|null
     */
    private function detectEntity($entitiesDescription, $entity) {
        return array_reduce($entitiesDescription, function ($carry, $entityDescription) use ($entity) {
            return $carry ? $carry : ($entityDescription->getName() == $entity ? $entityDescription : null);
        });
    }

    /**
     * Load the content from the whole request (body has the most precedence, then post, then get)
     * @param Request $request
     * @return array
     */
    private function loadContent(Request $request) {
        $getContent = $request->query->all();
        $postContent = $request->request->all();
        $bodyContent = [];
        try { $bodyContent = $request->toArray(); } catch (\Exception $exception) {}
        return array_merge($getContent, $postContent, $bodyContent);
    }
}
