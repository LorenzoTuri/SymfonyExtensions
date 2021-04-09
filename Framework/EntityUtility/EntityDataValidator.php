<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Lturi\SymfonyExtensions\Framework\Exception\UnauthorizedUserException;
use ReflectionClass;
use Symfony\Bridge\Doctrine\PropertyInfo\DoctrineExtractor;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class EntityDataValidator {
    protected $entityManager;
    protected $entityDescriptor;
    protected $authorizationChecker;
    protected $propertyInfo;
    protected $reader;

    public function __construct(
        EntityManager $entityManager,
        EntitiesDescriptor $entityDescriptor,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        $this->entityManager = $entityManager;
        $this->entityDescriptor = $entityDescriptor;
        $this->authorizationChecker = $authorizationChecker;

        $reflectionExtractor = new ReflectionExtractor();
        $doctrineExtractor = new DoctrineExtractor($entityManager);
        $this->propertyInfo = new PropertyInfoExtractor(
            [$reflectionExtractor, $doctrineExtractor],
            [$reflectionExtractor, $doctrineExtractor]
        );

        $this->reader = new AnnotationReader();
    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @param string $prefix
     * @param string $action
     * @param string $entityName
     * @param string $type
     * @param array|null $requestContent
     * @return bool
     * @throws UnauthorizedUserException
     */
    public function validateData(
        ParameterBagInterface $parameterBag,
        string $prefix,
        string $action,
        string $entityName,
        string $type,
        ?array $requestContent
    ): bool
    {
        // For starters, let's check for authorizationChecker of $type
        $this->throwOnUnauthorized(
            $parameterBag,
            $prefix,
            $action,
            $entityName,
            $type,
            $requestContent
        );

        // Then go into recursion, checking only $requestContent shared properties.
        $properties = $this->propertyInfo->getProperties($type);
        $sharedProperties = $requestContent ? array_filter($properties, function($property) {
            return isset($requestContent[$property]);
        }) : [];
        foreach ($sharedProperties as $property) {
            $propertyTypes = $this->propertyInfo->getTypes($type, $property);

            /** @var Type $propertyType */
            foreach ($propertyTypes as $propertyType) {
                $propertyClass = $propertyType->getClassName();
                $propertyCollectionClass = $propertyType->getCollectionValueType()->getClassName();
                $propertyEntityDescription = $this->entityDescriptor::describeEntity($propertyCollectionClass);
                if ($propertyType->isCollection() && $this->isEntity($propertyCollectionClass)) {
                    foreach ($requestContent[$property] as $requestContentPropertySingle) {
                        $this->validateData(
                            $parameterBag,
                            $prefix,
                            $action,
                            $propertyEntityDescription ? $propertyEntityDescription["name"] : null,
                            $propertyCollectionClass,
                            $requestContentPropertySingle
                        );
                    }
                } else if ($this->isEntity($propertyClass)){
                    $propertyClassDescription = $this->entityDescriptor::describeEntity($propertyClass);
                    $this->validateData(
                        $parameterBag,
                        $prefix,
                        $action,
                        $propertyClassDescription ? $propertyClassDescription["name"] : null,
                        $propertyClass,
                        $requestContent[$property]
                    );
                }
            }
        }
        return true;
    }

    public function removeInvalidData(
        string $type,
        ?array $requestContent
    ) {

    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @param string $prefix
     * @param string $action
     * @param string $entityName
     * @param string $type
     * @param array|null $requestContent
     * @throws UnauthorizedUserException
     */
    protected function throwOnUnauthorized(
        ParameterBagInterface $parameterBag,
        string $prefix,
        string $action,
        string $entityName,
        string $type,
        ?array $requestContent
    ) {
        if (!$this->authorizationChecker->isGranted([
            sprintf("%s.%s", $prefix, $action),
            sprintf("%s.%s", $prefix, $entityName),
            sprintf("%s.%s.%s", $prefix, $action, $entityName)
        ], [
            "parameters" => $parameterBag,
            "type" => $type,
            "entityData" => $requestContent
        ])) {
            throw new UnauthorizedUserException();
        }
    }

    /**
     * @param $class
     * @return bool
     */
    protected function isEntity($class): bool
    {
        try {
            $reflectionClass = new ReflectionClass($class);
            $classAnnotation = $this->reader->getClassAnnotations($reflectionClass);
            return in_array(Entity::class, array_map("get_class", $classAnnotation));
        } catch (Exception $exception) {
            return true;
        }
    }
}