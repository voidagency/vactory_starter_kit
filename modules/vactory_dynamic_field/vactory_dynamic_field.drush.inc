<?php

/**
 * @file
 * Main Drush file for Vactory dynamic field.
 */

use Drupal\media\Entity\Media;

/**
 * Implements hook_drush_command().
 */
function vactory_dynamic_field_drush_command() {
  return [
    'vdf_delete_files' => [
      'aliases'     => ['vdf'],
      'callback'    => '_vdf_delete_files',
      'description' => "Delete all the files in public folder that contains the keyword '__placeholder__vdf__'",
      'arguments'   => [],
      'examples'    => [
        'drush vdf'  => dt("Delete all files with keyword '__placeholder__vdf__'"),
      ],
    ],
  ];
}

/**
 * Drush commend callback function.
 */
function _vdf_delete_files() {

  $medias = Media::loadMultiple();
  foreach ($medias as $media) {
    if (is_dummy_file($media->getName())) {
      $media->delete();
    }
  }

  $file_storage = \Drupal::service('entity.manager')->getStorage('file');
  $fids = Drupal::entityQuery('file')->execute();
  $files = $file_storage->loadMultiple($fids);

  foreach ($files as $file) {
    if (is_dummy_file($file->get('filename')->value)) {
      $file->delete();
    }
  }

}

/**
 * Function to check if a string contains the keyword __placeholder__vdf__.
 *
 * @param string $fileName
 *   File name.
 *
 * @return bool
 *   Is the file dummy.
 */
function is_dummy_file($fileName) {
  if (strpos($fileName, '__placeholder__vdf__') !== FALSE) {
    return TRUE;
  }
  return FALSE;
}
