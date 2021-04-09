<?php

namespace Lturi\SymfonyExtensions\JsonApi\Service\Normalizer;

use ArrayObject;
use Doctrine\Inflector\Inflector;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\String\Inflector\EnglishInflector;

class JsonapiNormalizer extends AbstractNormalizer implements DenormalizerAwareInterface, NormalizerAwareInterface
{
    const ROUTE_DESCRIPTION = "jsonapi.route.description";
    const ENTITY_MANAGER = "jsonapi.entity.manager";

    protected $entitiesDescription;
    protected $englishInflector;

    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    /**
     * @param EntityViewModel[]                  $entitiesDescription
     * @param ClassMetadataFactoryInterface|null $classMetadataFactory
     * @param NameConverterInterface|null        $nameConverter
     * @param array|null                         $defaultContext
     */
    public function __construct(
        array $entitiesDescription,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    ) {
        $this->entitiesDescription = $entitiesDescription;
        $this->englishInflector = new EnglishInflector();

        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $defaultContext
        );
    }

    /**
     * Only supports object normalization (or array of objects...), since the normalization request should be made from entities.
     * @param mixed       $data
     * @param string|null $format
     *
     * @return bool
     */
    public function supportsNormalization($data, string $format = null)
    {
        if (is_object($data)) {
            return !!$this->getEntityDescription($data);
        }
        return false;
    }

    /**
     * Only support array de-normalization, and only inside object that is an entity
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     *
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return
            is_array($data) &&
            array_reduce($this->entitiesDescription, function($context, EntityViewModel $item) use ($type) {
                return $context || $item->getClass() == $type;
            }, false);
    }

    /**
     * Normalize object to array, formatted as jsonapi specs.
     * This function is used for other types then objects internally (like bool, floats...),
     * but is precluded from external use by supportNormalization
     *
     * @param mixed       $object
     * @param string|null $format
     * @param array       $context
     * @param int         $depth
     *
     * @return array|ArrayObject|bool|float|int|mixed|string|null
     * @throws ExceptionInterface
     */
    public function normalize ($object, string $format = null, array $context = [], $depth = 0)
    {
        if (is_null($object) || !$this->supportsNormalization($object)) return $object;
        // TODO: inject links into entity
        /** @var RouteCollection $routes */
        $routes = isset($context[self::ROUTE_DESCRIPTION]) ? $context[self::ROUTE_DESCRIPTION] : new RouteCollection();

        $entity = $this->getEntityDescription($object);
        $result = [
            "id" => (string)$object->getId(),
            "type" => $entity->getName(),
            "links" => [
                "self" => $this->getEntityLink($routes, $entity, $object->getId()),
            ]
        ];
        if ($depth >= 1) {
            return $result;
        }

        $result["attributes"] = [];
        $result["relationships"] = [];

        /** @var EntityPropertyViewModel $property */
        foreach ($entity->getProperties() as $property) {
            if ($property->getName() == "id") continue;

            $obj = call_user_func(array($object, "get".$property->getName()));
            $content = null;
            if ($property->isCollection()) {
                $localResult = [];
                foreach ($obj as $localObj) {
                    $localResult[] = $this->normalize($localObj, $format, $context, ($depth + 1));
                }
                if ($property->isEntity()) {
                    $content = [
                        "links" => [
                            "self" => $this->getEntityPropertyLink($routes, $entity, $object->getId(), $property)
                        ],
                        "data" => $localResult
                    ];
                } else {
                    $content = $localResult;
                }
            } else {
                if ($this->supportsNormalization($obj)) {
                    $content = $this->normalize($obj, $format, $context, ($depth + 1));
                } else {
                    $content = $this->normalizer->normalize($obj);
                }
            }
            if ($property->isEntity()) {
                if (!$property->isCollection()) {
                    $content["links"] = [
                        "self" => $this->getEntityPropertyLink($routes, $entity, $object->getId(), $property),
                        "related" => $this->getEntityLink(
                            $routes,
                            $this->entitiesDescription[$property->getPropertyType()->getClassName()],
                            $content["id"]
                        )
                    ];
                }
                $result["relationships"][$property->getName()] = $content;
            } else {
                $result["attributes"][$property->getName()] = $content;
            }
        }
        return $result;
    }

    /**
     * Denormalize array to object. Only supports array de-normalization.
     * Objects are automatically created, unless provided
     *
     * @param mixed        $data
     * @param string       $type
     * @param string|null  $format
     * @param array<mixed> $context
     *
     * @return mixed|string
     * @throws ExceptionInterface
     * @throws ReflectionException
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        // TODO: i've removed it but... denormalize should work on "data" => ["whatever"...]
        //  restore it...
        $entityManager = (isset($context[self::ENTITY_MANAGER]) && $context[self::ENTITY_MANAGER]) ?
            $context[self::ENTITY_MANAGER] :
            null;

        $object = (isset($context[AbstractNormalizer::OBJECT_TO_POPULATE]) && $context[AbstractNormalizer::OBJECT_TO_POPULATE]) ?
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] :
            new $type();
        $entityDescription = $this->getEntityDescriptionByClass($type);

        if ($entityManager && isset($data["id"]) && !isset($context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            $object = $entityManager->find($type, $data["id"]);
            if (!$object) {
                // TODO: proper exception (or move it to entity manager?)
                throw new \Exception("Entity not found");
            }
        }

        /** @var EntityPropertyViewModel $property */
        foreach ($entityDescription->getProperties() as $property) {
            // TODO: this is a problem on update: always new relations get's created
            if ($property->getName() == "id") continue;

            $methodName = "set".$property->getName();
            $addMethodName = $this->englishInflector->singularize("add".$property->getName())[0];

            if (isset($data[$property->getName()])) {
                if (method_exists($object, $addMethodName)) {
                    if ($property->isCollection()) {
                        $propertyType =
                            $property->getPropertyType()->getCollectionValueType() ?
                                $property->getPropertyType()->getCollectionValueType()->getClassName() :
                                $property->getPropertyType()->getClassName();
                        foreach ($data[$property->getName()] as $singleRelation) {
                            call_user_func(
                                array($object, $addMethodName),
                                $this->denormalizer->denormalize(
                                    $singleRelation,
                                    $propertyType,
                                    null,
                                    [
                                        self::ENTITY_MANAGER => $entityManager
                                    ]
                                )
                            );
                        }
                    } else {
                        call_user_func(
                            array($object, $addMethodName),
                            $this->denormalizer->denormalize(
                                $singleRelation,
                                $propertyType,
                                null,
                                [
                                    self::ENTITY_MANAGER => $entityManager
                                ]
                            )
                        );
                    }
                } else if (method_exists($object, $methodName)) {
                    if ($property->isEntity()) {
                        call_user_func(
                            array($object, $methodName),
                            $this->denormalizer->denormalize(
                                $data[$property->getName()],
                                $property->getPropertyType()->getClassName(),
                                null,
                                [
                                    self::ENTITY_MANAGER => $entityManager
                                ]
                            )
                        );
                    } else {
                        call_user_func(
                            array($object, $methodName),
                            $data[$property->getName()]
                        );
                    }
                }
            }
        }
        return $object;
    }

    /** Get description for a single object
     *
     * @param $data
     *
     * @return EntityViewModel|null
     */
    private function getEntityDescription($data) {
        foreach (array_keys($this->entitiesDescription) as $entityClass) {
            if ($data instanceof $entityClass) return $this->entitiesDescription[$entityClass];
        }
        return null;
    }

    /** Get description for a class
     *
     * @param $class
     *
     * @return EntityViewModel|null
     */
    private function getEntityDescriptionByClass($class) {
        foreach (array_keys($this->entitiesDescription) as $entityClass) {
            if ($entityClass == $class) return $this->entitiesDescription[$entityClass];
        }
        return null;
    }

    private function getEntityLink(
        RouteCollection $routes,
        EntityViewModel $entity,
        $id
    ) {
        return str_replace("{trailingSlash}", "", str_replace("{id}", $id, array_reduce($routes->all(), function($carry, Route $route) use ($entity) {
            if ($carry) return $carry;
            if (
                $route->getDefault("entity") == $entity->getName() &&
                stripos($route->getPath(), "{id}") !== false
            ) {
                return $route->getPath();
            }
        }, null)));
    }

    private function getEntityPropertyLink(
        RouteCollection $routes,
        EntityViewModel $entity,
        $id,
        EntityPropertyViewModel $property
    ) {
        return str_replace("{trailingSlash}", "", str_replace("{id}", $id, array_reduce($routes->all(), function($carry, Route $route) use ($entity, $property) {
            if ($carry) return $carry;
            if (
                $route->getDefault("entity") == $entity->getName() &&
                $route->getDefault("relatedEntity") == $property->getType() &&
                stripos($route->getPath(), "{id}") !== false
            ) {
                return $route->getPath();
            }
        }, null)));
    }
}
