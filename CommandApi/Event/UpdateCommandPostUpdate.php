<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateCommandPostUpdate extends Event {
    protected $entityName;
    protected $entityData;

    public function __construct(
        string $entityName,
        $entityData,
    ) {
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
     * @return CreateCommandPostSave
     */
    public function setEntityName(string $entityName): CreateCommandPostSave
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