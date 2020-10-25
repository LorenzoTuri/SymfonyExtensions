<?php

namespace Lturi\SymfonyExtensions\Services\Normalizers;

use Lturi\SymfonyExtensions\Classes\ViewModels\EntityViewModel;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class StreamNormalizer extends AbstractNormalizer implements DenormalizerAwareInterface, NormalizationAwareInterface
{
    protected $entitiesDescription;

    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    /**
     * EntityNormalizer constructor.
     *
     * @param ClassMetadataFactoryInterface|null $classMetadataFactory
     * @param NameConverterInterface|null        $nameConverter
     * @param array|null                         $defaultContext
     */
    public function __construct(
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        array $defaultContext = []
    ) {
        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $defaultContext
        );
    }

    public function supportsNormalization ($data, string $format = null)
    {
        return is_resource($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_resource($data);
    }

    public function normalize ($object, string $format = null, array $context = [])
    {
        return (string)$object;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array<mixed> $context
     *
     * @return array<mixed>|object
     * @throws ExceptionInterface
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        die("todo denormalizer resource");
        $entity = $this->entityManager->find($type, $data["id"]);
        unset($data["id"]);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity;
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
