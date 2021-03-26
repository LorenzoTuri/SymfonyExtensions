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
     * @return array
     * @throws EntityIdNotFoundException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): array {
        $id = $requestContent->get("id");
        $entityData = $this->entityManager->find($entity->getClass(), $id);
        if ($entityData) {

            $entityDataEvent = $this->eventDispatcher->dispatch(new DeleteCommandPreDelete(
                $entity->getName(),
                $entityData
            ));

            $this->entityManager->delete($entity->getClass(), $id);

            $this->eventDispatcher->dispatch(new DeleteCommandPostDelete(
                $entity->getName(),
                $entityDataEvent->getEntityData()
            ));

            return [
                "success" => true,
                "message" => "Entity {$id} deleted"
            ];
        } else {
            throw new EntityIdNotFoundException($id);
        }
    }
}
