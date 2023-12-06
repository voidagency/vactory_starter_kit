<?php

/**
 * @file
 * Hooks specific to the Vactory jsonapi disable resources.
 */

/**
 * Implements hook_jsonapi_disable_resources_alter().
 */
function hook_jsonapi_disable_resources_alter(&$global_disabled_fields, &$disabled_resources, &$disabled_fields_per_resource) {
  $disabled_resources[] = 'node--vactory_blog';
  $global_disabled_fields['node'] = [
      'vid'
  ];
  $disabled_fields_per_resource['node--vactory_blog'] = [
      'vid',
  ];
  $disabled_fields_per_resource['user--user'] = [
      'face_id',
      'otp',
  ];
}
