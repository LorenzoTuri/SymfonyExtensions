<?php

namespace Lturi\SymfonyExtensions\GraphQLApi\Controller;

use Exception;
use GraphQL\Error\DebugFlag;
use GraphQL\GraphQL;
use GraphQL\Utils\SchemaPrinter;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\GraphQLApi\Type\SchemaGenerator;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Throwable;
use Traversable;

/**
 * TODO: remove authorization skip
 * TODO: make events
 * TODO: errors
 */
class GraphQLController
{
    protected $entities;
    protected $entitiesDescriptor;
    protected $entityManager;
    protected $eventDispatcher;

    protected $entitiesDescription;
    protected $serializer;


    public function __construct (
        $entities,
        AbstractEntitiesDescriptor $entitiesDescriptor,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher = null,
    ) {
        $this->entities = $entities;
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;

        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedGraphQLApiEntities", $this->entities);

        $encoders = [new JsonEncoder()];
        $normalizers = [
            new ArrayDenormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
            new ObjectNormalizer(),
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

        $parameterBag = new ParameterBag([
            "request" => $request
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
        // TODO: Debug only dev...
        $debug = DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE;
        $resultArray = $this->serializer->normalize($result->toArray($debug));
        return new JsonResponse($resultArray);
    }

    /**
     * TODO: take care: I should refactor the whole loadContent, since I need a "data" param
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
        return array_merge($getContent, $postContent, $bodyContent);
    }

    public function callableGet($type, $entityName, $args, ParameterBag $context) {
        try {
            return $this->entityManager->find(
                $context,
                $type,
                $entityName,
                isset($args["id"]) ? $args["id"] : null,
                true
            );
        } catch (Exception $exception) {
            dump($exception);
            die();
        }
    }

    public function callableList($type, $entityName, $args, ParameterBag $context): ?Traversable
    {
        try {
            dump($args);
            die();
            // TODO: filters not implemented
            $filters = $args;

            return $this->entityManager->list(
                $context,
                $type,
                $entityName,
                $filters,
                true
            );
        } catch (Exception $exception) {
            dump($exception);
            die();
        }
    }

    public function callableUpdate($type, $entityName, $args, ParameterBag $context) {
        try {
            return $this->entityManager->save(
                $context,
                $type,
                $entityName,
                isset($args["id"]) ? $args["id"] : null,
                $args,
                true
            );
        } catch (Throwable $exception) {
            dump($exception);
            die();
        }
    }

    public function callableDelete($type, $entityName, $args, ParameterBag $context): bool
    {
        try {
            return $this->entityManager->delete(
                $context,
                $type,
                $entityName,
                isset($args["id"]) ? $args["id"] : null,
                true
            );
        } catch (Throwable $exception) {
            dump($exception);
            die();
        }
    }
}
