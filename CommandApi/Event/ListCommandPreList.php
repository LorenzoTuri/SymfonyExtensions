<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ListCommandPreList extends Event {
    protected $entityName;
    protected $requestContent;

    public function __construct(
        string $entityName,
        ParameterBagInterface $requestContent
    )
    {
        $this->entityName = $entityName;
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
     * @return CreateCommandPreSave
     */
    public function setEntityName(string $entityName): CreateCommandPreSave
    {
        $this->entityName = $entityName;
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
     * @return GetCommandPreGet
     */
    public function setRequestContent(ParameterBagInterface $requestContent): GetCommandPreGet
    {
        $this->requestContent = $requestContent;
        return $this;
    }
}