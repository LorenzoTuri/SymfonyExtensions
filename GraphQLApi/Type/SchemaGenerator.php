<?php

namespace Lturi\SymfonyExtensions\GraphQLApi\Type;

use DateTimeInterface;
use GraphQL\Type\Definition\FieldArgument;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

// TODO: cache???
// TODO: save schema as textual version, the return the textual version loaded. useful for caching and also to remove
//  unnecessary problems related to my ObjectTypes... let graphQL handle the object creation, not my code.
//  -> if possible with callbacks
class SchemaGenerator {
    protected $entitiesDefinition;
    protected $createdTypes = [];
    protected $propertyInfo;

    public function __construct(array $entitiesDefinition)
    {
        $this->entitiesDefinition = $entitiesDefinition;

        $phpDocExtractor = new PhpDocExtractor();
        $reflectionExtractor = new ReflectionExtractor();
        $listExtractors = [$reflectionExtractor];
        $typeExtractors = [$phpDocExtractor, $reflectionExtractor];
        $this->propertyInfo = new PropertyInfoExtractor(
            $listExtractors,
            $typeExtractors
        );
    }

    /**
     * @param callable $callableGet
     * @param callable $callableList
     * @param callable $callableDelete
     * @param callable $callableSave
     * @return Schema
     * @throws ReflectionException
     */
    public function generate(
        callable $callableGet,
        callable $callableList,
        callable $callableDelete,
        callable $callableSave
    ): Schema {
        /* Let's load before all entities... */
        foreach ($this->entitiesDefinition as $entityDefinition) {
            $this->detectComplexType($entityDefinition["class"]);
        }
        // Now let's complete the object definition before building the true schema by iterating
        // the created types and injecting args
        /** @var ObjectType $createdType */
        foreach ($this->createdTypes as $createdType) {
            if ($createdType instanceof ObjectType) {
                foreach ($createdType->getFields() as $field) {
                    if ($field->getType() instanceof ObjectType) {
                        $field->args = array_merge(
                            $field->args,
                            FieldArgument::createMap(array_map(function($field) {
                                return $this->detectInputTypeFromField($field);
                            }, $field->getType()->config["fields"]))
                        );
                    }
                }
            }
        }

        $queryFields = [];
        $mutationFields = [];
        foreach ($this->entitiesDefinition as $entityDefinition) {
            $entityClass = $entityDefinition["class"];
            $entityName = $entityDefinition["name"];

            $entityType = $this->detectComplexType($entityClass);
            $entityFields = $entityType->config["fields"] ?? [];
            $entityArguments = [];
            foreach ($entityFields as $key =>$entityField) {
                $entityArguments[$key] = $this->detectInputTypeFromField($entityField);
            }

            $queryFields[$entityName] = [
                'type' => $entityType,
                'args' => array_merge(
                    $entityArguments,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableGet, $entityName, $entityClass) {
                    return $callableGet($entityClass, $entityName, $args, $context);
                }
            ];

            $queryFields[$entityName."List"] = [
                'type' => Type::listOf($entityType),
                'args' => array_merge(
                    $entityArguments,
                    ['id' => Type::string()],
                    ['filters' => Type::string()],
                    ['limit' => Type::int()],
                    ['page' => Type::int()]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableList, $entityName, $entityClass) {
                    return $callableList($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Update'] = [
                'name' => $entityName.'Update',
                'type' => $entityType,
                'args' => array_merge(
                    $entityArguments,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableSave, $entityName, $entityClass) {
                    return $callableSave($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Create'] = [
                'name' => $entityName.'Create',
                'type' => $entityType,
                'args' => $entityArguments,
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableSave, $entityName, $entityClass) {
                    return $callableSave($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Delete'] = [
                'name' => $entityName.'Delete',
                'type' => $entityType,
                'args' => array_merge(
                    $entityArguments,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableDelete, $entityName, $entityClass) {
                    return $callableDelete($entityClass, $entityName, $args, $context);
                }
            ];
        }

        return new Schema([
            'query' => new ObjectType([
                'name' => 'Query',
                'fields' => $queryFields
            ]),
            'mutation' => new ObjectType([
                'name' => 'Mutation',
                'fields' => $mutationFields
            ]),
        ]);
    }

    public function callableResolveField($entity, $args, $context, ResolveInfo $info) {
        if (is_array($entity)) {
            if (array_key_exists($info->fieldName, $entity)) {
                return $entity[$info->fieldName];
            } else {
                return null;
            }
        } else {
            if (method_exists($entity, "get" . ucfirst($info->fieldName))) {
                return $entity->{"get" . $info->fieldName}();
            } else if (property_exists($entity, $info->fieldName)) {
                return $entity->{$info->fieldName};
            } else {
                return null;
            }
        }
    }

    /**
     * @param \Symfony\Component\PropertyInfo\Type $property
     * @return ListOfType|Type
     * @throws ReflectionException
     */
    private function detectPropertyType(\Symfony\Component\PropertyInfo\Type $property): Type|ListOfType
    {
        if ($property->isCollection()) {
            return Type::listOf($this->detectPropertyType($property->getCollectionValueType()));
        } elseif ($property->getBuiltinType() != "object") {
            return $this->detectBuiltinType($property->getBuiltinType());
        } else {
            return $this->detectComplexType($property->getClassName());
        }
    }

    /**
     * @param string $typeName
     * @return ScalarType
     */
    private function detectBuiltinType(string $typeName): ScalarType
    {
        if ($typeName == "int") { return Type::int();
        } elseif ($typeName == "bool") { return Type::boolean();
        } elseif ($typeName == "float") { return Type::float();
        } else { return Type::string(); }
    }

    /**
     * @param string $className
     * @return Type
     * @throws ReflectionException
     */
    private function detectComplexType(string $className): Type {
        $reflect = new ReflectionClass($className);
        if ($reflect->implementsInterface(DateTimeInterface::class)) {
            if (!isset($this->createdTypes["dateTime"])) {
                $this->createdTypes["dateTime"] = new DateTimeType();
            }
            return $this->createdTypes["dateTime"];
        } else if ($reflect->hasMethod('__toString')) {
            return Type::string();
        } else {
            // Detect name from entitiesDefinition, with fallback to class, and eventually return if existing
            $typeName = array_reduce($this->entitiesDefinition, function($carry, $entityDefinition) use ($className) {
                return $carry ? $carry : ($entityDefinition["class"] == $className ? $entityDefinition["name"] : null);
            }) ?? $className;
            if (isset($this->createdTypes[$typeName])) return $this->createdTypes[$typeName];

            if (is_subclass_of($typeName, Type::class)) {
                $this->createdTypes[$typeName] = new $typeName();
            } else {
                // Let's create if before handling properties to handle circular references
                $this->createdTypes[$typeName] = new ObjectType([
                    'name' => $typeName,
                    'resolveField' => [$this, "callableResolveField"]
                ]);

                // Build entity type from properties
                $entityFields = [];
                $properties = $this->propertyInfo->getProperties($className);
                foreach ($properties as $entityProperty) {
                    $entityPropertyType = $this->propertyInfo->getTypes($className, $entityProperty)[0];
                    $entityPropertyField = self::detectPropertyType($entityPropertyType);
                    $entityFields[$entityProperty] = $entityPropertyField;
                }
                $this->createdTypes[$typeName]->config["fields"] = $entityFields;
            }
            return $this->createdTypes[$typeName];
        }
    }

    /**
     * TODO: right now, only the external "layer" of the fields gets translated
     *  from ObjectType to InputObjectType... meaning that arguments are injected
     *  correctly only on scalar types (that do not require translation) but not in
     *  nested ObjectTypes
     * @param $field
     * @return Type
     * @throws ReflectionException
     */
    protected function detectInputTypeFromField(Type $field): Type {
        if ($field instanceof ScalarType) return $field;
        if ($field instanceof ListOfType) return $this->detectInputTypeFromField($field->getOfType());

        $entityDefinition = array_reduce($this->entitiesDefinition, function ($carry, $entityDefinition) use ($field) {
            return $carry ? $carry : ($entityDefinition["name"] == $field->name ? $entityDefinition : null);
        });
        if (!$entityDefinition) return $field;
        $className = $entityDefinition["class"];

        // TODO: maybe just removing this line solves the problem of
        //  the nested... or maybe just makes everything crash
        if (!isset($this->createdTypes[$field->name])) return $field;

        $typeName = $field->name."Input";
        if (isset($this->createdTypes[$typeName])) return $this->createdTypes[$typeName];

        // Let's create if before handling properties to handle circular references
        $this->createdTypes[$typeName] = new InputObjectType([
            'name' => $typeName,
        ]);

        // Build entity type from properties
        $entityFields = [];
        $properties = $this->propertyInfo->getProperties($className);
        foreach ($properties as $entityProperty) {
            $entityPropertyType = $this->propertyInfo->getTypes($className, $entityProperty)[0];
            $entityPropertyField = self::detectPropertyType($entityPropertyType);
            $entityFields[$entityProperty] = $entityPropertyField;
        }
        $this->createdTypes[$typeName]->config["fields"] = $entityFields;
        return $this->createdTypes[$typeName];
    }
}