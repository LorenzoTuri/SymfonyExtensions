<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GetCommandPostGet extends Event {
    protected $type;
    protected $entityData;

    public function __construct(
        string $type,
        $entityData
    )
    {
        $this->type = $type;
        $this->entityData = $entityData;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     * @return GetCommandPostGet
     */
    public function setType($type)
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