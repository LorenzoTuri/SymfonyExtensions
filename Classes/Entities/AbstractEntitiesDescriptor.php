<?php

namespace Lturi\SymfonyExtensions\Classes\Entities;

abstract class AbstractEntitiesDescriptor {
    public abstract function describe(array $items) : array;

    protected function getDefaultObject() : string
    {
        return "Object";
    }
    protected function getDefaultNames() : array
    {
        return [
            "int" => "Number",
            "float" => "Number",
            "string" => "String",
            "array" => "Array",
            "bool" => "Boolean",
            "boolean" => "Boolean",
            "DateTimeInterface" => "Date"
        ];
    }
}