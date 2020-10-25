<?php

namespace Lturi\SymfonyExtensions\Controller\Api;

use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Classes\Constants;
use Lturi\SymfonyExtensions\Classes\Entities\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Classes\Entities\EntityManagerDoctrine;
use Lturi\SymfonyExtensions\Classes\ViewModels\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Classes\ViewModels\EntityViewModel;
use Lturi\SymfonyExtensions\Services\Normalizers\JsonapiNormalizer;
use Lturi\SymfonyExtensions\Services\Normalizers\StreamNormalizer;
use Lturi\SymfonyExtensions\Services\Response\JsonapiResponse;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class JsonapiController
{
    protected $entitiesDescriptor;
    protected $entitiesDescription;
    protected $serializer;
    protected $entityManager;

    protected $entities;

    public function __construct (ContainerInterface $container = null, AbstractEntitiesDescriptor $entitiesDescriptor = null, EntityManagerInterface $entityManager)
    {
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->entities = $container->getParameter(Constants::ENTITIES);
        $this->entitiesDescription = $this->entitiesDescriptor->describe($this->entities);
        $this->entityManager = new EntityManagerDoctrine($entityManager);

        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
            new JsonapiNormalizer(
                $this->entitiesDescription
            ),
            new ObjectNormalizer(),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    public function dispatchRequest(Request $request, JsonapiResponse $apiResponse, $entity): JsonResponse
    {
        $id = $request->attributes->get("id", null);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);

        $entityData = $this->entityManager->find($entity->getClass(), $id);
        $results = $this->serializer->normalize($entityData, "json");
        return $apiResponse->setSuccess($results);
    }

    public function dispatchRelationRequest(Request $request, JsonapiResponse $apiResponse, $entity, $relatedEntity): JsonResponse
    {
        $id = $request->attributes->get("id", null);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);

        $entityData = $this->entityManager->find($entity->getClass(), $id);

        $results = null;
        if ($entityData) {
            /** @var EntityPropertyViewModel $property */
            foreach ($entity->getProperties() as $property) {
                if ($property->getName() == $relatedEntity) {
                    $method = "get".$property->getName();
                    if (method_exists($entityData, $method)) {
                        $results = $this->serializer->normalize(call_user_func([$entityData, $method]), "json");
                    }
                }
            }
        }
        return $apiResponse->setSuccess($results);
    }

    private function detectEntity($entitiesDescription, $entity) {
        /** @var EntityViewModel $entityDescription */
        foreach ($entitiesDescription as $entityDescription) {
            if ($entityDescription->getName() == $entity) return $entityDescription;
        }
        return null;
    }

    private function injectData(Request $request, $entity) {
        $content = $request->getContent();
        $content = json_decode($content, true);
        return $this->serializer->denormalize(
            ["data"=> $content],
            $entity->getClass(),
            null,
            [
                JsonapiNormalizer::ENTITY_MANAGER => $this->entityManager
            ]
        );
    }
}
