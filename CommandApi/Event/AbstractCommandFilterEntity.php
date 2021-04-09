<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractCommandFilterEntity extends Event {
    protected $type;
    protected $entity;

    public function __construct(
        $type,
        $entity
    )
    {
        $this->type = $type;
        $this->entity = $entity;
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
     * @return AbstractCommandFilterEntity
     */
    public function setType($type)
    {
        $this->type = $type;
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