<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;


use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\LazyCriteriaCollection;
use Lturi\SymfonyExtensions\Framework\Exception\UnrecognizableFilterException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

class EntityManagerDoctrine implements EntityManagerInterface {
    protected $entityManager;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    /**
     * Load repository by class and return entity or null
     * @param string $type
     * @param $id
     * @return object|null
     */
    function find (string $type, $id): ?object
    {
        return $this->entityManager->getRepository($type)->find($id);
    }

    /**
     * Load repository by class, the filter by request content. Eventually load criteria from request
     * @param string $type
     * @param array $requestContent
     * @param Request|ParameterBagInterface $request
     * @return Collection|LazyCriteriaCollection
     * @throws UnrecognizableFilterException
     */
    public function list(
        string $type,
        array $requestContent,
        Request|ParameterBagInterface $request
    ): Collection|LazyCriteriaCollection
    {
        $criteria = $this->buildCriteria($requestContent, $request);
        /** @var EntityRepository $entityRepository */
        $entityRepository = $this->entityManager->getRepository($type);
        return $entityRepository->matching($criteria);
    }

    /**
     * Load repository and entity by class, then delete it
     * @param string $type
     * @param $id
     */
    public function delete(string $type, $id)
    {
        $entity = $this->entityManager->getRepository($type)->find($id);
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
    }

    /**
     * Save entity data
     * @param $entity
     */
    public function save($entity) {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    /**
     * Load entity relation data by building criteria like list method, using on collection retrieved by
     * $entity $method
     * @param $entity
     * @param array $requestContent
     * @param string $method
     * @param Request|ParameterBagInterface $request
     * @return mixed
     * @throws UnrecognizableFilterException
     */
    public function listRelation(
        $entity,
        array $requestContent,
        string $method,
        Request|ParameterBagInterface $request
    ): mixed
    {
        $criteria = $this->buildCriteria($requestContent, $request);
        $relations = call_user_func([$entity, $method]);
        return $relations->matching($criteria);
    }

    /**
     * @param $requestContent
     * @param Request|ParameterBagInterface $request
     * @return Criteria
     * @throws UnrecognizableFilterException
     */
    private function buildCriteria($requestContent, Request|ParameterBagInterface $request): Criteria
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
     *      "type" => "{type of query, default to eq}",
     *      "value" => "{searchedValue}",
     * ]
     * Value field may vary depending on type, ex on type and, value should be an array of sub filters.
     * Field is ignored on some queries, like and
     * Value is ignored in isNull and notNull filters
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

                // TODO: proper exception
                default: throw new UnrecognizableFilterException($type);
            }
            return new Comparison($field, $comparison, new Value($value));
        }
    }
}