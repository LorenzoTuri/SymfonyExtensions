# Symfony flex (5) Utility package for fast developing

> composer require lturi/symfony-extensions

Installing it through composer is enough (if the project is developed following common symfony rules)
to make it work.

In order to enable the 2 routes (routes collector and translations) e little modifications to project config files
may be necessary. Open the config/routes.yaml file and add:

```
lturi_symfony_extensions:\
  resource: lturi_symfony_extensions\
  type: extra
```

Features:
- Utility classes:
    - ApiController (better solution to manage generic ApiController then AbstractController)
    - RestApiController (contains the base function to manage a rest api endpoint)
- Controllers:
    - RoutesController (exposes all routes of the project)
    - TranslationController (exposes translation of the project, only for main translations)
- Exceptions
    - ValidationsErrorsException (exception for multiple errors in the message)
- Services
    - EntityNormalizer (normalizer able to detect if the object refers to an entity, and eventually load it)
    - ApiResponse (better solution to answer to an endpoint with a json response)
    - HttpExceptionService (service used to manage in better ways the exceptions)
    - SerializerService (serializer service configured with common normalizers etc and EntityNormalizer)
- Validators
    - SafeString (validate a string for database XSS injection)
    
Refer to full doc to get details about the single component



TODO:
cacheable response fix
- Since symfony is not completely capable of handling cached response, here is \
a little fix for that. Insert in the index.php file something like

```
if ('prod' === $kernel->getEnvironment()) {
    $kernel = new CachedKernel($kernel);
}
```

and automatically each response returned of the types CachableResponse or
CachableApiResponse will be cached.