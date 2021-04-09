<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ListCommandPostList extends Event {
    protected $type;
    protected $entityList;

    public function __construct(
        string $type,
        $entityList
    )
    {
        $this->type = $type;
        $this->entityList = $entityList;
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
     * @return ListCommandPostList
     */
    public function setType(string $type): ListCommandPostList
    {
        $this->type = $type;
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