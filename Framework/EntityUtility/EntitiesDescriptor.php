<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Lturi\SymfonyExtensions\Framework\EntityUtility\Annotation\Entity;
use Lturi\SymfonyExtensions\JsonApi\Controller\JsonapiController;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Contracts\Cache\CacheInterface;
use function Symfony\Component\String\u;

class EntitiesDescriptor extends AbstractEntitiesDescriptor {
    protected $cache;
    protected $propertyInfo;
    protected $reader;

    public function __construct (
        CacheInterface $cache = null
    ) {
        $this->cache = $cache;

        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $this->propertyInfo = new PropertyInfoExtractor(
            [$reflectionExtractor],
            [$phpDocExtractor, $reflectionExtractor],
            [$phpDocExtractor],
            [$reflectionExtractor],
            [$reflectionExtractor]
        );

    }

    /**
     * @param string $type
     * @return array|null
     */
    public static function describeEntity(string $type): ?array
    {
        $reader = new AnnotationReader();
        try {
            $reflectionClass = new ReflectionClass($type);
            /** @var Entity $entityDescription */
            $entityDescription = $reader->getClassAnnotation($reflectionClass, Entity::class);

            $entityConfig["class"] = $type;
            $entityConfig["name"] = $entityDescription->name ?? u($type)->camel()->toString();
            $entityConfig["path"] = str_replace("_", "-", $entityDescription->path ?? u($type)->snake()->toString());
            $entityConfig["controller"] = $entityDescription->controller ?? JsonapiController::class;
            $entityConfig["versions"] = $entityDescription->versions ?? ['v1'];
            return $entityConfig;
        } catch (Exception $exception) {
            return null;
        }
    }

    /**
     * @param string $cachedKey
     * @param array $items
     * @return EntityViewModel[]
     * @throws InvalidArgumentException
     * @throws ReflectionException
     */
    public function describe(string $cachedKey, array $items) : array
    {
        $callback = function () use ($items) {
            $result = [];
            $names = [];
            foreach ($items as $entity) {
                $entityModel = $this->getEntity($entity);
                if ($entityModel) {
                    $result[$entity["class"]] = $entityModel;
                    $names[$entity["class"]] = $entityModel->getName();
                }
            }
            $result = $this->normalize($result, $names);
            return $result;
        };
        return
            $this->cache ?
            $this->cache->get($cachedKey, $callback) :
            $callback();
    }



    private function getEntity($entity): ?EntityViewModel
    {
        if (class_exists($entity["class"])) {
            $entityModel = new EntityViewModel();
            $properties = new ArrayCollection();
            $fields = $this->propertyInfo->getProperties($entity["class"]);
            foreach ($fields as $field) {
                $property = $this->getEntityProperty($entity, $field);
                if ($property) {
                    $properties->add($property);
                }
            }
            $entityModel
                ->setName($entity["name"])
                ->setClass($entity["class"])
                ->setProperties($properties);
            return $entityModel;
        }
        return null;
    }

    private function getEntityProperty($entity, $field): ?EntityPropertyViewModel
    {
        $type = $this->propertyInfo->getTypes($entity["class"], $field);
        if ($type) {
            $property = new EntityPropertyViewModel();
            $property
                ->setName($field)
                ->setPropertyType($type[0]);
            return $property;
        }
        return null;
    }

    /**
     * @param EntityViewModel[] $entities
     * @param                   $names
     *
     * @return EntityViewModel[]
     * @throws ReflectionException
     */
    private function normalize(array $entities, $names): array
    {
        $defaultNames = $this->getDefaultNames();
        foreach ($entities as $entity) {
            /** @var EntityPropertyViewModel $property */
            foreach ($entity->getProperties() as $property) {
                $propertyType = $property->computePropertyType();
                $property->setEntity(false);
                if (isset($names[$propertyType])) {
                    $property->setType($names[$propertyType]);
                    $property->setEntity(true);
                } elseif (isset($defaultNames[$propertyType])) {
                    $property->setType($defaultNames[$propertyType]);
                } else {
                    $class = new ReflectionClass($propertyType);
                    if (empty($class->getNamespaceName())) {
                        $property->setType($this->getDefaultObject());
                    } else {
                        $entity->getProperties()->removeElement($property);
                    }
                }
            }
        }
        return $entities;
    }
}