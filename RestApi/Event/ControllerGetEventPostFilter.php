<?php

namespace Lturi\SymfonyExtensions\RestApi\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ControllerGetEventPostFilter extends Event {
    protected $parameterBag;
    protected $type;
    protected $id;
    protected $result;
    protected $context;

    public function __construct(
        $parameterBag,
        $type,
        $id,
        $result,
        $context
    )
    {
        $this->parameterBag = $parameterBag;
        $this->type = $type;
        $this->id = $id;
        $this->result = $result;
        $this->context = $context;
    }

    /**
     * @return mixed
     */
    public function getParameterBag()
    {
        return $this->parameterBag;
    }

    /**
     * @param mixed $parameterBag
     */
    public function setParameterBag($parameterBag): void
    {
        $this->parameterBag = $parameterBag;
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
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result
     */
    public function setResult($result): void
    {
        $this->result = $result;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     */
    public function setContext($context): void
    {
        $this->context = $context;
    }
}