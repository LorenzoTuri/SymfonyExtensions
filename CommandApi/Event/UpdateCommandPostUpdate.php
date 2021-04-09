<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateCommandPostUpdate extends Event {
    protected $type;
    protected $entityData;

    public function __construct(
        string $type,
        $entityData,
    ) {
        $this->type = $type;
        $this->entityData = $entityData;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return UpdateCommandPostUpdate
     */
    public function setType(string $type): UpdateCommandPostUpdate
    {
        $this->type = $type;
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