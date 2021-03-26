<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Psr\Cache\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Contracts\Cache\CacheInterface;

class EntitiesDescriptor extends AbstractEntitiesDescriptor {
    protected $cache;
    protected $propertyInfo;

    public function __construct (CacheInterface $cache)
    {
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
     * @param array $entities
     *
     * @return EntityViewModel[]
     * @throws InvalidArgumentException
     */
    public function describe(string $cacheKey, array $entities) : array
    {
        return $this->cache->get($cacheKey, function () use ($entities) {
            $result = [];
            $names = [];
            foreach ($entities as $entity) {
                $entityModel = $this->getEntity($entity);
                if ($entityModel) {
                    $result[$entity["class"]] = $entityModel;
                    $names[$entity["class"]] = $entityModel->getName();
                }
            }
            $result = $this->normalize($result, $names);
            return $result;
        });
    }


    private function getEntity($entity) {
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

    private function getEntityProperty($entity, $field) {
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
    private function normalize($entities, $names) {
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