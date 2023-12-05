<?php

namespace Drupal\vactory_content_package\Services;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\file\FileInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\vactory_dynamic_field\WidgetsManager;

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
   * DF widgets manager service.
   *
   * @var \Drupal\vactory_dynamic_field\WidgetsManager
   */
  protected $widgetsManager;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileUrlGeneratorInterface $fileUrlGenerator,
    EntityFieldManagerInterface $entityFieldManager,
    WidgetsManager $widgetsManager
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->widgetsManager = $widgetsManager;
  }

  /**
   * Normalize given entity.
   */
  public function normalize(EntityInterface $entity, $entity_translation = FALSE): array {
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

        $not_append_to_translated_entity = !($field_type === 'entity_reference_revisions' && isset($field_settings['target_type']) &&
            $field_settings['target_type'] === 'paragraph') && !$field_definition->isTranslatable() && $entity_translation && $field_name !== 'type';

        if ($not_append_to_translated_entity) {
          unset($entity_values[$field_name]);
          continue;
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
          $field_value = $this->normalizeFieldWysiwygDynamic($field_value, $entity_values);
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
    if ($entity_type === 'block_content') {
      unset($entity_values['id']);
    }
    return $entity_values;
  }

  /**
   * Normalize field wysiwyg dynamic.
   */
  public function normalizeFieldWysiwygDynamic($field_value, $entity_values) {
    $widget_id = $field_value[0]['widget_id'];
    $widget_data = Json::decode($field_value[0]['widget_data']);
    $settings = $this->widgetsManager->loadSettings($widget_id);
    if (isset($settings['extra_fields'])) {
      foreach ($settings['extra_fields'] as $name => $field) {
        $df_field_value = $widget_data['extra_field'][$name] ?? NULL;
        if (!empty($df_field_value)) {
          $df_field_value = $this->normalizeDynamicFieldValue($df_field_value, $field, $entity_values);
        }
        $widget_data['extra_field'][$name] = $df_field_value;
      }
    }
    if (isset($settings['fields'])) {
      foreach ($settings['fields'] as $name => $field) {
        foreach ($widget_data as $key => $value) {
          if ($key === 'extra_field') {
            continue;
          }
          $df_field_value = $value[$name] ?? NULL;
          if ($df_field_value) {
            $df_field_value = $this->normalizeDynamicFieldValue($df_field_value, $field, $entity_values);
            $widget_data[$key][$name] = $df_field_value;
          }
        }
      }
    }
    $field_value[0]['widget_data'] = $widget_data;
    return reset($field_value);
  }

  /**
   * Denormalize field wysiwyg dynamic.
   */
  public function denormalizeFieldWysiwygDynamic($field_value, $entity_values) {
    $widget_id = $field_value['widget_id'] ?? NULL;
    $widget_data = $field_value['widget_data'] ?? NULL;
    if (!isset($widget_id)) {
      return NULL;
    }
    $settings = $this->widgetsManager->loadSettings($widget_id);
    if (isset($settings['extra_fields'])) {
      foreach ($settings['extra_fields'] as $name => $field) {
        $df_field_value = $widget_data['extra_field'][$name] ?? NULL;
        if (!empty($df_field_value)) {
          $df_field_value = $this->denormalizeDynamicFieldValue($df_field_value, $field, $entity_values);
        }
        $widget_data['extra_field'][$name] = $df_field_value;
      }
    }
    if (isset($settings['fields'])) {
      foreach ($settings['fields'] as $name => $field) {
        foreach ($widget_data as $key => $value) {
          if ($key === 'extra_field') {
            continue;
          }
          $df_field_value = $value[$name] ?? NULL;
          if ($df_field_value) {
            $df_field_value = $this->denormalizeDynamicFieldValue($df_field_value, $field, $entity_values);
            $widget_data[$key][$name] = $df_field_value;
          }
        }
      }
    }
    $field_value['widget_data'] = $widget_data;
    return $field_value;
  }

  /**
   * Normalize dynnamic field value.
   */
  public function normalizeDynamicFieldValue($df_field_value, $field, $entity_values = []) {
    if (!isset($field['type']) && isset($field['g_title'])) {
      $field_value = [];
      foreach ($field as $field_name => $field_info) {
        if ($field_name === 'g_title') {
          continue;
        }
        $field_value[$field_name] = $this->normalizeDynamicFieldValue($df_field_value[$field_name], $field_info, $entity_values);
      }
      $df_field_value = $field_value;
    }
    if (!isset($field['type']) && !isset($field['g_title'])) {
      return $df_field_value;
    }
    if ($field['type'] === 'date') {
      $df_field_value = $this->dateFromFormatToFormat('Y-m-d', 'd/m/Y', $df_field_value, $entity_values);
    }
    if ($field['type'] === 'entity_autocomplete') {
      $target_type = $field['options']['#target_type'] ?? NULL;
      if ($target_type && !empty($target_type)) {
        if ($target_type === 'node') {
          $df_field_value = !empty($df_field_value) ? $df_field_value : NULL;
          if (!empty($df_field_value)) {
            $node = $this->entityTypeManager->getStorage('node')
              ->load($df_field_value);
            if (!empty($node)) {
              $node_id = $node->get('node_id')->value;
              $df_field_value = $node_id ?? $node->id();
            }
          }
        }
        elseif ($target_type === 'taxonomy_term') {
          $df_field_value = !empty($df_field_value) ? $df_field_value : NULL;
          if (!empty($df_field_value)) {
            $term = $this->entityTypeManager->getStorage('taxonomy_term')
              ->load($df_field_value);
            if (!empty($term)) {
              $term_id = $term->get('term_id')->value;
              $df_field_value = $term_id ?? $term->id();
            }
          }
        }
      }
    }
    if (in_array($field['type'], array_keys(ContentPackageManagerInterface::MEDIA_FIELD_NAMES))) {
      $df_field_value = $this->normalizeDfMedia($df_field_value, $field['type']);
    }

    return $df_field_value;
  }

  /**
   * Denormalize dynamic field value.
   */
  public function denormalizeDynamicFieldValue($df_field_value, $field, $entity_values = []) {
    if (!isset($field['type']) && isset($field['g_title'])) {
      $field_value = [];
      foreach ($field as $field_name => $field_info) {
        if ($field_name === 'g_title') {
          continue;
        }
        $field_value[$field_name] = $this->denormalizeDynamicFieldValue($df_field_value[$field_name], $field_info, $entity_values);
      }
      $df_field_value = $field_value;
    }
    if (!isset($field['type']) && !isset($field['g_title'])) {
      return $df_field_value;
    }
    if (isset($field['type'])) {
      if ($field['type'] === 'date') {
        $df_field_value = $this->dateFromFormatToFormat('d/m/Y', 'Y-m-d', $df_field_value, $entity_values);
      }
      if ($field['type'] === 'entity_autocomplete') {
        $target_type = $field['options']['#target_type'] ?? NULL;
        if ($target_type && !empty($target_type)) {
          if ($target_type === 'node') {
            $df_field_value = !empty($df_field_value) ? $df_field_value : NULL;
            if (!empty($df_field_value)) {
              $nodes = $this->entityTypeManager->getStorage('node')
                ->loadByProperties([
                  'node_id' => $df_field_value,
                ]);
              $node = reset($nodes);
              if (!empty($node)) {
                $df_field_value = $node->id();
              }
            }
          }
          elseif ($target_type === 'taxonomy_term') {
            $df_field_value = !empty($df_field_value) ? $df_field_value : [];
            if (!empty($df_field_value)) {
              $terms = $this->entityTypeManager->getStorage('taxonomy_term')
                ->loadByProperties([
                  'term_id' => $df_field_value,
                ]);
              $term = reset($terms);
              if (!empty($term)) {
                $df_field_value = $term->id();
              }
            }
          }
        }
      }
      if (in_array($field['type'], array_keys(ContentPackageManagerInterface::MEDIA_FIELD_NAMES))) {
        $df_field_value = $this->denormalizeDfMedia($df_field_value, $field['type']);
      }
    }
    return $df_field_value;
  }

  /**
   * Denormalize given entity.
   */
  public function denormalize(array $entity_values): array {
    $values = [];
    $entity_type = $entity_values['entity_type'] ?? NULL;
    unset($entity_values['entity_type']);

    $typeValue = $entity_values['type'] ?? NULL;
    if (is_array($typeValue)) {
      $bundle = isset($typeValue[0]['target_id']) ? $typeValue[0]['target_id'] : NULL;
    } elseif (is_string($typeValue)) {
      $bundle = $typeValue;
    } else {
      $bundle = NULL;
    }
    if (empty($entity_type) || empty($bundle)) {
      return $values;
    }

    if ($entity_type === 'block_content') {
      $values['type'] = $bundle;
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
            $values[$field_name] = $this->getTimestamp('d/m/Y H:i', $field_value, $entity_values);
          }
          if (is_array($field_value)) {
            foreach ($field_value as &$v) {
              $v = $this->getTimestamp('d/m/Y H:i', $v, $entity_values);
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
          $field_value = $this->denormalizeFieldWysiwygDynamic($field_value, $entity_values);
          if ($field_value) {
            $field_value['widget_data'] = Json::encode($field_value['widget_data']);
            $values[$field_name] = [$field_value];
          }
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
  protected function getTimestamp($format, $dateString, $entity_values = []) {
    $dateTime = \DateTime::createFromFormat($format, $dateString);
    if ($dateTime) {
      return $dateTime->getTimestamp();
    }
    throw new \Exception('Invalid datetime format, format must be ' . $format . ' "' . $dateString . '" given.' . Json::encode($entity_values));
  }

  /**
   * Get timestamp from date string.
   */
  protected function dateFromFormatToFormat($from_format, $to_format, $dateString, $entity_values = []) {
    $dateTime = \DateTime::createFromFormat($from_format, $dateString);
    if ($dateTime) {
      return $dateTime->format($to_format);
    }

    throw new \Exception('Invalid datetime format, format must be from ' . $from_format . ' to ' . $to_format . '"' . $dateString . '" given.<br>' . Json::encode($entity_values));
  }

  /**
   * Normalize dynamic field media.
   */
  protected function normalizeDfMedia($df_field_value, $media_type = 'image') {
    if (!empty($df_field_value)) {
      $image_data = reset($df_field_value);
      $mid = $image_data['selection'][0]['target_id'] ?? NULL;
      $df_field_value = '';
      if ($mid) {
        $media = Media::load($mid);
        if ($media) {
          $media_field_name = ContentPackageManagerInterface::MEDIA_FIELD_NAMES[$media_type];
          if ($media_type !== 'remote_video') {
            $fid = $media->get($media_field_name)->target_id;
            if ($fid) {
              $file = File::load($fid);
              if ($file) {
                $df_field_value = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
              }
            }
          }
          else {
            $df_field_value = $media->get($media_field_name)->value;
          }
        }
      }
    }
    return $df_field_value;
  }

  /**
   * Denormalize dynamic field media.
   */
  protected function denormalizeDfMedia($df_field_value, $media_type = 'image') {
    if (!empty($df_field_value)) {
      $mid = $this->generateMediaFromUrl($df_field_value, $media_type);
      $df_field_value = [];
      if ($mid) {
        $df_field_value = [
          uniqid() => [
            'selection' => [
              [
                'target_id' => $mid,
              ],
            ],
          ],
        ];
      }
    }
    return $df_field_value;
  }

  /**
   * Generate media from the given url.
   */
  public function generateMediaFromUrl(string $url, string $type): ?int {
    $media = NULL;
    switch ($type) {
      case 'remote_video':
        $media = $this->entityTypeManager->getStorage('media')->create([
          'bundle' => $type,
          'uid' => '1',
          self::MEDIA_FIELD_NAMES[$type] => $url,
        ]);
        break;

      default:
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
