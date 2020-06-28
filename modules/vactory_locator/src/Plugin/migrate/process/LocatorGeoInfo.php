<?php

namespace Drupal\vactory_locator\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Locator Geo Info process Plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "locator_geaography_info"
 * )
 */
class LocatorGeoInfo extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $transformed['lat'] = $value[0];
    $transformed['lon'] = $value[1];
    return $transformed;
  }

}
