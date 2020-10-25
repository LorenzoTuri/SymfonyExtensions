<?php

namespace Lturi\SymfonyExtensions\Classes\ViewModels;

use Ramsey\Collection\Set;

class EntityViewModel {
    /** @var string */
    protected $name;
    /** @var string */
    protected $class;
    /** @var array[EntityPropertyViewModel] */
    protected $properties;

    public function __construct ()
    {
        $this->properties = new Set(EntityPropertyViewModel::class);
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
     * @return EntityViewModel
     */
    public function setName (string $name) : EntityViewModel
    {
        $this->name = $name;
        return $this;
    }

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
     * @return EntityViewModel
     */
    public function setClass (string $class) : EntityViewModel
    {
        $this->class = $class;
        return $this;
    }

    /**
     * @return Set
     */
    public function getProperties () : Set
    {
        return $this->properties;
    }

    /**
     * @param Set $properties
     *
     * @return EntityViewModel
     */
    public function setProperties (Set $properties) : EntityViewModel
    {
        $this->properties = $properties;
        return $this;
    }
}