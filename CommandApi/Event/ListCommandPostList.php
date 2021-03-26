<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ListCommandPostList extends Event {
    protected $entityName;
    protected $entityList;

    public function __construct(
        string $entityName,
        $entityList
    )
    {
        $this->entityName = $entityName;
        $this->entityList = $entityList;
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
     * @return CreateCommandPreSave
     */
    public function setEntityName(string $entityName): CreateCommandPreSave
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getEntityList()
    {
        return $this->entityList;
    }

    /**
     * @param mixed $entityList
     * @return GetCommandPostGet
     */
    public function setEntityList($entityList)
    {
        $this->entityList = $entityList;
        return $this;
    }
}