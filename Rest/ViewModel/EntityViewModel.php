<?php

namespace Lturi\SymfonyExtensions\Rest\ViewModel;

use Doctrine\Common\Collections\ArrayCollection;

class EntityViewModel {
    /** @var string */
    protected $name;
    /** @var string */
    protected $class;
    /** @var ArrayCollection */
    protected $properties;

    public function __construct ()
    {
        $this->properties = new ArrayCollection();
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
     * @return ArrayCollection
     */
    public function getProperties () : ArrayCollection
    {
        return $this->properties;
    }

    /**
     * @param ArrayCollection $properties
     *
     * @return EntityViewModel
     */
    public function setProperties (ArrayCollection $properties) : EntityViewModel
    {
        $this->properties = $properties;
        return $this;
    }
}