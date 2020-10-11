<?php

namespace Lturi\SymfonyExtensions\Controller\Api;

use Lturi\SymfonyExtensions\Classes\Constants;
use Lturi\SymfonyExtensions\Classes\Entities\AbstractEntitiesDescriptor;
use Lturi\SymfonyExtensions\Services\Response\ApiResponse;
use Lturi\SymfonyExtensions\Services\Response\CacheableApiResponse;
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
        $entities = $this->container->getParameter(Constants::ENTITIES);
        $results = $this->entitiesDescriptor->describe($entities);
        return $apiResponse->setResponse($results);
    }
}
