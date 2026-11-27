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
 *
 * Our class extends a class of views_autocomplete_filters. Without that module
 * (a site where it was never enabled, until
 * vactory_google_places_update_10001() runs), the core class is kept:
 * otherwise every view with a text filter fails.
 */
function vactory_google_places_views_plugins_filter_alter(array &$plugins) {
  if (isset($plugins['string']) && \Drupal::moduleHandler()->moduleExists('views_autocomplete_filters')) {
    $plugins['string']['class'] = VactoryGooglePlacesString::class;
  }
}
