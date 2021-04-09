<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\LazyCriteriaCollection;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerDeleteEventPostFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerDeleteEventPreFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerGetEventPostFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerGetEventPreFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerListEventPostFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerListEventPreFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerSaveEventPostFilter;
use Lturi\SymfonyExtensions\Framework\Event\EntityManagerSaveEventPreFilter;
use Lturi\SymfonyExtensions\Framework\Exception\EntityValidationException;
use Lturi\SymfonyExtensions\Framework\Exception\UnauthorizedUserException;
use Lturi\SymfonyExtensions\Framework\Exception\UnrecognizableFilterException;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\EntityNormalizer;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\StreamNormalizer;
use ReflectionException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

// TODO: hei i write here but...  implements fucking traits, and support most used...
class EntityManagerDoctrine implements EntityManagerInterface {
    protected $entityManager;
    protected $eventDispatcher;
    protected $validator;
    protected $entityDataValidator;

    protected $serializer;

    public function __construct(
        \Doctrine\ORM\EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        ValidatorInterface $validator,
        EntityDataValidator $entityDataValidator
    ) {
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->validator = $validator;
        $this->entityDataValidator = $entityDataValidator;

        $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return spl_object_hash($object);
            },
        ];
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);
        $normalizers = [
            new EntityNormalizer(
                $this->entityManager
            ),
            new GetSetMethodNormalizer(
                null,
                null,
                $extractor,
                null,
                null,
                $defaultContext
            ),
            new ArrayDenormalizer(),
            new DateTimeNormalizer(),
            new StreamNormalizer(),
        ];
        $this->serializer = new Serializer($normalizers, []);
    }

    /**
     * Load repository by class and return entity or null
     * @param ParameterBagInterface $parameterBag
     * @param string $type
     * @param string $entityName
     * @param $id
     * @param bool $removeAuthorizationCheck
     * @return mixed
     * @throws UnauthorizedUserException
     */
    function find (
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        bool $removeAuthorizationCheck = false
    ): mixed {
        $eventData = $this->eventDispatcher->dispatch(new EntityManagerGetEventPreFilter(
            $parameterBag,
            $type,
            $id,
            (
                $removeAuthorizationCheck ||
                $this->entityDataValidator->validateData(
                    $parameterBag,
                    "entityManager",
                    "get",
                    $entityName,
                    $type,
                    null
                )
            )
        ));

        $entityData =  $this->entityManager->getRepository($eventData->getType())->find($eventData->getId());

        $eventData = $this->eventDispatcher->dispatch(new EntityManagerGetEventPostFilter(
            $parameterBag,
            $type,
            $entityData
        ));
        return $eventData->getEntityData();
    }

    /**
     * Load repository by class, the filter by request content. Eventually load criteria from request
     * @param ParameterBagInterface $parameterBag
     * @param string $type
     * @param string $entityName
     * @param array $requestContent
     * @param bool $removeAuthorizationCheck
     * @return Collection|LazyCriteriaCollection
     * @throws UnrecognizableFilterException
     * @throws UnauthorizedUserException
     */
    public function list(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        array $requestContent,
        bool $removeAuthorizationCheck = false
    ): Collection|LazyCriteriaCollection
    {
        $eventData = $this->eventDispatcher->dispatch(new EntityManagerListEventPreFilter(
            $parameterBag,
            $type,
            $requestContent,
            (
                $removeAuthorizationCheck ||
                $this->entityDataValidator->validateData(
                    $parameterBag,
                    "entityManager",
                    "list",
                    $entityName,
                    $type,
                    $requestContent
                )
            )
        ));

        $criteria = $this->buildCriteria($eventData->getParameterBag(), $eventData->getRequestContent());
        $entityRepository = $this->entityManager->getRepository($eventData->getType());
        $matchingEntities = $entityRepository->matching($criteria);

        $eventData = $this->eventDispatcher->dispatch(new EntityManagerListEventPostFilter(
            $parameterBag,
            $type,
            $matchingEntities
        ));
        return $eventData->getMatchingEntities();
    }

    /**
     * Load repository and entity by class, then delete it
     * @param ParameterBagInterface $parameterBag
     * @param string $type
     * @param string $entityName
     * @param $id
     * @param bool $removeAuthorizationCheck
     * @return bool
     * @throws UnauthorizedUserException
     */
    public function delete(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        bool $removeAuthorizationCheck = false
    ): bool {
        $eventData = $this->eventDispatcher->dispatch(new EntityManagerDeleteEventPreFilter(
            $parameterBag,
            $type,
            $id,
            (
                $removeAuthorizationCheck ||
                $this->entityDataValidator->validateData(
                    $parameterBag,
                    "entityManager",
                    "delete",
                    $entityName,
                    $type,
                    null
                )
            )
        ));

        $entityData = $this->entityManager->getRepository($eventData->getType())->find($eventData->getId());
        $this->entityManager->remove($entityData);
        $this->entityManager->flush();

        $this->eventDispatcher->dispatch(new EntityManagerDeleteEventPostFilter(
            $parameterBag,
            $type,
            $entityData
        ));

        return true;
    }

    /**
     * Save entity data
     * @param ParameterBagInterface $parameterBag
     * @param string $type
     * @param string $entityName
     * @param $id
     * @param array $requestContent
     * @param bool $removeAuthorizationCheck
     * @return mixed
     * @throws ExceptionInterface
     * @throws UnauthorizedUserException
     * @throws EntityValidationException
     */
    public function save(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        array $requestContent,
        bool $removeAuthorizationCheck = false
    ): mixed {
        // No need to check for authorization, if the true auth should check save
        $entityData =
            $id ?
            $this->find($parameterBag, $type, $entityName, $id, true) :
            new $type();

        $entityData = $this->serializer->denormalize(
            $requestContent,
            $type,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $entityData
            ]
        );

        $eventData = $this->eventDispatcher->dispatch(new EntityManagerSaveEventPreFilter(
            $parameterBag,
            $type,
            $entityData,
            (
                $removeAuthorizationCheck ||
                $this->entityDataValidator->validateData(
                    $parameterBag,
                    "entityManager",
                    "save",
                    $entityName,
                    $type,
                    $requestContent
                )
            )
        ));

        $errors = $this->validator->validate($eventData->getEntityData());
        if (count($errors) > 0) {
            throw new EntityValidationException($errors, $entityName);
        }

        $this->entityManager->persist($eventData->getEntityData());
        $this->entityManager->flush();

        $eventData = $this->eventDispatcher->dispatch(new EntityManagerSaveEventPostFilter(
            $parameterBag,
            $type,
            $entityData
        ));
        return $eventData->getEntityData();
    }

    /**
     * @param $requestContent
     * @param ParameterBagInterface $request
     * @return Criteria
     * @throws UnrecognizableFilterException
     */
    private function buildCriteria(ParameterBagInterface $request, $requestContent): Criteria
    {
        $limit = isset($requestContent["limit"]) ? (int)$requestContent["limit"] : 10;
        $page = isset($requestContent["page"]) ? (int)$requestContent["page"] : 0;
        $filters = isset($requestContent["filters"]) ? $requestContent["filters"] : [];

        if ($request->has("CRITERIA")) {
            $criteria = $request->get("CRITERIA");
        } else {
            // TODO: limit and page default values should be configurable by yaml files
            $criteria = new Criteria();
            $criteria->setFirstResult($page * $limit);
            $criteria->setMaxResults($limit);
        }

        if ($filters) {
            foreach ($filters as $filter) {
                $comparison = $this->buildFilterExpression($filter);
                $criteria->andWhere($comparison);
            }
        }
        return $criteria;
    }

    /**
     * Filter should be something like [
     *      "field" => "{propertyName}"
     *      "type" => "{type of query, default to equals}",
     *      "value" => "{searchedValue}",
     * ]
     * Value field may vary depending on type, ex on type and, value should be an array of sub filters.
     * Value is ignored in isNull and notNull filters
     * Field is ignored on some queries, like and
     *
     * @param $filter
     * @return Comparison|CompositeExpression|null
     * @throws UnrecognizableFilterException
     */
    private function buildFilterExpression($filter): CompositeExpression|Comparison|null
    {
        if (!$filter) return null;
        $field = isset($filter["field"]) ? $filter["field"] : "";
        $value = isset($filter["value"]) ? $filter["value"] : null;
        $type = isset($filter["type"]) ? $filter["type"] : "equals";

        if ($type === "and") {
            return new CompositeExpression(CompositeExpression::TYPE_AND, array_map(function ($single) {
                return $this->buildFilterExpression($single);
            }, $value));
        } else if ($type === "or") {
            return new CompositeExpression(CompositeExpression::TYPE_OR, array_map(function ($single) {
                return $this->buildFilterExpression($single);
            }, $value));
        } else {
            $comparison = null;
            switch ($type) {
                case "equals": $comparison = Comparison::EQ; break;
                case "isNull": $comparison = Comparison::EQ; $value = null; break;
                case "notEquals": $comparison = Comparison::NEQ; break;
                case "notNull": $comparison = Comparison::NEQ; $value = null; break;

                case "startsWith": $comparison = Comparison::STARTS_WITH; break;
                case "endsWith": $comparison = Comparison::ENDS_WITH; break;
                case "greater": $comparison = Comparison::GT; break;
                case "greaterEquals": $comparison = Comparison::GTE; break;
                case "lower": $comparison = Comparison::LT; break;
                case "lowerEquals": $comparison = Comparison::LTE; break;

                case "like":
                case "contains": $comparison = Comparison::CONTAINS; break;
                case "in": $comparison = Comparison::IN; break;
                case "notIn": $comparison = Comparison::NIN; break;

                default: throw new UnrecognizableFilterException($type);
            }
            return new Comparison($field, $comparison, new Value($value));
        }
    }
}