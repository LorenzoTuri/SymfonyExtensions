<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractCommandFilterCommandResults extends Event {
    protected $type;
    protected $results;

    public function __construct(
        $type,
        $results
    )
    {
        $this->type = $type;
        $this->results = $results;
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
     * @return AbstractCommandFilterCommandResults
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param mixed $results
     * @return AbstractCommandFilterCommandResults
     */
    public function setResults($results)
    {
        $this->results = $results;
        return $this;
    }
}