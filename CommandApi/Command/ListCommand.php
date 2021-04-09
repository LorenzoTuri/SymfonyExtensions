<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\LazyCriteriaCollection;
use Lturi\SymfonyExtensions\CommandApi\Event\ListCommandPostList;
use Lturi\SymfonyExtensions\CommandApi\Event\ListCommandPreList;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ListCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:list';

    /**
     * @param EntityViewModel $entity
     * @param ParameterBagInterface $requestContent
     * @return Collection|LazyCriteriaCollection
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): Collection|LazyCriteriaCollection
    {
        $entityDataEvent = $this->eventDispatcher->dispatch(new ListCommandPreList(
            $entity->getClass(),
            $requestContent
        ));
        $requestContent = $entityDataEvent->getRequestContent();

        $entityList = $this->entityManager->list(
            $requestContent,
            $entity->getClass(),
            $entity->getName(),
            $requestContent->all(),
            true
        );

        $entityDataEvent = $this->eventDispatcher->dispatch(new ListCommandPostList(
            $entity->getClass(),
            $entityList
        ));
        return $entityDataEvent->getEntityList();
    }
}
