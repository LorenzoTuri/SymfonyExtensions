<?php

namespace Lturi\SymfonyExtensions\Framework;

class Constants {
    // General
    const BUNDLE_PREFIX = 'lturi.symfony_extensions.';
    const SNAKE_BUNDLE_PREFIX = 'lturi_symfony_extensions_';

    // Parameters
    // PATH USED FOR API ENDPOINT
    const API_PATH = self::BUNDLE_PREFIX.'api.path';
    // Would you like to load the routes controller
    const LOAD_ROUTES = self::BUNDLE_PREFIX."load_routes";
    // Would you like to load the translations controller
    const LOAD_TRANSLATIONS = self::BUNDLE_PREFIX."load_translations";

    // Routes name and path
    const ROUTES_NAME = self::SNAKE_BUNDLE_PREFIX.'api_routes';
    const ROUTES_PATH = '/api/routes';

    // Translations name and path
    const TRANSLATIONS_NAME = self::SNAKE_BUNDLE_PREFIX.'api_translations';
    const TRANSLATIONS_PATH = '/api/translations';
    const TRANSLATIONS_FULL_NAME = self::SNAKE_BUNDLE_PREFIX.'api_translations_full';
    const TRANSLATIONS_FULL_PATH = '/api/translations/all';
}