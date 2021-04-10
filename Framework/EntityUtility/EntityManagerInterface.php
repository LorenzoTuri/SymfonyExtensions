<?php

namespace Lturi\SymfonyExtensions\Framework\EntityUtility;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Traversable;

interface EntityManagerInterface {
    /**
     * Find entity.
     * @param ParameterBagInterface $parameterBag Used in events
     * @param string $type Class of the entity
     * @param string $entityName Short name of the entity. Used in voters
     * @param mixed $id Id of the entity
     * @param bool $removeAuthorizationCheck Should ask for user auth, or skip it?
     * @return mixed                                Entity or null
     */
    function find(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        bool $removeAuthorizationCheck = false
    ): mixed;

    /**
     * Delete entity
     * @param ParameterBagInterface $parameterBag Used in events
     * @param string $type Class of the entity
     * @param string $entityName Short name of the entity. Used in voters
     * @param mixed $id Id of the entity
     * @param bool $removeAuthorizationCheck Should ask for user auth or skip it?
     * @return bool                                 Always true. Throws exceptions instead of false
     */
    function delete(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        bool $removeAuthorizationCheck = false
    ): bool;

    /**
     * @param ParameterBagInterface $parameterBag Used in events. Also used to inflate CRITERIA
     * @param string $type Class of the entity
     * @param string $entityName Short name of the entity. Used in voters
     * @param array $requestContent Array containing the request content, filters etc...
     * @param bool $removeAuthorizationCheck Should ask for user or skip it?
     * @return ?Traversable mixed                   List of entities
     */
    function list(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        array $requestContent,
        bool $removeAuthorizationCheck = false
    ): ?Traversable;

    /**
     * @param ParameterBagInterface $parameterBag Used in events. Also used to inflate CRITERIA
     * @param string $type Class of the entity
     * @param string $entityName Short name of the entity. Used in voters
     * @param mixed $id Id of the entity
     * @param array $requestContent Array containing the request content, ex. new entity data
     * @param bool $removeAuthorizationCheck Should ask for user auth or skip it?
     * @return mixed                                Saved entity
     */
    function save(
        ParameterBagInterface $parameterBag,
        string $type,
        string $entityName,
        mixed $id,
        array $requestContent,
        bool $removeAuthorizationCheck = false
    ): mixed;
}