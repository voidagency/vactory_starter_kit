<?php
/**
 * @file
 * Integration with Views.
 */

/**
 * Implements hook_views_data_alter().
 */
function vactory_google_places_views_data_alter(array &$data) {
  foreach ($data as $table_name => $table_data) {
    foreach ($table_data as $field_name => $field_data) {
      // Extend String handler with autocomplete capabilities.
      // views_autocomplete_filters 2.x extends the core "string" filter
      // instead of providing its own "views_autocomplete_filters_string" id.
      if (isset($field_data['filter']['id']) && in_array($field_data['filter']['id'], ['string', 'views_autocomplete_filters_string'], TRUE)) {
        $data[$table_name][$field_name]['filter']['id'] = 'vactory_google_places';
      }
    }
  }
}
