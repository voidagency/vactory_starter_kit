<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

/**
 * Interface for Checklist plugins.
 */
interface ChecklistInterface {

  /**
   * Runs the check and returns the results.
   *
   * @return array
   *   An array containing:
   *   - status: boolean indicating if check passed
   *   - message: string message describing the result
   *   - details: array of additional details (optional)
   */
  public function runCheck();

  /**
   * Gets the plugin label.
   */
  public function getLabel();

  /**
   * Gets the plugin description.
   */
  public function getDescription();

}
