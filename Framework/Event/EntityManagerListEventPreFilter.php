<?php

namespace Lturi\SymfonyExtensions\Framework\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EntityManagerListEventPreFilter extends Event {
    protected $parameterBag;
    protected $type;
    protected $requestContent;
    protected $isAuthorized;

    public function __construct(
        ParameterBagInterface $parameterBag,
        string $type,
        $requestContent,
        bool $isAuthorized
    ) {
        $this->parameterBag = $parameterBag;
        $this->type = $type;
        $this->requestContent = $requestContent;
        $this->isAuthorized = $isAuthorized;
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
    public function getRequestContent()
    {
        return $this->requestContent;
    }

    /**
     * @param mixed $requestContent
     * @return EntityManagerGetEventPreFilter
     */
    public function setRequestContent($requestContent)
    {
        $this->requestContent = $requestContent;
        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->isAuthorized;
    }

    /**
     * @param bool $isAuthorized
     * @return EntityManagerGetEventPreFilter
     */
    public function setIsAuthorized(bool $isAuthorized): EntityManagerGetEventPreFilter
    {
        $this->isAuthorized = $isAuthorized;
        return $this;
    }
}