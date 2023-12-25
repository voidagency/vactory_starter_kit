# Vactory decoupled router
When a user requests a page, vactory decoupled router serves Next.js which sends
a request to Drupal to get the necessary route information for the requested
page. Once the information is received, the Next.js app stores it in the Redis 
cache.

### Installation
`drush en vactory_decoupled_router -y`

### Dependencies
- vactory_decoupled
- decoupled_router

### Older content of this file
Make sure this patch is applied.

https://www.drupal.org/files/issues/2021-07-28/json_api_q_param.patch

# TODO:

- Make protect this endpoint using an API KEY.