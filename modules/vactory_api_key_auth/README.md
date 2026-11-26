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

Send the key in the `apikey` header. The key is not read from the query
string or from a form body (they end up in server logs and browser history).

The provider is not global: it authenticates only on routes that list
`api_key_auth` in their `_auth` option (JSON:API routes list every provider).
A key linked to a blocked account is refused. Link keys to technical accounts
with the permissions they need, never to an administrator.

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
