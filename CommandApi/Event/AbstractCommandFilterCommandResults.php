<?php

namespace Lturi\SymfonyExtensions\CommandApi\Event;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AbstractCommandFilterCommandResults extends Event {
    protected $entityName;
    protected $results;

    public function __construct(
        $entityName,
        $results
    )
    {
        $this->entityName = $entityName;
        $this->results = $results;
    }

    /**
     * @return mixed
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @param mixed $entityName
     * @return AbstractCommandFilterCommandResults
     */
    public function setEntityName($entityName)
    {
        $this->entityName = $entityName;
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