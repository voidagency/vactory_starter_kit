<?php

/**
 * @file
 * Contains vactory_extended_seo.page.inc.
 *
 * Page callback for Vactory extended seo entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Vactory extended seo templates.
 *
 * Default template: vactory_extended_seo.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_vactory_extended_seo(array &$variables) {
  // Fetch VactoryExtendedSeo Entity Object.
  $vactory_extended_seo = $variables['elements']['#vactory_extended_seo'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
