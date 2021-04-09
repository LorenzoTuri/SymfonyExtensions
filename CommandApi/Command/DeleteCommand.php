<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\DeleteCommandPostDelete;
use Lturi\SymfonyExtensions\CommandApi\Event\DeleteCommandPreDelete;
use Lturi\SymfonyExtensions\Framework\Exception\EntityIdNotFoundException;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DeleteCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:delete';

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
     * @return bool
     * @throws EntityIdNotFoundException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): bool {
        $id = $requestContent->get("id");
        $entityData = $this->entityManager->find($requestContent, $entity->getClass(), $id, true);

        if ($entityData) {
            $entityDataEvent = $this->eventDispatcher->dispatch(new DeleteCommandPreDelete(
                $entity->getClass(),
                $entityData
            ));

            $this->entityManager->delete(
                $entityDataEvent->getEntityData(),
                $entity->getClass(),
                $entity->getName(),
                $id,
                true
            );

            $this->eventDispatcher->dispatch(new DeleteCommandPostDelete(
                $entity->getClass(),
                $entityDataEvent->getEntityData()
            ));

            return true;
        } else {
            throw new EntityIdNotFoundException($id);
        }
    }
}
