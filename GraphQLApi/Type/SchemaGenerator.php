<?php

namespace Lturi\SymfonyExtensions\GraphQLApi\Type;

use DateTimeInterface;
use GraphQL\Type\Definition\FieldArgument;
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
        $queryFields = [];
        $mutationFields = [];
        foreach ($this->entitiesDefinition as $entityDefinition) {
            $entityClass = $entityDefinition["class"];
            $entityName = $entityDefinition["name"];

            $entityType = $this->detectComplexType($entityClass);
            $entityFields = $entityType->config["fields"] ?? [];

            $queryFields[$entityName] = [
                'type' => $entityType,
                'args' => array_merge(
                    $entityFields,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableGet, $entityName, $entityClass) {
                    return $callableGet($entityClass, $entityName, $args, $context);
                }
            ];

            // TODO: pagination missing
            $queryFields[$entityName."List"] = [
                'type' => Type::listOf($entityType),
                'args' => array_merge(
                    $entityFields,
                    ['id' => Type::string()]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableList, $entityName, $entityClass) {
                    return $callableList($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Update'] = [
                'name' => $entityName.'Update',
                'type' => $entityType,
                'args' => array_merge(
                    $entityFields,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableSave, $entityName, $entityClass) {
                    return $callableSave($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Create'] = [
                'name' => $entityName.'Create',
                'type' => $entityType,
                'args' => $entityFields,
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableSave, $entityName, $entityClass) {
                    return $callableSave($entityClass, $entityName, $args, $context);
                }
            ];

            $mutationFields[$entityName.'Delete'] = [
                'name' => $entityName.'Delete',
                'type' => $entityType,
                'args' => array_merge(
                    $entityFields,
                    ['id' => Type::nonNull(Type::string())]
                ),
                'resolve' => function ($objectValue, $args, $context, ResolveInfo $info) use ($callableDelete, $entityName, $entityClass) {
                    return $callableDelete($entityClass, $entityName, $args, $context);
                }
            ];
        }

        // Now let's complete the object definition before building the true schema by iterating the created types
        // and injecting args
        /** @var ObjectType $createdType */
        foreach ($this->createdTypes as $createdType) {
            if ($createdType instanceof ObjectType) {
                foreach ($createdType->getFields() as $field) {
                    if ($field->getType() instanceof ObjectType) {
                        $field->args = array_merge(
                            $field->args,
                            FieldArgument::createMap($field->getType()->config["fields"])
                        );
                    }
                }
            }
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
        if (method_exists($entity, "get".ucfirst($info->fieldName))) {
            return $entity->{"get".$info->fieldName}();
        } else if (property_exists($entity, $info->fieldName)) {
            return $entity->{$info->fieldName};
        } else {
            return null;
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
        if ($typeName == "int") {
            return Type::int();
        } elseif ($typeName == "bool") {
            return Type::boolean();
        } elseif ($typeName == "float") {
            return Type::float();
        } else {
            return Type::string();
        }
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
}