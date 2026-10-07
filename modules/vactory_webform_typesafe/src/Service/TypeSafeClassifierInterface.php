<?php

namespace Drupal\vactory_webform_typesafe\Service;

/**
 * Defines the structured classification boundary.
 */
interface TypeSafeClassifierInterface {

  /**
   * Returns validated answers, model and usage; never generated prose. */

  /**
   * {@inheritdoc}
   */
  public function classify(array $state, array $questions, string $model): array;

}
