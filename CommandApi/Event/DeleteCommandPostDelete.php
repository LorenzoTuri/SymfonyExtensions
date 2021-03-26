<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class DeleteCommandPostDelete extends Event {
    protected $entityName;
    protected $entityData;

    public function __construct(
        string $entityName,
        $entityData
    )
    {
        $this->entityName = $entityName;
        $this->entityData = $entityData;
    }

    /**
     * @return string
     */
    public function getEntityName(): string
    {
        return $this->entityName;
    }

    /**
     * @param string $entityName
     * @return DeleteCommandPreDelete
     */
    public function setEntityName(string $entityName): DeleteCommandPreDelete
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityData()
    {
        return $this->entityData;
    }

    /**
     * @param mixed $entityData
     * @return CreateCommandPreSave
     */
    public function setEntityData($entityData)
    {
        $this->entityData = $entityData;
        return $this;
    }
}