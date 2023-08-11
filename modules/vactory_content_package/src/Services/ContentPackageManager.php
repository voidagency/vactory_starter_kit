<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\paragraphs\Entity\Paragraph;

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
   * {@inheritDoc}
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
  public function normalize(EntityInterface $entity): array {
    $entity_type = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $entity_values = $entity->toArray();
    $entity_values = array_diff_key($entity_values, array_flip(ContentPackageManagerInterface::UNWANTED_KEYS));
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($entity_values as $field_name => &$field_value) {
      $field_definition = $fields[$field_name] ?? NULL;
      if ($field_definition) {
        $field_type = $field_definition->getType();
        $cardinality = $field_definition->getFieldStorageDefinition()
          ->getCardinality();
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
          $field_value = !$is_multiple ? date('d/m/Y H:i', $field_value[0]['value']) : array_map(fn($value) => date('d/m/Y H:i', $value['value']), $field_value);
        }
        if ($field_type === 'entity_reference') {
          if (empty($field_value)) {
            // Inform others whether the field is multiple or not.
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
              foreach ($users as $i => $user) {
                $field_value[$i] = $user->get('name')->value;
              }
              $field_value = !$is_multiple ? reset($field_value) : $field_value;
            }
            if (empty($field_value)) {
              // Inform others whether the field is multiple or not.
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
              // Inform others whether the field is multiple or not.
              $field_value = !$is_multiple ? '' : [];
            }
          }

          // Taxonomy term entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'taxonomy_term') {
            $term_ids = $this->getFieldValue($field_value, $is_multiple, TRUE, 'target_id');
            $field_value = [];
            if (!empty($term_ids)) {
              $terms = $this->entityTypeManager->getStorage('taxonomy_term')
                ->loadMultiple($term_ids);
              $terms = array_values($terms);
              if (!empty($terms)) {
                foreach ($terms as $i => $term) {
                  $term_id = $term->get('term_id')->value;
                  if ($term_id) {
                    $field_value[$i] = $term_id;
                  }
                }
              }
              $field_value = !$is_multiple ? reset($field_value) : $field_value;
            }
            if (empty($field_value)) {
              // Inform others whether the field is multiple or not.
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
              $paragraphs = $this->entityTypeManager->getStorage('paragraph')
                ->loadMultiple($paragraphs_ids);
              $paragraphs = array_values($paragraphs);
              foreach ($paragraphs as $i => $paragraph) {
                $paragraph_values = $this->normalize($paragraph);
                unset(
                  $paragraph_values['status'],
                  $paragraph_values['created'],
                );
                $appearance_fields = array_intersect_key($paragraph_values, array_flip(ContentPackageManagerInterface::PARAGRAPHS_APPEARANCE_KEYS));
                $no_appearance_fields = array_diff_key($paragraph_values, array_flip(ContentPackageManagerInterface::PARAGRAPHS_APPEARANCE_KEYS));
                $field_value[$i] = [
                  ...$no_appearance_fields,
                  ...[
                  'appearance' => $appearance_fields,
                ],
                ];
              }
            }
          }
        }
      }
    }
    $entity_values['entity_type'] = $entity_type;
    if ($entity_type === 'node') {
      unset($entity_values['nid']);
    }
    if ($entity_type === 'paragraph') {
      unset($entity_values['id']);
    }
    return $entity_values;
  }

  /**
   * Denormalize given entity.
   */
  public function denormalize(array $entity_values): array {
    $values = [];
    $entity_type = $entity_values['entity_type'] ?? NULL;
    unset($entity_values['entity_type']);
    $bundle = $entity_values['type'] ?? NULL;
    if (empty($entity_type) || empty($bundle)) {
      return $values;
    }

    if ($entity_type === 'paragraph') {
      $appearance = $entity_values['appearance'] ?? [];
      unset($entity_values['appearance']);
      $entity_values = [...$entity_values, ...$appearance];
    }
    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($entity_values as $field_name => $field_value) {
      $field_definition = $fields[$field_name] ?? NULL;
      if ($field_definition) {
        $field_type = $field_definition->getType();
        $cardinality = $field_definition->getFieldStorageDefinition()
          ->getCardinality();
        $is_multiple = $cardinality > 1 || $cardinality <= -1;
        $field_settings = $field_definition->getSettings();
        if (in_array($field_type, ContentPackageManagerInterface::PRIMITIVE_TYPES)) {
          if (!empty($field_value)) {
            $values[$field_name] = $field_value;
          }
          if ($field_type === 'boolean' && empty($field_value)) {
            $values[$field_name] = 0;
          }
        }
        if (in_array($field_type, ContentPackageManagerInterface::DATE_TIME_TYPES) && !empty($field_value)) {
          if (is_string($field_value)) {
            $values[$field_name] = $this->getTimestamp('d/m/Y H:i', $field_value);
          }
          if (is_array($field_value)) {
            foreach ($field_value as &$v) {
              $v = $this->getTimestamp('d/m/Y H:i', $v);
            }
            $values[$field_name] = $field_value;
          }
        }
        if ($field_type === 'entity_reference' && !empty($field_value)) {
          // User entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'user') {
            $field_value = is_array($field_value) ? $field_value : [$field_value];
            $users_ids = $this->entityTypeManager->getStorage('user')
              ->getQuery()
              ->accessCheck(FALSE)
              ->condition('name', $field_value, 'IN')
              ->execute();
            if (!empty($users_ids)) {
              $users_ids = array_map(fn($id) => ['target_id' => $id], $users_ids);
              $values[$field_name] = $users_ids;
            }
          }

          // Media entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'media' && !empty($field_value)) {
            $field_value = is_array($field_value) ? $field_value : [$field_value];
            if (!empty($field_value) && isset($field_settings['handler_settings']['target_bundles'])) {
              $bundle = reset($field_settings['handler_settings']['target_bundles']);
              $bundle = is_array($bundle) ? array_values($bundle)[0] : $bundle;
              if (isset($bundle)) {
                $mids = [];
                foreach ($field_value as $value) {
                  $mid = $this->generateMediaFromUrl($value, $bundle);
                  if (isset($mid)) {
                    $mids[] = ['target_id' => $mid];
                  }
                }
                if (!empty($mids)) {
                  $values[$field_name] = $mids;
                }
              }
            }
          }

          // Taxonomy term entity reference field.
          if (isset($field_settings['target_type']) && $field_settings['target_type'] === 'taxonomy_term' && !empty($field_value)) {
            $field_value = is_array($field_value) ? $field_value : [$field_value];
            $terms_ids = $this->entityTypeManager->getStorage('taxonomy_term')
              ->getQuery()
              ->accessCheck(FALSE)
              ->condition('term_id', $field_value, 'IN')
              ->execute();
            if (!empty($terms_ids)) {
              $terms_ids = array_map(fn($id) => ['target_id' => $id], $terms_ids);
              $values[$field_name] = $terms_ids;
            }
          }

          // Entity type reference field.
          if ($field_name === 'type' && isset($field_settings['target_type']) && in_array($field_settings['target_type'], ContentPackageManagerInterface::ENTITY_TYPES_KEYS) && !empty($field_value)) {
            $field_value = is_array($field_value) ? $field_value : [$field_value];
            $values[$field_name] = array_map(fn($id) => ['target_id' => $id], $field_value);
          }
        }
        if ($field_type === 'colorapi_color_field' && !empty($field_value)) {
          $field_value = is_array($field_value) ? $field_value : [$field_value];
          $field_value = array_map(fn($el) => ['color' => $el], $field_value);
          $values[$field_name] = $field_value;
        }
        if ($field_type === 'path' && !empty($field_value)) {
          $field_value = is_array($field_value) ? $field_value : [$field_value];
          $field_value = array_map(fn($el) => ['alias' => $el], $field_value);
          $values[$field_name] = $field_value;
        }
        if ($field_type === 'field_wysiwyg_dynamic' && !empty($field_value)) {
          // DF field type.
          $field_value['widget_data'] = Json::encode($field_value['widget_data']);
          $values[$field_name] = [$field_value];
        }
        if ($field_type === 'entity_reference_revisions' && isset($field_settings['target_type']) && $field_settings['target_type'] === 'paragraph') {
          // Paragraphs entity type case.
          $field_value = empty($field_value) ? [] : $field_value;
          if (!empty($field_value)) {
            foreach ($field_value as &$paragraph) {
              $paragraph_values = $this->denormalize($paragraph);
              $paragraph_entity = Paragraph::create($paragraph_values);
              $paragraph_entity->save();
              $paragraph = [
                'target_id' => $paragraph_entity->id(),
                'target_revision_id' => $this->entityTypeManager
                  ->getStorage('paragraph')
                  ->getLatestRevisionId($paragraph_entity->id()),
              ];
            }
            $values[$field_name] = $field_value;
          }
        }
      }
    }
    return $values;
  }

  /**
   * Get field value depending on its cardinality.
   */
  protected function getFieldValue($fieldValue, $isMultiple = FALSE, $arrayFormat = FALSE, $key = 'value') {
    $value = !$isMultiple ? $fieldValue[0][$key] : array_map(fn($value) => $value[$key], $fieldValue);
    return $arrayFormat && !is_array($value) ? [$value] : $value;
  }

  /**
   * Get timestamp from date string.
   */
  protected function getTimestamp($format, $dateString) {
    $dateTime = \DateTime::createFromFormat($format, $dateString);
    if ($dateTime) {
      return $dateTime->getTimestamp();
    }
    throw new \Exception('Invalid datetime format, format must be ' . $format . ' "' . $dateString . '" given.<br>' . Json::encode($entity_values));
  }

  /**
   * Generate media from the given url.
   */
  public function generateMediaFromUrl(string $url, string $type): int {
    $media = NULL;
    switch ($type) {
      case 'remote_video':
        $media = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => $type,
          'uid' => '1',
          self::MEDIA_FIELD_NAMES[$type] => $url,
        ]);
        break;

      case 'image':
      case 'file':
      case 'onboarding_video':
      case 'video';
      case 'audio':
        if (!file_exists('public://content_package_manager')) {
          mkdir('public://content_package_manager', 0777);
        }
        $filename = pathinfo($url);
        $filename = $filename['filename'];
        $filename = preg_replace("/-[^-]*$/", "", $filename);
        $filename = ucfirst(strtolower(str_replace('-', ' ', $filename)));
        $file = system_retrieve_file($url, 'public://content_package_manager', TRUE, FileSystemInterface::EXISTS_RENAME);
        if ($file instanceof FileInterface) {
          $file->save();
          $media_data = [
            'bundle' => $type,
            'uid' => '1',
            self::MEDIA_FIELD_NAMES[$type] => [
              'target_id' => $file->id(),
              'title' => $filename,
              'alt' => $filename,
            ],
          ];
          if ($type == 'image') {
            $file_metadata = $file->getAllMetadata() ?? [];
            if (!empty($file_metadata)) {
              $media_data[self::MEDIA_FIELD_NAMES[$type]]['width'] = $file_metadata['width'];
              $media_data[self::MEDIA_FIELD_NAMES[$type]]['height'] = $file_metadata['height'];
            }
          }
          $media = $this->entityTypeManager->getStorage('media')
            ->create($media_data);
        }
        break;
    }

    if (!isset($media)) {
      return NULL;
    }

    $media->setPublished(TRUE)->save();
    return $media->id();
  }

}
