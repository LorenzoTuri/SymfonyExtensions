<?php

namespace Lturi\SymfonyExtensions\Framework\Service;

use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Service\Normalizer\EntityNormalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class SerializerService extends Serializer
{
    /** @var ContainerInterface  */
    protected $container;

    public function __construct(EntityManagerInterface $entityManager, ContainerInterface $container)
    {
        $this->container = $container;
        $extractor = new PropertyInfoExtractor([], [new PhpDocExtractor(), new ReflectionExtractor()]);

        $circularReferenceHandler = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                return method_exists($object, "getId") ? ["id" => $object->getId()] : null;
            },
        ];

        $encoders = [new JsonEncoder()];
        $normalizers = [
            new DateTimeNormalizer(),
            new EntityNormalizer(
                $entityManager,
                null,
                null,
                null,
                null,
                null,
                null,
                $circularReferenceHandler
            ),
            new GetSetMethodNormalizer(
                null,
                null,
                $extractor,
                null,
                null,
                $circularReferenceHandler
            ),
            new ObjectNormalizer(
                null,
                null,
                null,
                $extractor,
                null,
                null,
                $circularReferenceHandler
            ),
            new ArrayDenormalizer(),
        ];
        parent::__construct($normalizers, $encoders);
    }
}
