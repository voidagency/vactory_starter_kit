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

### Page entity types
The router (`/api/router?path=…`) resolves a path to an entity only when the
type of this entity is in the list of page entity types. A path to an entity of
another type gets the error page with a 404 status, like an unknown path.

The list is built in this order:

1. the `page_entity_types` key of the `vactory_decoupled_router.settings`
   config, `node` only by default. Form:
   `/admin/config/system/vactory_router/page-entity-types` (permission
   `administer site configuration`);
2. `$settings['vactory_decoupled_router_page_entity_types']` in `settings.php`
   replaces the config when it is defined, for example with the value
   `['node', 'taxonomy_term']`;
3. `hook_vactory_decoupled_router_page_entity_types_alter()` alters the result
   (see `vactory_decoupled_router.api.php`).

A front that displays taxonomy terms or custom entities through the router has
to add these types to the list.

### Canonical address only
Only the canonical address of a page is resolved: its alias, or its exact
internal path (`/node/12`, redirected to the alias when there is one). The
other routes of the same entity (`/node/12/edit`, `/delete`, `/revisions`,
`/latest`, `/translations`, its JSON:API resource) and the other spellings of
the internal path (`/NODE/12`, `/node/012`, `/node/12/`) get the error page
with a 404 status and no `redirect` entry, like an unknown path. The rule is
the same for every page entity type. It applies to the requested path, not to
the path stored in a system route (`/admin/config/system/vactory_router`).

An unknown or unpublished `/node/12` gets the same answer, without redirect to
the alias of the error page. A `path` starting with two slashes is not a path
of the site.

### Alias variants
The alias lookup ignores the letter case (and the accents with the usual
database collations). A path that is the alias of a node written in another
way (letter case, accents, trailing slash) is answered with a permanent
redirect to the stored alias: `status` 301 and a `redirect` entry whose
`status` is the string `"301"`, like the redirects of the Redirect module.

### Older content of this file
Make sure this patch is applied.

https://www.drupal.org/files/issues/2021-07-28/json_api_q_param.patch

# TODO:

- Make protect this endpoint using an API KEY.
