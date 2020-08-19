<?php

namespace Lturi\SymfonyExtensions\Classes;

class Constants {
    // General
    const BUNDLE_PREFIX = 'lturi.symfony_extensions.';
    const SNAKE_BUNDLE_PREFIX = 'lturi_symfony_extensions_';

   // Parameters
    const API_PATH = self::BUNDLE_PREFIX.'api.path';
    const ENTITY_NAMESPACE = self::BUNDLE_PREFIX.'entity.namespace';
    const LOAD_ROUTES = self::BUNDLE_PREFIX."load_routes";

    // Other
    const ROUTES_NAME = self::SNAKE_BUNDLE_PREFIX.'api_routes';
    const ROUTES_PATH = '/api/routes';
}