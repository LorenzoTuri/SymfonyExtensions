<?php

namespace Lturi\SymfonyExtensions\Classes;

class Constants {
    // General
    const BUNDLE_PREFIX = 'lturi.symfony_extensions.';
    const SNAKE_BUNDLE_PREFIX = 'lturi_symfony_extensions_';

    // Parameters
    // PATH USED FOR API ENDPOINT
    const API_PATH = self::BUNDLE_PREFIX.'api.path';
    // NAMESPACE FOR THE ENTITIES -> serializer
    const ENTITY_NAMESPACE = self::BUNDLE_PREFIX.'entity.namespace';
    // Would you like to load the routes controller
    const LOAD_ROUTES = self::BUNDLE_PREFIX."load_routes";
    // Would you like to load the translations controller
    const LOAD_TRANSLATIONS = self::BUNDLE_PREFIX."load_translations";
    // Would you like to load the entities describer controller
    const LOAD_ENTITIES = self::BUNDLE_PREFIX."load_entities";
    // Which entity should be exposed by the entities controller?
    const ENTITIES = self::BUNDLE_PREFIX."entities";

    // Routes name and path
    const ROUTES_NAME = self::SNAKE_BUNDLE_PREFIX.'api_routes';
    const ROUTES_PATH = '/api/routes';

    // Translations name and path
    const TRANSLATIONS_NAME = self::SNAKE_BUNDLE_PREFIX.'api_translations';
    const TRANSLATIONS_PATH = '/api/translations';

    // Open api entities name and path
    const ENTITIES_NAME = self::SNAKE_BUNDLE_PREFIX.'api_entities';
    const ENTITIES_PATH = '/jsonapi';

    // Cache keys
    const CACHE_ENTITIES = self::BUNDLE_PREFIX."entities";
}