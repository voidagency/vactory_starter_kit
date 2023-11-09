<?php

/**
 * @file
 * Hooks specific to the Vactory decoupled webform module.
 */

/**
 * Alter the internal block classification.
 *
 * @param array $schema
 *   The webform schema.
 * @param string $webform_id
 *   The webform id.
 */
function hook_decoupled_webform_schema_alter(array &$schema, $webform_id) {
  if ($webform_id === 'toto') {
    $schema['telephone']['type'] = 'select';
  }
}
