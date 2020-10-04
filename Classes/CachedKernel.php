<?php

namespace Lturi\SymfonyExtensions\Classes;

use Lturi\SymfonyExtensions\Services\Response\CacheableApiResponse;
use Lturi\SymfonyExtensions\Services\Response\CacheableResponse;
use Symfony\Bundle\FrameworkBundle\HttpCache\HttpCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class CachedKernel extends HttpCache {
    public function handle (Request $request, int $type = HttpKernelInterface::MASTER_REQUEST, bool $catch = true)
    {
        $response = parent::handle($request, $type, $catch);
        if ($response instanceof CacheableResponse || $response instanceof CacheableApiResponse) {
            $response->setPublic();
            $response->setMaxAge(3600);
        }
        return $response;
    }
}