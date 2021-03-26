<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;

abstract class AbstractEntitiesDescriptor {
    public abstract function describe(string $cachedKey, array $items) : array;

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