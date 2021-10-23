<?php

namespace Lturi\SymfonyExtensions\RestApi\Controller;

use Doctrine\Common\Collections\Collection;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityDataValidator;
use Lturi\SymfonyExtensions\Framework\EntityUtility\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Exception\UnauthorizedUserException;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Lturi\SymfonyExtensions\RestApi\Event\ControllerGetEventPostFilter;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\UidNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * This controller is responsible to handle the whole process of request detection and
 * response format.
 *
 * TODO: refactor
 * TODO: events
 * TODO: tests
 */
class RestApiController
{
    protected $entities;
    protected $entitiesDescriptor;
    protected $entityManager;
    protected $entityDataValidator;
    protected $eventDispatcher;

    protected $entitiesDescription;
    protected $serializer;

    public function __construct (
        $entities,
        AbstractEntitiesDescriptor $entitiesDescriptor,
        EntityManagerInterface $entityManager,
        EntityDataValidator $entityDataValidator,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->entities = $entities;
        $this->entitiesDescriptor = $entitiesDescriptor;
        $this->entityManager = $entityManager;
        $this->entityDataValidator = $entityDataValidator;
        $this->eventDispatcher = $eventDispatcher;
        $this->entitiesDescription = $this->entitiesDescriptor->describe("cachedRestApiEntities", $this->entities);

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return ["id" => $object->getId()];
            },
            AbstractNormalizer::IGNORED_ATTRIBUTES => ["__initializer__", "__cloner__","__isInitialized__"]
        ];
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $encoders = [new JsonEncoder(), new XmlEncoder(), new YamlEncoder(), new CsvEncoder()];
        $normalizers = [
            new UidNormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
            new ArrayDenormalizer(),
            new ObjectNormalizer(
                null,
                null,
                null,
                $extractor,
                null,
                null,
                $defaultContext
            )
        ];
        $this->serializer = new Serializer($normalizers, $encoders);
    }

    /**
     * List all entitites corresponding to the selected entity
     * @param Request $request
     * @param $entity
     * @param $version
     * @return Response
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     */
    public function list(
        Request $request,
        $entity,
        $version
    ): Response {
        $requestContent = $this->loadContent($request);
        $entityDescription = $this->detectEntity($this->entitiesDescription, $entity);
        $entityName = $entityDescription->getName();
        $type = $entityDescription->getClass();

        $parameterBag = new ParameterBag([
            "request" => $request,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        /** @var Collection $result */
        $result = $this->entityManager->list(
            $parameterBag,
            $type,
            $entityName,
            $requestContent
        );

        // TODO: event???

        // Load some data before serializing, else the collection will return false data
        $totalCount = $result->count();
        $limit = $this->entityManager->detectLimit($requestContent);
        $page = $this->entityManager->detectPage($requestContent);

        $results = [
            "data" => array_map(function($entity) use ($type, $entityName, $parameterBag) {
                return $this->entityDataValidator->validateData(
                    $parameterBag,
                    "restApi",
                    "list",
                    $entityName,
                    $type,
                    $entity,
                    false
                );
            }, $this->serializer->normalize($result)),
            "meta" => [
                "total" => $totalCount,
                "page" => $page,
                "limit" => $limit
            ]
        ];

        return $this->formatResponse($request, $results);
    }

    /**
     * Get a single entity by id
     * @param Request $request
     * @param $entity
     * @param $id
     * @param $version
     * @return Response
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     */
    public function get(
        Request $request,
        $entity,
        $id,
        $version
    ): Response {
        $requestContent = $this->loadContent($request);
        $entityDescription = $this->detectEntity($this->entitiesDescription, $entity);
        $entityName = $entityDescription->getName();
        $type = $entityDescription->getClass();

        $parameterBag = new ParameterBag([
            "request" => $request,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        $result = $this->entityManager->find(
            $parameterBag,
            $type,
            $entityName,
            $id
        );

        $event = new ControllerGetEventPostFilter(
            $parameterBag,
            $type,
            $id,
            $result,
            []
        );

        $this->eventDispatcher->dispatch($event);

        // TODO: meta?
        $results = [
            "data" => $this->entityDataValidator->validateData(
                $parameterBag,
                "restApi",
                "find",
                $entityName,
                $type,
                $this->serializer->normalize(
                    $event->getResult(),
                    null,
                    $event->getContext()
                ),
                false
            ),
            "meta" => []
        ];

        return $this->formatResponse($request, $results);
    }

    /**
     * Create a single entity
     * @param Request $request
     * @param $entity
     * @param $version
     * @return Response
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     */
    public function create(
        Request $request,
        $entity,
        $version
    ): Response {
        $requestContent = $this->loadContent($request);
        $entityDescription = $this->detectEntity($this->entitiesDescription, $entity);
        $entityName = $entityDescription->getName();
        $type = $entityDescription->getClass();

        $parameterBag = new ParameterBag([
            "request" => $request,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        $result = $this->entityManager->save(
            $parameterBag,
            $type,
            $entityName,
            null,
            $requestContent
        );

        // TODO: event???

        // TODO: meta?
        $results = [
            "data" => $this->entityDataValidator->validateData(
                $parameterBag,
                "restApi",
                "save",
                $entityName,
                $type,
                $this->serializer->normalize($result),
                false
            )
        ];

        return $this->formatResponse($request, $results);
    }

    /**
     * Update a single entity
     * @param Request $request
     * @param $entity
     * @param $id
     * @param $version
     * @return Response
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     */
    public function update(
        Request $request,
        $entity,
        $id,
        $version
    ): Response {
        $requestContent = $this->loadContent($request);
        $entityDescription = $this->detectEntity($this->entitiesDescription, $entity);
        $entityName = $entityDescription->getName();
        $type = $entityDescription->getClass();

        $parameterBag = new ParameterBag([
            "request" => $request,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        $result = $this->entityManager->save(
            $parameterBag,
            $type,
            $entityName,
            $id,
            $requestContent
        );

        // TODO: event???

        // TODO: meta?
        $results = [
            "data" => $this->entityDataValidator->validateData(
                $parameterBag,
                "restApi",
                "save",
                $entityName,
                $type,
                $this->serializer->normalize($result),
                false
            )
        ];

        return $this->formatResponse($request, $results);
    }

    /**
     * Delete a single entity
     * @param Request $request
     * @param $entity
     * @param $id
     * @param $version
     * @return Response
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     */
    public function delete(
        Request $request,
        $entity,
        $id,
        $version
    ): Response {
        $requestContent = $this->loadContent($request);
        $entityDescription = $this->detectEntity($this->entitiesDescription, $entity);
        $entityName = $entityDescription->getName();
        $type = $entityDescription->getClass();

        $parameterBag = new ParameterBag([
            "request" => $request,
            "version" => $version,
            "entitiesDescription" => $this->entitiesDescription,
            "requestContent" => $requestContent
        ]);

        $result = $this->entityManager->delete(
            $parameterBag,
            $type,
            $entityName,
            $id
        );

        // TODO: event???

        // TODO: meta?
        $results = [
            "data" => $result
        ];

        return $this->formatResponse($request, $results);
    }

    /**
     * Detect the correct entity description, given entity name and descriptions
     * @param $entitiesDescription
     * @param $entity
     *
     * @return EntityViewModel|null
     */
    private function detectEntity($entitiesDescription, $entity): ?EntityViewModel
    {
        return array_reduce($entitiesDescription, function ($carry, $entityDescription) use ($entity) {
            return $carry ? $carry : ($entityDescription->getName() == $entity ? $entityDescription : null);
        });
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

        // Request type
        $contentType = AcceptHeader::fromString($request->headers->get("Content-Type", "application/json"))->first()->getValue();

        try {
            switch ($this->simplifyMimeType($contentType)) {
                case "xml": $bodyContent = $this->serializer->decode($request->getContent(), "xml"); break;
                case "yaml": $bodyContent = $this->serializer->decode($request->getContent(), "yaml"); break;
                case "csv": $bodyContent = $this->serializer->decode($request->getContent(), "csv"); break;
                case "json": default: $bodyContent = $request->toArray();
            }
        } catch (\Exception $exception) {}
        return array_merge((array)$getContent, (array)$postContent, (array)$bodyContent);
    }

    private function formatResponse(Request $request, $results) {
        // Response type
        $accept = AcceptHeader::fromString($request->headers->get("Accept", "application/json"))->first()->getValue();

        switch ($this->simplifyMimeType($accept)) {
            case "xml":
                return new Response(
                    $this->serializer->serialize($results, "xml"),
                    200,
                    ["Content-Type" => $accept]
                );
            case "yaml":
                return new Response(
                    $this->serializer->serialize($results, "yaml", [YamlEncoder::YAML_INLINE => 4]),
                    200,
                    ["Content-Type" => $accept]
                );
            case "csv":
                return new Response($this->serializer->serialize($results, "csv"),
                    200,
                    ["Content-Type" => $accept]
                );
            case "json":
            default:
                return new JsonResponse($results);
        }
    }

    private function simplifyMimeType($mimeType): string {
        switch ($mimeType) {
            case "application/xml": return "xml";

            case "text/vnd.yaml":
            case "text/yaml":
            case "text/x-yaml":
            case "application/x-yaml": return "yaml";

            case "text/csv": return "csv";

            case "application/json":
            case "application/ld+json": default: return "json";
        }
    }
}
