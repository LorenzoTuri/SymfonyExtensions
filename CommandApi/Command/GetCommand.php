<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\GetCommandPostGet;
use Lturi\SymfonyExtensions\CommandApi\Event\GetCommandPreGet;
use Lturi\SymfonyExtensions\Framework\Exception\EntityIdNotFoundException;
use Lturi\SymfonyExtensions\Framework\Exception\UnauthorizedUserException;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GetCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:get';

    protected function configure()
    {
        parent::configure();
        $this
            ->addOption(
                "id",
                null,
                InputOption::VALUE_REQUIRED,
                "Id of the entity"
            );
    }

    /**
     * @param EntityViewModel $entity
     * @param ParameterBagInterface $requestContent
     * @return object
     * @throws EntityIdNotFoundException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): object
    {
        $id = $requestContent->get("id");
        $entityDataEvent = $this->eventDispatcher->dispatch(new GetCommandPreGet(
            $entity->getClass(),
            $id
        ));

        $entityData = $this->entityManager->find(
            $entityDataEvent->getRequestContent(),
            $entity->getClass(),
            $entity->getName(),
            $entityDataEvent->getId(),
            true
        );

        $entityDataEvent = $this->eventDispatcher->dispatch(new GetCommandPostGet(
            $entity->getClass(),
            $entityData
        ));
        if (!$entityDataEvent->getEntityData()) {
            throw new EntityIdNotFoundException($id);
        }

        return $entityDataEvent->getEntityData();
    }
}
