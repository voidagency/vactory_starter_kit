<?php

namespace Drupal\vactory_user_migrate\Plugin\migrate\process;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Hash user password migrate process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "vactory_file"
 * )
 */

class VactoryFile extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   * @throws MigrateSkipRowException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $target_id = NULL;
    if (!empty($value)) {
      $parsed_url = parse_url($value);
      $destination = PublicStream::basePath() . "/users-pics";
      if (!file_exists($destination)) {
        mkdir($destination, 0777, TRUE);
      }
      if (isset($parsed_url['path'])) {
        $parsed_path = pathinfo($parsed_url['path']);
        if (isset($parsed_path['extension']) && isset($parsed_path['basename'])) {
          $filename = $parsed_path['basename'];
          $file_path = "${destination}/${filename}";
          $downloaded_file = file_get_contents($value);
          file_put_contents($file_path, $downloaded_file);
          $file = File::create([
            'uid'      => 1,
            'filename' => $filename,
            'uri'      => $file_path,
            'status'   => 1,
          ]);
          $file->save();
          $target_id = $file->id();
        }
      }
    }
    return $target_id;
  }

}
