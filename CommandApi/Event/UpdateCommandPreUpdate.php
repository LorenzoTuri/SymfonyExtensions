<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateCommandPreUpdate extends Event {
    protected $entityName;
    protected $entityData;
    protected $requestContent;

    public function __construct(
        string $entityName,
        $entityData,
        ParameterBagInterface $requestContent,
    )
    {
        $this->entityName = $entityName;
        $this->entityData = $entityData;
        $this->requestContent = $requestContent;
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

    /**
     * @return ParameterBagInterface
     */
    public function getRequestContent(): ParameterBagInterface
    {
        return $this->requestContent;
    }

    /**
     * @param ParameterBagInterface $requestContent
     * @return CreateCommandPostSave
     */
    public function setRequestContent(ParameterBagInterface $requestContent): CreateCommandPostSave
    {
        $this->requestContent = $requestContent;
        return $this;
    }
}