<?php

/**
 * @file
 * Integration with Views.
 */

use Drupal\vactory_google_places\Plugin\views\filter\VactoryGooglePlacesString;

/**
 * Implements hook_views_plugins_filter_alter().
 *
 * Views picks the filter handler from views data ("string"), not from the
 * plugin_id stored in the view config. views_autocomplete_filters swaps the
 * "string" class for its own; we swap it again for our subclass (which only
 * changes behavior when its "vactory_google_places" option is enabled).
 * Runs after views_autocomplete_filters, see
 * vactory_google_places_module_implements_alter().
 */
function vactory_google_places_views_plugins_filter_alter(array &$plugins) {
  if (isset($plugins['string'])) {
    $plugins['string']['class'] = VactoryGooglePlacesString::class;
  }
}
