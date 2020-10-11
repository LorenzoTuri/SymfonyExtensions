<?php

namespace Lturi\SymfonyExtensions\Classes\Entities;

class Entity {
    /** @var string */
    protected $class;
    /** @var string */
    protected $name;
    /** @var array */
    protected $paths;

    /**
     * @return string
     */
    public function getClass () : string
    {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Entity
     */
    public function setClass (string $class) : Entity
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return string
     */
    public function getName () : string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Entity
     */
    public function setName (string $name) : Entity
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getPaths () : array
    {
        return $this->paths;
    }

    /**
     * @param array $paths
     *
     * @return Entity
     */
    public function setPaths (array $paths) : Entity
    {
        $this->paths = $paths;
        return $this;
    }

    /**
     * @param $path
     *
     * @return Entity
     */
    public function addPath ($path) : Entity
    {
        $this->paths[] = $path;
        return $this;
    }
}