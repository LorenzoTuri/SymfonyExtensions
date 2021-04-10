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

completion of parts:
- commandApi: completed
- graphQLApi: 66%
- jsonApi: 66% to be rafactored, is now broken
- restApi: 0
- rest: to be refactored into restApi/framework and then removed
- package.json/composer.json: TO CHECK FOR DEPENDENCIES


User management:\
<a>https://symfony.com/doc/current/security.html</a>

> composer require symfony/security-bundle

```yaml
# config/packages/security.yaml
security:
    enable_authenticator_manager: true

    providers:
        # this name is casual, class and property should match to this snippet
        app_user_provider:
            entity:
                class: Lturi\SymfonyExtensions\Framework\Entity\User
                property: username
    encoders:
      Lturi\SymfonyExtensions\Framework\Entity\User:
        # insert whatever algoritm you want
        algorithm: sha512
```

## Command api example
Data for create/update goes into "data", while criterias into "filters"
```shell
php bin/console command-api:create site --content '{\"data\": {\"name\": \"test api create\", \"baseUrl\":\"test\", \"siteUrls\":[{\"url\":\"testSiteUrl\"}]}}'
```
Results:
```json
{"id":"01F1X6WA7ZMF9CZP73C1EANMWA","baseUrl":"test","dateCreate":"2021-03-28T22:02:22+02:00","dateUpdate":null,"lastCheckDate":null,"checking":false,"siteUrls":[{"id":"01F1X6WA84RTRHG7M0JRJYM19M","url":"testSiteUrl","site":"000000000debbbe70000000003d488a4","siteUrlChecks":[],"siteUrlSummaries":[]}]}
```


# contraints:
if related entity, add on the property this annotation
```
Symfony\Component\Validator\Constraints\Valid
```
