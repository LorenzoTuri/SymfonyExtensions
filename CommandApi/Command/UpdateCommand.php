<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\UpdateCommandPostUpdate;
use Lturi\SymfonyExtensions\CommandApi\Event\UpdateCommandPreUpdate;
use Lturi\SymfonyExtensions\Framework\Exception\EntityIdNotFoundException;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UpdateCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:update';

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
     * @return mixed
     * @throws EntityIdNotFoundException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): mixed
    {
        $id = $requestContent->get("id");
        $entityClass = $entity->getClass();
        $entityData = $this->entityManager->find(
            $requestContent,
            $entityClass,
            $entity->getName(),
            $id,
            true
        );

        if ($entityData) {
            $entityDataEvent = $this->eventDispatcher->dispatch(new UpdateCommandPreUpdate(
                $entityClass,
                $entityData,
                $requestContent
            ));
            $requestContent = $entityDataEvent->getRequestContent();

            $this->entityManager->save(
                $requestContent,
                $entityClass,
                $entity->getName(),
                $entityDataEvent->getEntityData()->getId(),
                $requestContent->get("data"),
                true
            );

            $entityDataEvent = $this->eventDispatcher->dispatch(new UpdateCommandPostUpdate(
                $entity->getClass(),
                $entityData
            ));

            return $entityDataEvent->getEntityData();
        } else {
            throw new EntityIdNotFoundException($id);
        }
    }
}
