<?php

namespace Lturi\SymfonyExtensions\Framework\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class EntityManagerListEventPostFilter extends Event {
    protected $parameterBag;
    protected $type;
    protected $matchingEntities;

    public function __construct(
        ParameterBagInterface $parameterBag,
        string $type,
        $matchingEntities
    ) {
        $this->parameterBag = $parameterBag;
        $this->type = $type;
        $this->matchingEntities = $matchingEntities;
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
    public function getMatchingEntities()
    {
        return $this->matchingEntities;
    }

    /**
     * @param mixed $matchingEntities
     * @return EntityManagerListEventPostFilter
     */
    public function setMatchingEntities($matchingEntities)
    {
        $this->matchingEntities = $matchingEntities;
        return $this;
    }
}