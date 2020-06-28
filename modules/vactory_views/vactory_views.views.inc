<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\vactory_views\Plugin\views\filter\DatetimeVactory;
use Drupal\vactory_views\Plugin\views\filter\DateVactory;

/**
 * Implements hook_views_plugins_filter_alter().
 */
function vactory_views_views_plugins_filter_alter(&$info) {
  if (isset($info['date'])) {
    $info['date']['class'] = DateVactory::class;
  }
  if (isset($info['datetime'])) {
    $info['datetime']['class'] = DatetimeVactory::class;
  }
}
