<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractCommandFilterAttributes extends Event {
    protected $entityName;
    protected $contentType;
    protected $content;

    public function __construct(
        string $entityName,
        string $contentType,
        ParameterBagInterface $content
    )
    {
        $this->entityName = $entityName;
        $this->contentType = $contentType;
        $this->content = $content;
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
     * @return AbstractCommandFilterAttributes
     */
    public function setEntityName(string $entityName): AbstractCommandFilterAttributes
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     * @return AbstractCommandFilterAttributes
     */
    public function setContentType(string $contentType): AbstractCommandFilterAttributes
    {
        $this->contentType = $contentType;
        return $this;
    }

    /**
     * @return ParameterBagInterface
     */
    public function getContent(): ParameterBagInterface
    {
        return $this->content;
    }

    /**
     * @param ParameterBagInterface $content
     * @return AbstractCommandFilterAttributes
     */
    public function setContent(ParameterBagInterface $content): AbstractCommandFilterAttributes
    {
        $this->content = $content;
        return $this;
    }
}