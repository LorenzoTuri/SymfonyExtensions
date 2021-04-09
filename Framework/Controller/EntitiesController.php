<?php

namespace Lturi\SymfonyExtensions\Framework\Controller;

use Lturi\SymfonyExtensions\Framework\Constants;
use Lturi\SymfonyExtensions\Framework\EntityUtility\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Framework\Service\Response\ApiResponse;
use Lturi\SymfonyExtensions\Framework\Service\Response\CacheableApiResponse;
use Psr\Container\ContainerInterface;

class EntitiesController
{
    protected $container;
    protected $entitiesDescriptor;

    public function __construct (ContainerInterface $container, AbstractEntitiesDescriptor $entitiesDescriptor)
    {
        $this->container = $container;
        $this->entitiesDescriptor = $entitiesDescriptor;
    }

    /**
     * @param CacheableApiResponse $apiResponse
     *
     * @return ApiResponse
     */
    public function getAllRequest(CacheableApiResponse $apiResponse): ApiResponse
    {
        // TODO: find a way to get parameter without container
        $entities = $this->container->getParameter("jsonApiEntities");
        $results = $this->entitiesDescriptor->describe("cachedJsonApiEntities", $entities);
        return $apiResponse->setResponse($results);
    }
}
