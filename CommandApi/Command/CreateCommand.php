<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\CreateCommandPostSave;
use Lturi\SymfonyExtensions\CommandApi\Event\CreateCommandPreSave;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class CreateCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:create';

    /**
     * @param EntityViewModel $entity
     * @param ParameterBagInterface $requestContent
     * @return mixed
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): mixed
    {
        $entityDataEvent = $this->eventDispatcher->dispatch(new CreateCommandPreSave(
            $entity->getClass(),
            $requestContent
        ));

        $entityData = $this->entityManager->save(
            $entityDataEvent->getRequestContent(),
            $entity->getClass(),
            $entity->getName(),
            null,
            $requestContent->get("data"),
            true
        );

        $entityDataEvent = $this->eventDispatcher->dispatch(new CreateCommandPostSave(
            $entity->getClass(),
            $entityData
        ));

        return $entityDataEvent->getEntityData();
    }
}
