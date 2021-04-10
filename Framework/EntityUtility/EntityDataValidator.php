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
     * @param bool $throwOnUnauthorized
     * @return array|null
     * @throws UnauthorizedUserException
     */
    public function validateData(
        ParameterBagInterface $parameterBag,
        string $prefix,
        string $action,
        string $entityName,
        string $type,
        ?array $requestContent,
        bool $throwOnUnauthorized = true
    ): ?array
    {
        try {
            // For starters, let's check for authorizationChecker of $type
            $this->throwOnUnauthorized(
                $parameterBag,
                $prefix,
                $action,
                $entityName,
                $type,
                $requestContent
            );
        } catch (Exception $exception) {
            if ($throwOnUnauthorized) throw $exception;
            else return null;
        }

        // Then go into recursion, checking only $requestContent shared properties.
        $properties = $this->propertyInfo->getProperties($type);
        $sharedProperties = $requestContent ? array_filter($properties, function($property) use ($requestContent) {
            return array_key_exists($property, $requestContent);
        }) : [];
        foreach ($sharedProperties as $property) {
            $propertyTypes = $this->propertyInfo->getTypes($type, $property);

            /** @var Type $propertyType */
            foreach ($propertyTypes as $propertyType) {
                $propertyClass = $propertyType->getClassName();
                if ($propertyType->isCollection()) {
                    $propertyCollectionClass = $propertyType->getCollectionValueType()->getClassName();
                    $propertyEntityDescription = $this->entityDescriptor::describeEntity($propertyCollectionClass);
                    if ($this->isEntity($propertyCollectionClass)) {
                        foreach ($requestContent[$property] as $propertyKey => $requestContentPropertySingle) {
                            $requestContent[$property][$propertyKey] = $this->validateData(
                                $parameterBag,
                                $prefix,
                                $action,
                                $propertyEntityDescription ? $propertyEntityDescription["name"] : null,
                                $propertyCollectionClass,
                                $requestContentPropertySingle,
                                $throwOnUnauthorized
                            );
                        }
                    }
                } else if ($this->isEntity($propertyClass)){
                    $propertyClassDescription = $this->entityDescriptor::describeEntity($propertyClass);
                    $requestContent[$property] = $this->validateData(
                        $parameterBag,
                        $prefix,
                        $action,
                        $propertyClassDescription ? $propertyClassDescription["name"] : null,
                        $propertyClass,
                        $requestContent[$property],
                        $throwOnUnauthorized
                    );
                }
            }
        }
        return $requestContent;
    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @param string|null $prefix
     * @param string|null $action
     * @param string|null $entityName
     * @param string|null $type
     * @param array $requestContent
     * @throws UnauthorizedUserException
     */
    protected function throwOnUnauthorized(
        ParameterBagInterface $parameterBag,
        ?string $prefix,
        ?string $action,
        ?string $entityName,
        ?string $type,
        array $requestContent
    ) {
        if (!$this->authorizationChecker->isGranted(array_unique(array_filter([
            implode(".",array_filter([$action])),
            implode(".",array_filter([$entityName])),
            implode(".",array_filter([$action, $entityName])),
            implode(".",array_filter([$prefix, $action])),
            implode(".",array_filter([$prefix, $entityName])),
            implode(".",array_filter([$prefix, $action, $entityName]))
        ])), [
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
            return false;
        }
    }
}