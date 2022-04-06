Vactory API Key Authentication Provider 
================

# Usage

## Authentification

A route where you need the user to be authentificated
Make sure that you select a user when creating your api key
In this example we really don't need authentification to get translation,
It's a public endpoint and should be using the API key method

The trick here is to add api_key_auth as an _auth option to the route, the rest is up to you.

```php

vactory_decoupled.translations:
  path: '/api/translations'
  defaults:
    _controller: '\Drupal\vactory_decoupled\Controller\TranslationsController::index'
  methods: [GET]
  requirements:
    _permission: 'access content'
    _role: 'authenticated'
  options:
    _auth: ['api_key_auth']

```

You can send `api_key` as query string param in a GET request or POST
or as a header using `apikey` (recommended)

## API Key using route access checking

The trick here is to add as a requirement.
```php

vactory_decoupled.translations:
  path: '/api/translations'
  defaults:
    _controller: '\Drupal\vactory_decoupled\Controller\TranslationsController::index'
  methods: [GET]
  requirements:
    _route_api_key_auth__access_check: 'TRUE'

```
