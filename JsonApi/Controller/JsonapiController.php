<?php

namespace Lturi\SymfonyExtensions\JsonApi\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\JsonApi\Entity\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\JsonApi\Entity\EntityManagerDoctrine;
use Lturi\SymfonyExtensions\JsonApi\Service\Normalizer\JsonapiNormalizer;
use Lturi\SymfonyExtensions\JsonApi\Service\Response\JsonapiResponse;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
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

    public function __construct (ContainerInterface $container = null, AbstractEntitiesDescriptor $entitiesDescriptor = null, EntityManagerInterface $entityManager = null)
    {
        $this->entitiesDescriptor = $entitiesDescriptor;
        // TODO: find a way to get parameter without container
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

    /**
     * @param Request         $request
     * @param JsonapiResponse $apiResponse
     * @param                 $entity
     *
     * @return JsonResponse
     * @throws ExceptionInterface
     */
    public function dispatchRequest(Request $request, JsonapiResponse $apiResponse, $entity): JsonResponse
    {
        $id = $request->attributes->get("id", null);
        $entity = $this->detectEntity($this->entitiesDescription, $entity);

        $entityData = $this->entityManager->find($entity->getClass(), $id);
        $results = $this->serializer->normalize($entityData, "json");
        return $apiResponse->setSuccess($results);
    }

    /**
     * @param Request         $request
     * @param JsonapiResponse $apiResponse
     * @param                 $entity
     * @param                 $relatedEntity
     *
     * @return JsonResponse
     * @throws ExceptionInterface
     */
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

    /**
     * @param $entitiesDescription
     * @param $entity
     *
     * @return EntityViewModel|null
     */
    private function detectEntity($entitiesDescription, $entity) {
        /** @var EntityViewModel $entityDescription */
        foreach ($entitiesDescription as $entityDescription) {
            if ($entityDescription->getName() == $entity) return $entityDescription;
        }
        return null;
    }

    /**
     * TODO: what does it do?
     * @param Request $request
     * @param         $entity
     *
     * @return array|mixed|object
     * @throws ExceptionInterface
     */
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
