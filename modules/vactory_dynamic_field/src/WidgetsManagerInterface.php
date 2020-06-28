<?php

namespace Drupal\vactory_dynamic_field;

/**
 * Interface for the class that gathers the provider plugins.
 */
interface WidgetsManagerInterface {

  /**
   * Get an options list suitable for form elements for provider selection.
   *
   * @return array
   *   An array of options keyed by plugin ID with label values.
   */
  public function getProvidersOptionList();

  /**
   * {@inheritdoc}
   */
  public function getWidgetsList();

}
