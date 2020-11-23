<?php

namespace Lturi\SymfonyExtensions\JsonApi\Entity;

interface EntityManagerInterface {
    function find($type, $id);
}