<?php

namespace Lturi\SymfonyExtensions\Classes\Entities;


class EntityManagerDoctrine implements EntityManagerInterface {
    protected $entityManager;

    public function __construct(\Doctrine\ORM\EntityManagerInterface $entityManager) {
        $this->entityManager = $entityManager;
    }

    function find ($type, $id)
    {
        return $this->entityManager->getRepository($type)->find($id);
    }
}