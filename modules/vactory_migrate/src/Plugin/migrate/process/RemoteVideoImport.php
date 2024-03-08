<?php

namespace Drupal\vactory_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate_file\Plugin\migrate\process\FileImport;

/**
 * Migrate remote video media.
 *
 * Example:
 *
 * @code
 * process:
 *   field_media:
 *     plugin: remote_video_import
 *     source: url
 *
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "remote_video_import"
 * )
 */
class RemoteVideoImport extends FileImport {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!$value) {
      return NULL;
    }
    $media = Media::create([
      'bundle' => 'remote_video',
      'uid' => '1',
      'field_media_oembed_video' => $value,
    ]);
    try {
      $media->setPublished(TRUE)->save();
    }
    catch (EntityStorageException $e) {
      return NULL;
    }
    return $media->id();
  }

}
