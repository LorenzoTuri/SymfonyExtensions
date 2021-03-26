<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GetCommandPreGet extends Event {
    protected $entityName;
    protected $id;

    public function __construct(
        string $entityName,
        $id
    )
    {
        $this->entityName = $entityName;
        $this->id = $id;
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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     * @return GetCommandPreGet
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}