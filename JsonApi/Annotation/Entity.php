<?php

namespace Lturi\SymfonyExtensions\JsonApi\Annotation;

use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target("CLASS")
 */
class Entity
{
    /**
     * @var string name of the entity, default camelCase of the class (with namespace)
     */
    public $name;
    /**
     * @var string path to the entity, default snakeCase of the class (with namespace)
     */
    public $path;
    /**
     * @var string class of the generic controller of the entity,
     *      default to Lturi\SymfonyExtensions\JsonApi\Controller\JsonapiController
     */
    public $controller;
    /**
     * @var array list of strings, like v1, v2 etc corresponding to all actually supported versions.
     *      Take care that this is enough to inflate of versions the router, but true versions behaviour must
     *      be injected in other ways, overriding the controller or through other means.
     */
    public $versions;
}