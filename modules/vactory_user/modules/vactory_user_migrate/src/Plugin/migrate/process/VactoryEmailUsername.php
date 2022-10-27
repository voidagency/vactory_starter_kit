<?php

namespace Drupal\vactory_user_migrate\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Generate username from email.
 *
 * @MigrateProcessPlugin(
 *   id = "vactory_email_username"
 * )
 */
class VactoryEmailUsername extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($value)) {
      return NULL;
    }
    $email_pieces = explode('@', trim($value));
    return $email_pieces[0] ?? NULL;
  }

}
