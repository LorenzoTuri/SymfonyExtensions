<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ListCommandPreList extends Event {
    protected $type;
    protected $requestContent;

    public function __construct(
        string $type,
        ParameterBagInterface $requestContent
    )
    {
        $this->type = $type;
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
     * @return ListCommandPreList
     */
    public function setType(string $type): ListCommandPreList
    {
        $this->type = $type;
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