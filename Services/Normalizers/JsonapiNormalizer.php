<?php

namespace Lturi\SymfonyExtensions\Services\Normalizers;

use ArrayObject;
use Lturi\SymfonyExtensions\Classes\ViewModels\EntityPropertyViewModel;
use Lturi\SymfonyExtensions\Classes\ViewModels\EntityViewModel;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class JsonapiNormalizer extends AbstractNormalizer implements DenormalizerAwareInterface, NormalizerAwareInterface
{
    const ROUTE_DESCRIPTION = "jsonapi.route.description";
    const ENTITY_MANAGER = "jsonapi.entity.manager";

    protected $entitiesDescription;

    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    /**
     * EntityNormalizer constructor.
     *
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
        if (is_array($data)) {
            return array_reduce($data, function($carry, $item) {
                return $carry && $this->supportsNormalization($item);
            }, true);
        }
        return false;
    }

    /**
     * Only support array denormalization, and only inside object that is an entity
     * @param mixed       $data
     * @param string      $type
     * @param string|null $format
     *
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_array($data) && isset($data["data"]) && array_reduce($this->entitiesDescription, function($context, EntityViewModel $item) use ($type) {
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
     * TODO: links
     */
    public function normalize ($object, string $format = null, array $context = [], $depth = 0)
    {
        if (is_null($object) || !$this->supportsNormalization($object)) return $object;
        $routes = isset($context[self::ROUTE_DESCRIPTION]) ? $context[self::ROUTE_DESCRIPTION] : [];

        $entity = $this->getEntityDescription($object);
        $result = [
            "id" => (string)$object->getId(),
            "type" => $entity->getName(),
            "links" => []
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
                        "links" => [],
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
                $result["relationships"][$property->getName()] = $content;
            } else {
                $result["attributes"][$property->getName()] = $content;
            }
        }
        return $result;
    }

    /**
     * Denormalize array to object. Only supports array denormalization.
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
        $entityManager = (isset($context[self::ENTITY_MANAGER]) && $context[self::ENTITY_MANAGER]) ?
            $context[self::ENTITY_MANAGER] :
            null;

        $object = (isset($context[AbstractNormalizer::OBJECT_TO_POPULATE]) && $context[AbstractNormalizer::OBJECT_TO_POPULATE]) ?
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] :
            new $type();
        $entityDescription = $this->getEntityDescriptionByClass($type);

        $data = $data["data"];
        if ($entityManager && isset($data["id"]) && !isset($context[AbstractNormalizer::OBJECT_TO_POPULATE])) {
            $object = $entityManager->find($type, $data["id"]);
        }
        $attributes = isset($data["attributes"]) ? $data["attributes"] : [];
        $relations = isset($data["relationships"]) ? $data["relationships"] : [];

        /** @var EntityPropertyViewModel $property */
        foreach ($entityDescription->getProperties() as $property) {
            if ($property->getName() == "id") continue;
            $methodName = "set".$property->getName();
            if ($property->isEntity()) {
                if (isset($relations[$property->getName()]) && method_exists($object, $methodName)) {
                    call_user_func(
                        array($object, $methodName),
                        $this->denormalizer->denormalize(
                            ["data" => $relations[$property->getName()]],
                            $property->getPropertyType()->getClassName(),
                            null,
                            [
                                self::ENTITY_MANAGER => $entityManager
                            ]
                        )
                    );
                }
            } else {
                if (isset($attributes[$property->getName()]) && method_exists($object, $methodName)) {
                    if ($property->getPropertyType()->getClassName()) {
                        $class = new ReflectionClass($property->getPropertyType()->getClassName());
                        if ($class->getConstructor() && $class->getConstructor()->getNumberOfRequiredParameters() == 0)
                            $value = $this->denormalizer->denormalize(
                                $attributes[$property->getName()],
                                $property->getPropertyType()->getClassName(),
                                null,
                                [
                                    self::ENTITY_MANAGER => $entityManager
                                ]
                            );
                        // TODO: here are missing features...
                        else continue;
                    } else {
                        $value = $attributes[$property->getName()];
                    }
                    call_user_func(array($object, $methodName), $value);
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
}
