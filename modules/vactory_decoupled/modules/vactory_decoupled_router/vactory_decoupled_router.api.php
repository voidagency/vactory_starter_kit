<?php

/**
 * @file
 * Hooks specific to the Vactory decoupled router module.
 */

/**
 * Alter the entity types the router resolves as pages.
 *
 * A path that leads to an entity of another type gets the error page with a
 * 404 status. The list comes from the "page_entity_types" key of the
 * vactory_decoupled_router.settings config (node by default; form at
 * /admin/config/system/vactory_router/page-entity-types), replaced by
 * $settings['vactory_decoupled_router_page_entity_types'] of settings.php
 * when it is defined. This hook runs last.
 *
 * @param string[] $entity_types
 *   The entity type IDs.
 */
function hook_vactory_decoupled_router_page_entity_types_alter(array &$entity_types) {
  $entity_types[] = 'taxonomy_term';
}
