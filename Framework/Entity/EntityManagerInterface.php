<?php

namespace Lturi\SymfonyExtensions\Framework\Entity;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

interface EntityManagerInterface {
    function find(string $type, $id);
    function delete(string $type, $id);
    function list(string $type, array $requestContent, Request|ParameterBagInterface $request);
    function save($entity);
    function listRelation($entity, array $requestContent, string $method, Request|ParameterBagInterface $request);
}