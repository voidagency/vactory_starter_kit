<?php

namespace Drupal\vactory_user_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Remove spaces (or other characters) from the beginning and end of string.
 *
 * @MigrateProcessPlugin(
 *   id = "vactory_trim"
 * )
 */
class VactoryTrim extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $characters = $this->configuration['characters'] ?? '';
    if (!empty($characters)) {
      return trim($value, $characters);
    }
    return trim($value);
  }

}
