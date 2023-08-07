<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;

/**
 * Content package manager service.
 */
class ContentPackageManager implements ContentPackageManagerInterface {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File url generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * @inheritDoc
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileUrlGeneratorInterface $fileUrlGenerator,
    EntityFieldManagerInterface $entityFieldManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Normalize given entity.
   */
  public function normalize(EntityInterface $entity) {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_values = $entity->toArray();
    $entity_values = array_diff_key($entity_values, array_flip(ContentPackageManagerInterface::UNWANTED_KEYS));
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($entity_values as $field_name => &$field_value) {
      $field_definition = $fields[$field_name] ?? NULL;
      if ($field_definition) {
        $field_type = $field_definition->getType();
        $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
        $is_multiple = $cardinality > 1 || $cardinality <= -1;
        $field_settings = $field_definition->getSettings();
        if (in_array($field_type, ContentPackageManagerInterface::PRIMITIVE_TYPES)) {
          $field_value = empty($field_value) ? "" : $field_value;
          if (is_array($field_value) && isset($field_value[0]['value'])) {
            $field_value = $this->getFieldValue($field_value, $is_multiple);
          }
          if (is_array($field_value) && isset($field_value[0])) {
            $field_value = empty($field_value[0]) ? "" : $field_value;
          }
        }
        if (in_array($field_type, ContentPackageManagerInterface::DATE_TIME_TYPES)) {
          $field_value = !$is_multiple ? date('m/d/Y H:i', $field_value[0]['value']) : array_map(fn($value) => date('m/d/Y H:i', $value['value']), $field_value);
        }
        if ($field_type === 'entity_reference') {
          if (empty($field_value)) {
            // Help others to distinct between field cardinalities from json output.
            $field_value = !$is_multiple ? '' : [];
            continue;
          }
          // User entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'user') {
            $user_ids = $this->getFieldValue($field_value, $is_multiple, TRUE, 'target_id');
            $field_value = [];
            if (!empty($user_ids)) {
              $users = $this->entityTypeManager->getStorage('user')
                ->loadMultiple($user_ids);
              $users = array_values($users);
              foreach($users as $i => $user) {
                $field_value[$i] = $user->get('name')->value;
              }
              $field_value = !$is_multiple ? reset($field_value) : $field_value;
            }
            if (empty($field_value)) {
              // Help others to distinct between field cardinalities from json output.
              $field_value = !$is_multiple ? '' : [];
            }
          }

          // Media entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'media') {
            $media_ids = $this->getFieldValue($field_value, $is_multiple, TRUE, 'target_id');
            $field_value = [];
            if (!empty($media_ids)) {
              $medias = $this->entityTypeManager->getStorage('media')
                ->loadMultiple($media_ids);
              $medias = array_values($medias);
              if (!empty($medias)) {
                foreach ($medias as $i => $media) {
                  $media_bundle = $media->bundle();
                  $media_field = ContentPackageManagerInterface::MEDIA_FIELD_NAMES[$media_bundle] ?? NULL;
                  if ($media_field) {
                    $media_field_value = $media->get($media_field)->getValue();
                    if ($media_bundle === 'remote_video') {
                      $field_value[$i] = $media_field_value['value'] ?? "";
                      continue;
                    }
                    $fid = $media_field_value[0]['target_id'] ?? NULL;
                    if ($fid) {
                      $file = File::load($fid);
                      if ($file) {
                        $field_value[$i] = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
                      }
                    }
                  }
                }
              }
              $field_value = !$is_multiple ? reset($field_value) : $field_value;
            }
            if (empty($field_value)) {
              // Help others to distinct between field cardinalities from json output.
              $field_value = !$is_multiple ? '' : [];
            }
          }

          // Entity type reference field.
          if ($field_name === 'type' && isset($field_settings['target_type']) && in_array($field_settings['target_type'], ContentPackageManagerInterface::ENTITY_TYPES_KEYS)) {
            $field_value = $this->getFieldValue($field_value, $is_multiple, FALSE, 'target_id');
          }
        }
        if ($field_type === 'colorapi_color_field') {
          $field_value = $this->getFieldValue($field_value, $is_multiple, FALSE, 'color');
        }
        if ($field_type === 'path') {
          $field_value = $this->getFieldValue($field_value, $is_multiple, FALSE, 'alias');
        }
        if ($field_type === 'field_wysiwyg_dynamic' && !empty($field_value)) {
          // DF field type.
          $field_value[0]['widget_data'] = Json::decode($field_value[0]['widget_data']);
          $field_value = reset($field_value);
        }
        if ($field_type === 'entity_reference_revisions' && isset($field_settings['target_type']) && $field_settings['target_type'] === 'paragraph') {
          // Paragraphs entity type case.
          $field_value = empty($field_value) ? [] : $field_value;
          if (!empty($field_value)) {
            $paragraphs_ids = $this->getFieldValue($field_value, $is_multiple, TRUE, 'target_id');
            if (!empty($paragraphs_ids)) {
              $paragraphs = $this->entityTypeManager->getStorage('paragraph')->loadMultiple($paragraphs_ids);
              $paragraphs = array_values($paragraphs);
              foreach ($paragraphs as $i => $paragraph) {
                $paragraph_values = $this->normalize($paragraph);
                unset(
                  $paragraph_values['status'],
                  $paragraph_values['created'],
                );
                $appearance_fields = array_intersect_key($paragraph_values, array_flip(ContentPackageManagerInterface::PARAGRAPHS_APPEARANCE_KEYS));
                $no_appearance_fields = array_diff_key($paragraph_values, array_flip(ContentPackageManagerInterface::PARAGRAPHS_APPEARANCE_KEYS));
                $field_value[$i] = [...$no_appearance_fields, ...['appearance' => $appearance_fields]];
              }
            }
          }
        }
      }
    }
    return $entity_values;
  }

  /**
   * Denormalize given entity.
   */
  public function denormalize(EntityInterface $entity) {
    // todo: Add denormalizer logic here.
  }

  /**
   * Get field value depending on its cardinality.
   */
  protected function getFieldValue($fieldValue, $isMultiple = FALSE, $arrayFormat = FALSE, $key = 'value') {
    $value = !$isMultiple ? $fieldValue[0][$key] : array_map(fn($value) => $value[$key], $fieldValue);
    return $arrayFormat && !is_array($value) ? [$value] : $value;
  }

}
