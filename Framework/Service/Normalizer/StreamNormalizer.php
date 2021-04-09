<?php

namespace Lturi\SymfonyExtensions\Framework\Service\Normalizer;

use Symfony\Component\Serializer\Encoder\NormalizationAwareInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;

class StreamNormalizer extends AbstractNormalizer implements DenormalizerAwareInterface, NormalizationAwareInterface
{
    use DenormalizerAwareTrait;
    use NormalizerAwareTrait;

    /**
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
        return false;
    }

    public function normalize ($object, string $format = null, array $context = [])
    {
        return (string)$object;
    }

    public function denormalize ($data, string $type, string $format = null, array $context = [])
    {
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
