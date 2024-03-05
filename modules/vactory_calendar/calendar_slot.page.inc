<?php

/**
 * @file
 * Contains calendar_slot.page.inc.
 *
 * Page callback for Calendar slot entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Calendar slot templates.
 *
 * Default template: calendar_slot.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_calendar_slot(array &$variables) {
  // Fetch CalendarSlot Entity Object.
  $calendar_slot = $variables['elements']['#calendar_slot'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
