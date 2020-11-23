<?php

namespace Lturi\SymfonyExtensions\Framework\Service\Normalizer;

use Doctrine\ORM\EntityManagerInterface;
use Lturi\SymfonyExtensions\Framework\Constants;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\DenormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Exception\ExceptionInterface;

class EntityNormalizer extends ObjectNormalizer implements DenormalizerAwareInterface
{
    /** @var EntityManagerInterface  */
    protected $entityManager;
    /** @var string */
    protected $entityPrefix;

    use DenormalizerAwareTrait;


    /**
     * EntityNormalizer constructor.
     *
     * @param EntityManagerInterface                   $entityManager
     * @param ContainerInterface                       $container
     * @param ClassMetadataFactoryInterface|null       $classMetadataFactory
     * @param NameConverterInterface|null              $nameConverter
     * @param PropertyAccessorInterface|null           $propertyAccessor
     * @param PropertyTypeExtractorInterface|null      $propertyTypeExtractor
     *
     * @param ClassDiscriminatorResolverInterface|null $classDiscriminatorResolver
     * @param callable|null                            $objectClassResolver
     * @param array|null                               $defaultContext
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ContainerInterface $container,
        ?ClassMetadataFactoryInterface $classMetadataFactory = null,
        ?NameConverterInterface $nameConverter = null,
        ?PropertyAccessorInterface $propertyAccessor = null,
        ?PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        callable $objectClassResolver = null,
        array $defaultContext = null
    ) {
        $this->entityManager = $entityManager;
        $this->entityPrefix = $container->getParameter(Constants::ENTITY_NAMESPACE);

        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $propertyAccessor,
            $propertyTypeExtractor,
            $classDiscriminatorResolver,
            $objectClassResolver,
            $defaultContext
        );
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return
            (
                is_array($data) &&
                strpos($type, $this->entityPrefix) === 0 &&
                isset($data['id']) &&
                $data["id"]
            );
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
        $entity = $this->entityManager->find($type, $data["id"]);
        unset($data["id"]);

        $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $entity;
        return $this->denormalizer->denormalize($data, $type, $format, $context);
    }
}
