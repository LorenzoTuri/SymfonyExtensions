# Controllers:

## TranslationController
The TranslationController class provides the framework with an endpoint 
capable of returning the various translations of the app. 
The controller can be deactivated by configuring this package accordingly.

It also provides an endpoint to get all the translations -> this is time intensive since requires 
to load all the translations of all the bundles. The translation gets cached, both server side (TODO) and
client side (if CacheableResponse is set).

There is a difference between the 2 endpoints:
- simple endpoint loads only "messages" catalogue
- complex endpoint loads the full catalogue, returning results indexed by key
###### Simple endpoint
> /api/translations

Parameters: 
- locale: locale to be load, null for all

###### Full endpoint
> /api/translations/all

Parameters: 
- locale: locale to be load, null for all