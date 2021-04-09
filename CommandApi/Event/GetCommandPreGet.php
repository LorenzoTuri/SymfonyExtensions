<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GetCommandPreGet extends Event {
    protected $type;
    protected $id;

    public function __construct(
        string $type,
        $id
    )
    {
        $this->type = $type;
        $this->id = $id;
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
     * @return GetCommandPreGet
     */
    public function setType(string $type): GetCommandPreGet
    {
        $this->type = $type;
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