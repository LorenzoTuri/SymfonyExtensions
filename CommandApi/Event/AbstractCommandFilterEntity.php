<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractCommandFilterEntity extends Event {
    protected $entityName;
    protected $entity;

    public function __construct(
        $entityName,
        $entity
    )
    {
        $this->entityName = $entityName;
        $this->entity = $entity;
    }

    /**
     * @return mixed
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param mixed $entityName
     * @return AbstractCommandFilterEntity
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param mixed $entity
     * @return AbstractCommandFilterEntity
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }
}