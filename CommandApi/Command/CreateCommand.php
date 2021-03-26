<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\CreateCommandPostSave;
use Lturi\SymfonyExtensions\CommandApi\Event\CreateCommandPreSave;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class CreateCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:create';

    /**
     * @param EntityViewModel $entity
     * @param ParameterBagInterface $requestContent
     * @return mixed
     * @throws ExceptionInterface
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): mixed
    {
        $entityDataEvent = $this->eventDispatcher->dispatch(new CreateCommandPreSave(
            $entity->getName(),
            $requestContent
        ));

        $entityClass = $entity->getClass();
        $entityData = $this->serializer->denormalize(
            $entityDataEvent->getRequestContent()->all(),
            $entityClass,
            'json',
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => new $entityClass()
            ]
        );
        $this->entityManager->save($entityData);

        $entityDataEvent = $this->eventDispatcher->dispatch(new CreateCommandPostSave(
            $entity->getName(),
            $entityData
        ));

        return $entityDataEvent->getEntityData();
    }
}
