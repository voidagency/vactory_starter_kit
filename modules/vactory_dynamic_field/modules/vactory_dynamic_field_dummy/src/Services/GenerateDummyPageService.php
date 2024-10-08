<?php

namespace Drupal\vactory_dynamic_field_dummy\Services;

use Drupal\Component\Utility\Random;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;
use Drupal\file_entity\Entity\FileEntity;
use Drupal\media\Entity\Media;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Generate dummy content service.
 */
class GenerateDummyPageService {

  /**
   * Prepare content.
   */
  public static function prepareContent(array $settings): array {
    $multiple = $settings['multiple'] ?? FALSE;
    $limit = $multiple ? ($settings['limit'] ?? 3) : 1;
    $fields = $settings['fields'] ?? [];
    $extraFields = $settings['extra_fields'] ?? [];
    $fieldsContent = [
      'extra_field' => [],
      'pending_content' => [],
    ];

    // Prepare main content.
    for ($i = 0; $i < $limit; $i++) {
      $item = [];
      foreach ($fields as $key => $field) {
        if (strpos($key, 'group_') === 0) {
          unset($field['g_title']);
          foreach ($field as $field_key => $value) {
            $item[$key][$field_key] = static::prepareContentForField($value);
          }
        }
        else {
          $item[$key] = static::prepareContentForField($field);
        }
      }
      array_push($fieldsContent, $item);
    }
    // Prepare extra fields.
    foreach ($extraFields as $key => $extraField) {
      if (strpos($key, 'group_') === 0) {
        unset($field['g_title']);
        foreach ($extraField as $field_key => $value) {
          $fieldsContent['extra_field'][$key][$field_key] = static::prepareContentForField($value);
        }
      }
      else {
        $fieldsContent['extra_field'][$key] = self::prepareContentForField($extraField);
      }
    }

    return $fieldsContent;
  }

  /**
   * Prepare content for field.
   */
  private static function prepareContentForField($field) {
    $item = NULL;
    $random = new Random();
    switch ($field['type'] ?? "") {
      case 'text':
        $item = $random->sentences(4);
        break;

      case 'textarea':
        $item = $random->sentences(14);
        break;

      case 'text_format':
        $item = [
          "value" => "<p>{$random->paragraphs(5)}</p>",
          "format" => "basic_html",
        ];
        break;

      case 'url_extended':
        $item = [
          'title' => $random->word(12),
          'url' => 'http://void.fr',
          'attributes' => [
            'target' => '_self',
            'rel' => '',
            'class' => '',
            'id' => "link-{$random->machineName(14)}",
          ],
        ];
        break;

      case 'image':
        $item = self::prepareImageField($random);
        break;

      case 'file':
        $item = self::prepareFileField($random);
        break;

      case 'remote_video':
        $item = self::prepareRemoteVideoField($random);
        break;

      case 'checkbox':
        $item = 1;
        break;

      case 'vactory_icon_picker':
        $item = 'arrow-circle-left-solid';
        break;

      case 'json_api_collection':
      case 'json_api_cross_bundles':
      case 'node_queue':
        $item = $field['options']['#default_value'];
        break;

      case 'webform_decoupled':
        $item = [
          'id' => $field['options']['#default_value']['webform_id'] ?? 'contact',
          'style' => '',
          'buttons' => '',
        ];
        break;

    }
    return $item;
  }

  /**
   * Prepare image field.
   */
  private static function prepareImageField($random) {
    try {
      $random_number = rand(1, 20);
      $image_data = file_get_contents("https://picsum.photos/id/" . $random_number . "/650/650.jpg");
      $file_repository = \Drupal::service('file.repository');
      $image = $file_repository->writeData($image_data, "public://generated-media-for-dummy-content-{$random_number}.png", FileSystemInterface::EXISTS_REPLACE);
      $image_file = File::load($image->id());
      $image_info = getimagesize($image_file->getFileUri());
      $image_file->save();

      $image_media = Media::create([
        'name' => 'Generated Media for dummy content',
        'bundle' => 'image',
        'uid' => \Drupal::currentUser()->id(),
        'field_media_image' => [
          'target_id' => $image_file->id(),
          'alt' => t('Generated Media for dummy content'),
          'title' => t('Generated Media for dummy content'),
          'width' => $image_info[0] ?? 200,
          'height' => $image_info[1] ?? 200,
        ],

      ]);
      $image_media->setPublished()->save();
      return [
        $random->machineName(32) => [
          "selection" => [
            [
              "remove_button" => "Remove",
              "target_id" => $image_media->id(),
              "weight" => 0,
            ],
          ],
          "open_button" => "Add media",
          "media_library_selection" => "",
          "media_library_update_widget" => "Update widget",
        ],
      ];
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return NULL;
    }
  }

  /**
   * Prepare file field.
   */
  private static function prepareFileField($random) {
    $extension_list = \Drupal::service('extension.list.module');
    $filepath = $extension_list->getPath('vactory_dynamic_field_dummy') . '/assets/pdf-test.pdf';
    $file_media = NULL;
    if (!file_exists("public://" . basename($filepath))) {
      $directory = 'public://';
      $file_system = \Drupal::service('file_system');
      $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
      $file_system->copy($filepath, $directory . '/' . basename($filepath), FileSystemInterface::EXISTS_REPLACE);
      $file = File::create([
        'filename' => basename($filepath),
        'uri' => 'public://' . basename($filepath),
        'status' => 1,
        'uid' => \Drupal::currentUser()->id(),
      ]);
      $file->isNew();
      $file->save();
      $file_media = Media::create([
        'name' => 'Generated Media File for dummy content',
        'bundle' => 'file',
        'uid' => \Drupal::currentUser()->id(),
        'status' => 0,
        'field_media_file' => [
          'target_id' => $file->id(),
        ],
      ]);
      $file_media->setPublished()->save();
    }
    else {
      $files = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->loadByProperties(['uri' => 'public://' . basename($filepath)]);
      $file = reset($files) ?: NULL;
      $file_media = Media::create([
        'name' => 'Generated Media File for dummy content',
        'bundle' => 'file',
        'uid' => \Drupal::currentUser()->id(),
        'status' => 0,
        'field_media_file' => [
          'target_id' => $file->id(),
        ],
      ]);
      $file_media->setPublished()->save();
    }
    if ($file instanceof FileEntity) {
      return [
        $random->machineName(32) => [
          "selection" => [
            [
              "remove_button" => "Remove",
              "target_id" => $file_media->id(),
              "weight" => 0,
            ],
          ],
          "open_button" => "Add media",
          "media_library_selection" => "",
          "media_library_update_widget" => "Update widget",
        ],
      ];
    }
    return NULL;
  }

  /**
   * Prepare remote video field.
   */
  private static function prepareRemoteVideoField($random) {
    try {
      $video_media = Media::create([
        'bundle' => 'remote_video',
        'uid' => 1,
        'name' => 'DrupalCon Prague 2022 Driesnote',
        'field_media_oembed_video' => [
          'value' => 'https://www.youtube.com/watch?v=XrM-ZneIUPw',
        ],
      ]);
      $video_media->save();
      return [
        $random->machineName(32) => [
          "selection" => [
            [
              "remove_button" => "Remove",
              "target_id" => $video_media->id(),
              "weight" => 0,
            ],
          ],
          "open_button" => "Add media",
          "media_library_selection" => "",
          "media_library_update_widget" => "Update widget",
        ],
      ];
    }
    catch (\Exception $e) {
      \Drupal::messenger()->addError($e->getMessage());
      return NULL;
    }
  }

  /**
   * Create paragraph.
   */
  public static function createParagraph($widget_id, $widget_data) {
    $paragraph = [
      "type" => "vactory_component",
      "field_vactory_title" => "Generated Template For : {$widget_id}",
      "field_vactory_component" => [
        "widget_id" => $widget_id,
        "widget_data" => json_encode($widget_data),
      ],
    ];

    $paragraph = Paragraph::create($paragraph);
    $paragraph->save();
    return $paragraph;
  }

  /**
   * Matches pattern string:string.
   */
  public static function matchesPattern($string) {
    $pattern = '/^([\w\-]+):([\w\-]+)$/';

    if (preg_match($pattern, $string, $matches)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
