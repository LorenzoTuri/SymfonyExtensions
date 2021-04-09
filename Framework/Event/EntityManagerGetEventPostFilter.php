<?php

namespace Lturi\SymfonyExtensions\Framework\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EntityManagerGetEventPostFilter extends Event {
    protected $parameterBag;
    protected $type;
    protected $entityData;

    public function __construct(
        ParameterBagInterface $parameterBag,
        string $type,
        $entityData
    ) {
        $this->parameterBag = $parameterBag;
        $this->type = $type;
        $this->entityData = $entityData;
    }

    /**
     * @return ParameterBagInterface
     */
    public function getParameterBag(): ParameterBagInterface
    {
        return $this->parameterBag;
    }

    /**
     * @param ParameterBagInterface $parameterBag
     * @return EntityManagerGetEventPreFilter
     */
    public function setParameterBag(ParameterBagInterface $parameterBag): EntityManagerGetEventPreFilter
    {
        $this->parameterBag = $parameterBag;
        return $this;
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
     * @return EntityManagerGetEventPreFilter
     */
    public function setType(string $type): EntityManagerGetEventPreFilter
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
     * @return EntityManagerGetEventPreFilter
     */
    public function setEntityData($entityData)
    {
        $this->entityData = $entityData;
        return $this;
    }
}