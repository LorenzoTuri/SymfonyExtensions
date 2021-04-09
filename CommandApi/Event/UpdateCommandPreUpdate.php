<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class UpdateCommandPreUpdate extends Event {
    protected $type;
    protected $entityData;
    protected $requestContent;

    public function __construct(
        string $type,
        $entityData,
        ParameterBagInterface $requestContent,
    )
    {
        $this->type = $type;
        $this->entityData = $entityData;
        $this->requestContent = $requestContent;
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
     * @return UpdateCommandPreUpdate
     */
    public function setType(string $type): UpdateCommandPreUpdate
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