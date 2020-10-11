<?php

namespace Lturi\SymfonyExtensions\Classes\ViewModels;

use Symfony\Component\PropertyInfo\Type;

class EntityPropertyViewModel {
    /** @var string */
    protected $name;
    /** @var Type */
    protected $propertyType;
    /** @var string */
    protected $type;
    /** @var bool */
    protected $entity;

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
     * @return EntityPropertyViewModel
     */
    public function setName (string $name) : EntityPropertyViewModel
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param Type $propertyType
     *
     * @return EntityPropertyViewModel
     */
    public function setPropertyType (Type $propertyType) : EntityPropertyViewModel
    {
        $this->propertyType = $propertyType;
        return $this;
    }

    public function computePropertyType() : string
    {
        return
            $this->type ?
                $this->type :
                $this->propertyType->isCollection() ?
                (
                    $this->propertyType->getCollectionValueType() ?
                        $this->propertyType->getCollectionValueType()->getClassName() :
                        "array"
                ) :
                (
                    $this->propertyType->getClassName() ?
                        $this->propertyType->getClassName():
                        $this->propertyType->getBuiltinType()
                );
    }

    public function getType() : string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return EntityPropertyViewModel
     */
    public function setType (string $type) : EntityPropertyViewModel
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCollection () : bool
    {
        return
            $this->propertyType && $this->propertyType->isCollection();
    }

    /**
     * @return bool
     */
    public function isEntity () : bool
    {
        return $this->entity;
    }

    /**
     * @param bool $entity
     *
     * @return EntityPropertyViewModel
     */
    public function setEntity (bool $entity) : EntityPropertyViewModel
    {
        $this->entity = $entity;
        return $this;
    }
}