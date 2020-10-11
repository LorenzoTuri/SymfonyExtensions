<?php

namespace Lturi\SymfonyExtensions\Classes\Entities;

class EntityPath {
    /** @var string */
    protected $key;
    /** @var string */
    protected $controller;

    /**
     * @return string
     */
    public function getKey () : string
    {
        return $this->key;
    }

    /**
     * @param string $key
     *
     * @return EntityPath
     */
    public function setKey (string $key) : EntityPath
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @return string
     */
    public function getController () : string
    {
        return $this->controller;
    }

    /**
     * @param string $controller
     *
     * @return EntityPath
     */
    public function setController (string $controller) : EntityPath
    {
        $this->controller = $controller;
        return $this;
    }
}