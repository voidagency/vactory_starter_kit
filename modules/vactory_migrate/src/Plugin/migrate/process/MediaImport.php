<?php

namespace Drupal\vactory_migrate\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\media\Entity\Media;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Row;
use Drupal\file\Entity\File;
use Drupal\migrate_file\Plugin\migrate\process\FileImport;

/**
 * Imports a file from an local or external source.
 *
 * Files will be downloaded or copied from the source if necessary and a file
 * entity will be created for it. The file can be moved, reused, or set to be
 * automatically renamed if a duplicate exists.
 *
 * Required configuration keys:
 * - source: The source path or URI, e.g. '/path/to/foo.txt' or
 *   'public://bar.txt'.
 *
 * Optional configuration keys:
 * - destination: (recommended) The destination path or URI to import the file
 *   to. If no destination is set, it will default to "public://".
 *   The destination property works like the source in that you can reference
 *   source or destination properties for its value. This allows you to build
 *   dynamic destination paths based on source or destination values (see the
 *   "Dynamic File Path Destinations" section below for an example). However,
 *   this means if you want to assign a static destination value in your
 *   migration, you will need to use a constant.
 *
 *   @see https://www.drupal.org/docs/8/api/migrate-api/migrate-process/constant-values
 *   To provide a directory path (to which the file is saved using its original
 *   name), a trailing slash *must* be used to differentiate it from being a
 *   filename. If no trailing slash is provided the path will be assumed to be
 *   the destination filename.
 * - uid: The uid to attribute the file entity to. Defaults to 0
 * - move: Boolean, if TRUE, move the file, otherwise copy the file. Only
 *   applies if the source file is local. If the source file is remote it will
 *   be copied. Defaults to FALSE.
 * - file_exists: (optional) Replace behavior when the destination file already
 *   exists:
 *   - 'replace' - (default) Replace the existing file.
 *   - 'rename' - Append _{incrementing number} until the filename is
 *       unique.
 *   - 'use existing' - Do nothing and return FALSE.
 * - skip_on_missing_source: (optional) Boolean, if TRUE, this field will be
 *   skipped if the source file is missing (either not available locally or 404
 *   if it's a remote file). Otherwise, the row will fail with an error. Note
 *   that if you are importing a lot of remote files, this check will greatly
 *   reduce the speed of your import as it requires an http request per file to
 *   check for existence. Defaults to FALSE.
 * - source_check_method: The HTTP Request method used to check if the file
 *   exists when skip_on_missing_source is set. Either HEAD or GET. A HEAD
 *   request is faster than a GET since the file isn't actually downloaded,
 *   but not all servers support it. Switch to GET if necessary.
 * - skip_on_error: Boolean, if TRUE, this field will be skipped if any error
 *   occurs during the file import (including missing source files). Otherwise,
 *   the row will fail with an error. Defaults to FALSE.
 * - guzzle_options: Guzzle options which will be used for requests if the
 *   source file is a remote file. This will be used for the file check if
 *   skip_on_missing_source is set, as well as for the file Download itself.
 *   @see Drupal\migrate\Plugin\migrate\process\Download
 * - id_only: Boolean, if TRUE, the process will return just the id instead of
 *   an entity reference array. Useful if you want to manage other sub-fields
 *   in your migration (see example below).
 * - media_bundle: Media targeted bundle default to image.
 * - media_field_name: Media file field name default to field_media_image.
 * - media_name: Media name default to media file name.
 *
 * The destination and uid configuration fields support referencing destination
 * values. These are indicated by a prifixing with the @ character. Values
 * using @ must be wrapped in quotes. (the same as it works with the 'source'
 * property).
 *
 * @see Drupal\migrate\Plugin\migrate\process\Get
 *
 * Example:
 *
 * @code
 * destination:
 *   plugin: entity:node
 * source:
 *   # assuming we're using a source plugin that lets us define fields like this
 *   fields:
 *     -
 *       name: file
 *       label: 'Some file'
 *       selector: /file
 *     -
 *       name: image
 *       label: 'Main Image'
 *       selector: /image
 *     -
 *       name: text_field_1
 *       label: 'Some Text Value'
 *       selector: /text
 *     -
 *       name: text_field_2
 *       label: 'Another Text Value'
 *       selector: /text_2
 *   constants:
 *     # Note the trailing slash indicates this destination is a directory so
 *     # the filename will be kept intact when copying
 *     file_destination: 'public://path/to/save/'
 *     # This is for creating dynamic destination paths (see below)
 *     directory_separator: '/'
 * process:
 *   uid:
 *     plugin: default_value
 *     default_value: 1
 *   #
 *   # Simple media import
 *   #
 *   field_file:
 *     plugin: media_import
 *     source: file
 *     destination: constants/file_destination
 *     uid: @uid
 *     skip_on_missing_source: true
 *     media_bundle: 'image',
 *     media_field_name: 'field_media_image',
 *
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "media_import"
 * )
 */
class MediaImport extends FileImport {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StreamWrapperManagerInterface $stream_wrappers, FileSystemInterface $file_system, MigrateProcessInterface $download_plugin) {
    $configuration += [
      'destination' => NULL,
      'uid' => NULL,
      'skip_on_missing_source' => FALSE,
      'source_check_method' => 'HEAD',
      'skip_on_error' => FALSE,
      'id_only' => TRUE,
      'guzzle_options' => [],
      'media_bundle' => 'image',
      'media_field_name' => 'field_media_image',
      'media_name' => NULL,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $stream_wrappers, $file_system, $download_plugin);
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if ($this->isValidPattern($value)) {
      $exract = $this->extractTextWithParentheses($value);
      $mid = $exract['in'];
      $media = Media::load($mid);
      if ($media instanceof MediaInterface) {
        return $mid;
      }
    }
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (!$value) {
      return NULL;
    }

    $file = File::load($value);
    if ($file) {
      $file_name = $this->configuration['media_name'] ?? $file->getFilename();
      $media_bundle = $this->configuration['media_bundle'] ?? 'image';
      $media_field_name = $this->configuration['media_field_name'] ?? 'field_media_image';
      $data = [
        'bundle' => $media_bundle,
        'name' => $file_name,
        'uid' => \Drupal::currentUser()->id(),
        $media_field_name => [
          'target_id' => $file->id(),
        ],
      ];
      // Assign alt for images.
      if ($media_bundle == 'image') {
        $alt_field = $this->configuration['alt_field'];
        // Get alt from source.
        $alt_value = $row->get($alt_field);
        // Use title instead if alt is not preent in source.
        if (empty($alt_value)) {
          $alt_value = $row->get('-|title|-');
        }
        if (isset($alt_value)) {
          $data[$media_field_name]['alt'] = $alt_value;
        }
      }
      $media = Media::create($data);
      $media->save();
      return $media->id();
    }
    return NULL;
  }

  /**
   * Extract text with parentheses.
   */
  private function extractTextWithParentheses($text) {
    // Define a regular expression pattern for text inside parentheses.
    $pattern = '/\((.*?)\)/';

    // Match all occurrences of text inside parentheses.
    preg_match_all($pattern, $text, $matches);

    // Get the last match (content inside the last parentheses).
    $lastMatch = end($matches[1]);

    // Extract the text outside parentheses.
    $textOutsideParentheses = trim(str_replace(end($matches[0]), '', $text));

    // Return an associative array with extracted text.
    return [
      'in' => $lastMatch ?? '',
      'out' => $textOutsideParentheses,
    ];
  }

  /**
   * Check if the input text matches the given regex pattern.
   */
  private function isValidPattern($text) {
    $pattern = '/\((.*?)\)/';
    return preg_match($pattern, $text) === 1;
  }

}
