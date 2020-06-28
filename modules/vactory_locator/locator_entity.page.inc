<?php

/**
 * @file
 * Contains locator_entity.page.inc.
 *
 * Page callback for Locator Entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Locator Entity templates.
 *
 * Default template: locator_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_locator_entity(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
