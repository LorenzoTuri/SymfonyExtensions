<?php

namespace Lturi\SymfonyExtensions\JsonApi\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Fourtwosix\Entity\Site;
use Fourtwosix\Entity\SiteUrl;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\Entity\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Framework\Entity\EntityManagerDoctrine;
use Lturi\SymfonyExtensions\JsonApi\Entity\OpenApiDescriptor;
use Lturi\SymfonyExtensions\JsonApi\Entity\Processor\OpenApiProcessor;
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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class InfoController
{
    protected $entitiesDescriptor;
    protected $entitiesDescription;
    protected $serializer;

    protected $entities;

    public function __construct (
        ContainerInterface $container,
        AbstractEntitiesDescriptor $entitiesDescriptor,
    ) {
        $this->entitiesDescriptor = $entitiesDescriptor;
        // TODO: find a way to get parameter without container
        $this->entities = $container->getParameter("jsonApiEntities");
        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedJsonApiEntities", $this->entities);
    }

    /**
     * @param Request $request
     * @param JsonapiResponse $apiResponse
     * @param $entity
     * @return JsonResponse
     */
    public function info(
        Request $request,
        JsonapiResponse $apiResponse
    ): JsonResponse {

        $processor = new OpenApiProcessor($this->entitiesDescription);

        $files = array_map(function($entity) {
            $reflector = new \ReflectionClass($entity["class"]);
            return $reflector->getFileName();
        }, $this->entities);

        $openapi = \OpenApi\scan($files, [
            "processors" => [
                [$processor, "describe"],
                new MergeIntoOpenApi(),
                new MergeIntoComponents(),
                new InheritInterfaces(),
                new InheritTraits(),
                new AugmentSchemas(),
                new AugmentProperties(),
                new BuildPaths(),
                new InheritProperties(),
                new AugmentOperations(),
                new AugmentParameters(),
                new MergeJsonContent(),
                new MergeXmlContent(),
                new OperationId(),
                new CleanUnmerged(),
                // If not necessary, use this instead the list of new object above
                //...Analysis::processors()
            ]
        ]);

        return $apiResponse->setJson($openapi->toJson());
    }
}
