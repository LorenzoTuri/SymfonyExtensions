<?php

namespace Lturi\SymfonyExtensions\Classes\Entities;

interface EntityManagerInterface {
    function find($type, $id);
}