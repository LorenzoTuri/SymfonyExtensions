<?php

namespace Lturi\SymfonyExtensions\CommandApi\Command;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\LazyCriteriaCollection;
use Lturi\SymfonyExtensions\CommandApi\Event\ListCommandPostList;
use Lturi\SymfonyExtensions\CommandApi\Event\ListCommandPreList;
use Lturi\SymfonyExtensions\Framework\Exception\UnrecognizableFilterException;
use Lturi\SymfonyExtensions\Rest\ViewModel\EntityViewModel;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ListCommand extends AbstractCommand
{
    protected static $defaultName = 'command-api:list';

    /**
     * @param EntityViewModel $entity
     * @param ParameterBagInterface $requestContent
     * @return Collection|LazyCriteriaCollection
     * @throws UnrecognizableFilterException
     */
    public function executeApi(
        EntityViewModel $entity,
        ParameterBagInterface $requestContent
    ): Collection|LazyCriteriaCollection
    {
        $entityDataEvent = $this->eventDispatcher->dispatch(new ListCommandPreList(
            $entity->getName(),
            $requestContent
        ));

        $entityList = $this->entityManager->list(
            $entity->getClass(),
            $entityDataEvent->getRequestContent()->all(),
            $requestContent
        );

        $entityDataEvent = $this->eventDispatcher->dispatch(new ListCommandPostList(
            $entity->getName(),
            $entityList
        ));
        $entityList = $entityDataEvent->getEntityList();

        return $entityList;
    }
}
