<?php

namespace Lturi\SymfonyExtensions\GraphQLApi\Controller;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityDataValidator;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\GraphQLApi\Type\SchemaGenerator;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * TODO: make events
 */
class GraphQLController
{
    protected $entities;
    protected $entitiesDescriptor;
    protected $entityManager;
    protected $entityDataValidator;
    protected $eventDispatcher;
    protected $appEnv;

    protected $entitiesDescription;
    protected $serializer;


    public function __construct (
        $entities,
        AbstractEntitiesDescriptor $entitiesDescriptor,
        EntityManagerInterface $entityManager,
        EntityDataValidator $entityDataValidator,
        EventDispatcherInterface $eventDispatcher,
        $appEnv
    ) {
        $this->entities = $entities;
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->entityManager = $entityManager;
        $this->entityDataValidator = $entityDataValidator;
        $this->eventDispatcher = $eventDispatcher;
        $this->appEnv = $appEnv;

        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedGraphQLApiEntities", $this->entities);

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return spl_object_hash($object);
            },
        ];
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $encoders = [new JsonEncoder()];
        $normalizers = [
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
            new GetSetMethodNormalizer(
                null,
                null,
                $extractor,
                null,
                null,
                $defaultContext
            ),
            new ArrayDenormalizer(),
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     * @throws ExceptionInterface
     * @throws ReflectionException
     */
    public function query(
        Request $request,
    ): JsonResponse {
        $requestContent = $this->loadContent($request);
        $version = "1";
        // TODO: version should come from url, not from this hardcoded variable

        $parameterBag = new ParameterBag([
            "request" => $request,
            "env" => $this->appEnv,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        $schemaGenerator = new SchemaGenerator($this->entities);
        $schema = $schemaGenerator->generate(
            [$this, "callableGet"],
            [$this, "callableList"],
            [$this, "callableDelete"],
            [$this, "callableUpdate"],
        );

        $result = GraphQL::executeQuery(
            $schema,
            $requestContent["data"],
            null,
            $parameterBag,
        );

        $debug = $this->appEnv == "prod" ?
            DebugFlag::NONE :
            (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE);

        $resultArray = $this->serializer->normalize($result->toArray($debug));
        return new JsonResponse($resultArray);
    }

    /**
     * Load the content from the whole request (body has the most precedence, then post, then get)
     * @param Request $request
     * @return array
     */
    private function loadContent(Request $request): array
    {
        $getContent = $request->query->all();
        $postContent = $request->request->all();
        $bodyContent = [];
        try { $bodyContent = $request->toArray(); } catch (Exception $exception) {}
        $fullRequest = array_merge($getContent, $postContent, $bodyContent);
        if (!isset($fullRequest["data"])) $fullRequest["data"] = [];
        return $fullRequest;
    }

    public function callableGet($type, $entityName, $args, ParameterBag $context): ?array
    {
        $result = $this->entityManager->find(
            $context,
            $type,
            $entityName,
            isset($args["id"]) ? $args["id"] : null,
            true
        );
        return $this->entityDataValidator->validateData(
            $context,
            "asd",
            "get",
            $entityName,
            $type,
            $this->serializer->normalize($result),
            false
        );
    }

    public function callableList($type, $entityName, $args, ParameterBag $context): array
    {
        $limit = null;
        $page = null;
        $filters = [];
        foreach ($args as $argName => $argValue) {
            if ($argName == "filters") {
                $normalFilters = json_decode($argValue, true);
                if ($normalFilters) $filters = array_merge($filters, $normalFilters);
            } else if ($argName == "limit") {
                $limit = $argValue;
            } else if ($argName == "page") {
                $page = $argValue;
            } else {
                $filters[] = [
                    "field" => $argName,
                    "type" => "equals",
                    "value" => $argValue
                ];
            }
        }
        $results = $this->entityManager->list(
            $context,
            $type,
            $entityName,
            array_filter([
                "limit" => $limit,
                "page" => $page,
                "filters" => [[
                    "type" => "and",
                    "value" => $filters
                ]]
            ])
        );
        return $results->map(function($result) use ($type, $entityName, $context) {
            return $this->entityDataValidator->validateData(
                $context,
                "asd",
                "get",
                $entityName,
                $type,
                $this->serializer->normalize($result),
                false
            );
        });
    }

    public function callableUpdate($type, $entityName, $args, ParameterBag $context): ?array
    {
        $result = $this->entityManager->save(
            $context,
            $type,
            $entityName,
            isset($args["id"]) ? $args["id"] : null,
            $args
        );
        return $this->entityDataValidator->validateData(
            $context,
            "asd",
            "get",
            $entityName,
            $type,
            $this->serializer->normalize($result),
            false
        );
    }

    public function callableDelete($type, $entityName, $args, ParameterBag $context): bool
    {
        return $this->entityManager->delete(
            $context,
            $type,
            $entityName,
            isset($args["id"]) ? $args["id"] : null
        );
    }
}
