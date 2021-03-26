<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Lturi\SymfonyExtensions\CommandApi\Event\UpdateCommandPostUpdate;
use Lturi\SymfonyExtensions\CommandApi\Event\UpdateCommandPreUpdate;
use Lturi\SymfonyExtensions\Framework\Exception\EntityIdNotFoundException;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

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
     * @throws ExceptionInterface|EntityIdNotFoundException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): mixed
    {
        $id = $requestContent->get("id");
        $entityClass = $entity->getClass();
        $entityData = $this->entityManager->find($entityClass, $id);
        if ($entityData) {
            $entityDataEvent = $this->eventDispatcher->dispatch(new UpdateCommandPreUpdate(
                $entity->getName(),
                $entityData,
                $requestContent
            ));

            $entityData = $this->serializer->denormalize(
                $entityDataEvent->getRequestContent()->all(),
                $entityClass,
                'json',
                [
                    AbstractNormalizer::OBJECT_TO_POPULATE => $entityDataEvent->getEntityData(),
                ]
            );
            $this->entityManager->save($entityData);

            $entityDataEvent = $this->eventDispatcher->dispatch(new UpdateCommandPostUpdate(
                $entity->getName(),
                $entityData
            ));

            return $entityDataEvent->getEntityData();
        } else {
            throw new EntityIdNotFoundException($id);
        }
    }
}
