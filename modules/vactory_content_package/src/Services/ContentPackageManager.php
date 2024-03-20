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
use Drupal\Core\Entity\EntityRepositoryInterface;

/**
 * Content package manager service.
 */
class ContentPackageManager implements ContentPackageManagerInterface
{

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
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * {@inheritDoc}
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileUrlGeneratorInterface $fileUrlGenerator,
    EntityFieldManagerInterface $entityFieldManager,
    WidgetsManager $widgetsManager,
    EntityRepositoryInterface $entityRepository
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileUrlGenerator = $fileUrlGenerator;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityFieldManager = $entityFieldManager;
    $this->widgetsManager = $widgetsManager;
    $this->entityRepository = $entityRepository;
  }

  /**
   * Normalize given entity.
   */
  public function normalize(EntityInterface $entity, $entity_translation = FALSE): array
  {

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
                $paragraph = $this->entityRepository->getTranslationFromContext($paragraph, $entity->language()->getId());
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
          $field_value = !$is_multiple ? date('d/m/Y H:i', $field_value[0]['value']) : array_map(fn ($value) => date('d/m/Y H:i', $value['value']), $field_value);
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
                        // If the image is not loading on localhost, you can use an online image link like "https://hips.hearstapps.com/hmg-prod/images/nature-quotes-landscape-1648265299.jpg"
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
          $field_value = [
            'alias' => $entity->path->alias,
            'pathauto' => $entity->path->pathauto ?? 1,
          ];
        }

        if ($field_type === 'field_wysiwyg_dynamic' && !empty($field_value)) {
          // DF field type.

          \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("1 Test Field: %s, Type: %s, Value: %s, entity_values: %s", $field_name, $field_type, json_encode($field_value), json_encode($entity_values)));

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
  public function normalizeFieldWysiwygDynamic($field_value, $entity_values)
  {
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
  public function denormalizeFieldWysiwygDynamic($field_value, $entity_values)
  {
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
  public function normalizeDynamicFieldValue($df_field_value, $field, $entity_values = [])
  {
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
        } elseif ($target_type === 'taxonomy_term') {
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
  public function denormalizeDynamicFieldValue($df_field_value, $field, $entity_values = [])
  {
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
          } elseif ($target_type === 'taxonomy_term') {
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
  public function denormalize(array $entity_values): array
  {
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
              $users_ids = array_map(fn ($id) => ['target_id' => $id], $users_ids);
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
              $terms_ids = array_map(fn ($id) => ['target_id' => $id], $terms_ids);
              $values[$field_name] = $terms_ids;
            }
          }

          // Entity type reference field.
          if ($field_name === 'type' && isset($field_settings['target_type']) && in_array($field_settings['target_type'], ContentPackageManagerInterface::ENTITY_TYPES_KEYS) && !empty($field_value)) {
            $field_value = is_array($field_value) ? $field_value : [$field_value];
            $values[$field_name] = array_map(fn ($id) => ['target_id' => $id], $field_value);
          }
        }
        if ($field_type === 'colorapi_color_field' && !empty($field_value)) {
          $field_value = is_array($field_value) ? $field_value : [$field_value];
          $field_value = array_map(fn ($el) => ['color' => $el], $field_value);
          $values[$field_name] = $field_value;
        }
        if ($field_type === 'path' && !empty($field_value)) {
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
  // todo: solition two
  // todo: solition V1
  /**
   * Normalize a menu link entity.
   *
   * @param \Drupal\menu_link_content\Entity\MenuLinkContent $menu_link
   *   The menu link entity to normalize.
   * @param bool $is_translation
   *   Indicates if the normalization is for a translation.
   *
   * @return array
   *   The normalized array representation of the menu link.
   */
  // public function normalizeMenuLink(\Drupal\menu_link_content\Entity\MenuLinkContent $menu_link, $is_translation = FALSE)
  // {
  //   \Drupal::logger('vactory_content_package_Archive_manager')->debug(sprintf("10 %s", json_encode($menu_link->toArray())));

  //   // Initialize the normalized array.
  //   $normalized = [
  //     'id' => $menu_link->id(),
  //     'title' => $menu_link->getTitle(),
  //     'url' => $menu_link->getUrlObject()->toString(),
  //     'parent' => $menu_link->getParentId(),
  //     'menu_name' => $menu_link->getMenuName(),
  //     'weight' => $menu_link->getWeight(),
  //     'expanded' => $menu_link->isExpanded(),
  //     'enabled' => $menu_link->isEnabled(),
  //     // Add other properties and fields as needed.
  //   ];

  //   // Retrieve all field definitions for the menu link entity.
  //   $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('menu_link_content', $menu_link->bundle());


  //   // foreach ($entity_array as $field_name => &$field_value) {
  //   //   $field_definition = $fields[$field_name] ?? NULL;
  //   //   if ($field_definition) {
  //   //     $field_type = $field_definition->getType();
  //   //     if ($field_type === 'field_wysiwyg_dynamic' && !empty($field_value)) {

  //   foreach ($fields as $field_name => $field_definition) {
  //     // Check if the field is of type 'field_wysiwyg_dynamic'.
  //     if ($field_definition->getType() === 'field_wysiwyg_dynamic') {
  //       \Drupal::logger('vactory_content_package_Archive_manager')->debug(sprintf("10 the w"));

  //       // if ($menu_link->hasField($field_name) && !$menu_link->get($field_name)->isEmpty()) {
  //       //   $field_value = $menu_link->get($field_name)->getValue();
  //       \Drupal::logger('vactory_content_package_Archive_manager')->debug(sprintf("Field %s is not empty and contains: %s", $field_name, json_encode($field_definition)));
  //       // } else {
  //       //   \Drupal::logger('vactory_content_package_Archive_manager')->debug(sprintf("Field %s is empty or does not exist on this entity.", $field_name));
  //       // }

  //       // Assume $menu_link->field_wysiwyg_dynamic exists and has a method getValue().
  //       $field_value = $menu_link->$field_name->getValue();
  //       $field_value = $menu_link->$field_name->getValue();
  //       \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("1 Test Field: %s, Value: %s", $field_name, json_encode($field_value)));

  //       // Assume normalizeFieldWysiwygDynamic() is a method to normalize this field's complex structure.
  //       $normalized[$field_name] = $this->normalizeFieldWysiwygDynamic($field_definition, $field_value);
  //     }
  //   }

  //   // Handle translations if this is not a translation and the menu link has translation languages.
  //   if (!$is_translation && $menu_link->hasTranslationLanguages()) {
  //     $normalized['translations'] = [];
  //     foreach ($menu_link->getTranslationLanguages(FALSE) as $langcode => $language) {
  //       if ($menu_link->hasTranslation($langcode)) {
  //         $translation = $menu_link->getTranslation($langcode);
  //         // Recursively normalize translations, marking them as translations.
  //         $normalized['translations'][$langcode] = $this->normalizeMenuLink($translation, TRUE);
  //       }
  //     }
  //   }

  //   return $normalized;
  // }

  // todo: V2
  // this is work
  // public function normalizeMenuLink(\Drupal\menu_link_content\Entity\MenuLinkContent $menu_link, $is_translation = FALSE)
  // {
  //   // Initialize the normalized data structure.
  //   $normalized = [
  //     'id' => $menu_link->id(),
  //     'title' => $menu_link->getTitle(),
  //     'url' => $menu_link->getUrlObject()->toString(),
  //     'parent' => $menu_link->getParentId(),
  //     'menu_name' => $menu_link->getMenuName(),
  //     'weight' => $menu_link->getWeight(),
  //     'expanded' => $menu_link->isExpanded(),
  //     'enabled' => $menu_link->isEnabled(),
  //   ];

  //   // Load the full menu link entity to access all fields.
  //   $menu_link_full = \Drupal::entityTypeManager()->getStorage('menu_link_content')->load($menu_link->id());

  //   // Get field definitions for the menu link entity type and bundle.
  //   $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('menu_link_content', $menu_link_full->bundle());

  //   // Iterate through all defined fields to process them.
  //   foreach ($fields as $field_name => $field_definition) {
  //     if ($menu_link_full->hasField($field_name) && !$menu_link_full->get($field_name)->isEmpty()) {
  //       $field_type = $field_definition->getType();

  //       // Handle specific field types uniquely, such as 'field_wysiwyg_dynamic'.
  //       if ($field_type === 'field_wysiwyg_dynamic') {
  //         $field_value = $menu_link_full->get($field_name)->getValue();
  //         \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("Field: %s, Value: %s", $field_name, json_encode($field_value)));

  //         // Normalize and assign the dynamic field value.
  //         $normalized[$field_name] = $this->normalizeFieldWysiwygDynamic($field_value, []);
  //       } else {
  //         // For other field types, directly assign their values. Adapt as needed.
  //         $normalized[$field_name] = $menu_link_full->get($field_name)->getValue();
  //       }
  //     }
  //   }

  //   return $normalized;
  // }

  //todo: V3

  // public function normalizeMenuLink(\Drupal\menu_link_content\Entity\MenuLinkContent $menu_link, $is_translation = FALSE)
  // {

  //   \Drupal::logger('vactory_content_package_Archive_manager')->debug(sprintf("menu_link %s", json_encode($menu_link->toArray())));

  //   \Drupal::logger('vactory_content_package_extractLinksInfo')->debug(sprintf("title: %s", $menu_link->getUrlObject()->toString()));

  //   // Initialize the normalized data structure.
  //   $normalized = [
  //     'id' => $menu_link->id(),
  //     'title' => $menu_link->getTitle(),
  //     'url' => $menu_link->getUrlObject()->toString(),
  //     'parent' => $menu_link->getParentId(),
  //     'menu_name' => $menu_link->getMenuName(),
  //     'weight' => $menu_link->getWeight(),
  //     'expanded' => $menu_link->isExpanded(),
  //     'enabled' => $menu_link->isEnabled(),
  //     // Prepare to collect translations if any
  //     'translations' => [],
  //   ];

  //   // Load the full menu link entity to access all fields, ensuring all translations are loaded.
  //   $menu_link_full = \Drupal::entityTypeManager()->getStorage('menu_link_content')->load($menu_link->id());

  //   // Ensure we are working with a translatable menu link.
  //   if ($menu_link_full instanceof \Drupal\Core\Entity\TranslatableInterface) {
  //     // Get field definitions for the menu link entity type and bundle.
  //     $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('menu_link_content', $menu_link_full->bundle());

  //     // Check if there are translations and normalize them.
  //     if (!$is_translation) {
  //       foreach ($menu_link_full->getTranslationLanguages(false) as $langcode => $language) {
  //         if ($menu_link_full->hasTranslation($langcode)) {
  //           $translation = $menu_link_full->getTranslation($langcode);
  //           // Recursively call normalizeMenuLink for each translation.
  //           $normalized['translations'][$langcode] = $this->normalizeMenuLink($translation, TRUE);
  //         }
  //       }
  //     }

  //     // Iterate through all defined fields to process them, for the original and each translation.
  //     foreach ($fields as $field_name => $field_definition) {
  //       if ($menu_link_full->hasField($field_name) && !$menu_link_full->get($field_name)->isEmpty()) {
  //         $field_type = $field_definition->getType();
  //         $field_value = $menu_link_full->get($field_name)->getValue();
  //         // Normalize field based on type, if necessary.
  //         if ($field_type === 'field_wysiwyg_dynamic') {
  //           $normalized[$field_name] = $this->normalizeFieldWysiwygDynamic($field_value, []);
  //         } else {
  //           $normalized[$field_name] = $field_value;
  //         }
  //       }
  //     }
  //   }

  //   return $normalized;
  // }

  // public function normalizeMenuLink($menu_link, $is_translation = FALSE)
  // {
  //   // Ensure URLs are language-aware.
  //   $language_manager = \Drupal::languageManager();
  //   $langcode = $menu_link->language()->getId();
  //   $url_options = ['language' => $language_manager->getLanguage($langcode)];
  //   $url = $menu_link->toUrl('canonical', $url_options)->toString();

  //   // Basic link properties.
  //   $normalized = [
  //     'id' => $menu_link->id(),
  //     'title' => $menu_link->label(), // Use label() for the translated title.
  //     'url' => $url,
  //     // Add other basic properties as necessary...
  //     'translations' => [], // Prepare to collect translations if any.
  //   ];

  //   // Load the full menu link entity to access all fields, ensuring all translations are loaded.
  //   $menu_link_full = \Drupal::entityTypeManager()->getStorage('menu_link_content')->load($menu_link->id());

  //   // Handle translations if this is not a translation and the entity is translatable.
  //   if (!$is_translation && $menu_link_full instanceof \Drupal\Core\Entity\TranslatableInterface && $menu_link_full->isTranslatable()) {
  //     foreach ($menu_link_full->getTranslationLanguages(false) as $langcode => $language) {
  //       if ($menu_link_full->hasTranslation($langcode)) {
  //         $translation = $menu_link_full->getTranslation($langcode);
  //         // Recursively call normalizeMenuLink for each translation.
  //         $normalized['translations'][$langcode] = $this->normalizeMenuLink($translation, TRUE);
  //       }
  //     }
  //   }

  //   // Normalize fields, especially dynamic ones.
  //   $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('menu_link_content', $menu_link_full->bundle());
  //   foreach ($fields as $field_name => $field_definition) {
  //     if ($menu_link_full->hasField($field_name) && !$menu_link_full->get($field_name)->isEmpty()) {
  //       $field_value = $menu_link_full->get($field_name)->getValue();

  //       // Normalize 'field_wysiwyg_dynamic' fields specifically, or adapt for other custom fields.
  //       if ($field_definition->getType() === 'field_wysiwyg_dynamic') {
  //         $normalized[$field_name] = $this->normalizeFieldWysiwygDynamic($field_value, []);
  //       } else {
  //         // Direct assignment for other field types; adapt as needed.
  //         $normalized[$field_name] = $field_value;
  //       }
  //     }
  //   }

  //   return $normalized;
  // }


  /**
   * Denormalize menu data.
   */
  public function denormalizeMenu(array $menuData): array
  {
    // Convert the menuData array to a JSON string for logging
    $jsonContent = json_encode($menuData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    \Drupal::logger('vactory_content_package')->debug('MenuData Content of JSON file: @content', ['@content' => $jsonContent]);

    // Ensure $menuData is always an array of menus
    if (isset($menuData['menu_name'])) {
      $menuData = [$menuData];
    }

    // Initialize normalizedMenu array to collect all menus
    $normalizedMenus = [];

    foreach ($menuData as $menu) {
      $normalizedMenu = [
        'menu_name' => $menu['menu_name'] ?? '',
        'menu_system_name' => $menu['menu_system_name'] ?? '',
        'links' => [],
      ];

      // Check if 'links' exists and is an array
      if (isset($menu['links']) && is_array($menu['links'])) {
        foreach ($menu['links'] as $link) {
          $normalizedLink = $this->denormalizeMenuLink($link);
          $normalizedMenu['links'][] = $normalizedLink;
        }
      }

      // Add normalized menu to the collection of menus
      $normalizedMenus[] = $normalizedMenu;
    }

    \Drupal::logger('vactory_content_package')->debug('normalizedMenus Content of JSON file: @normalizedMenus', [
      '@normalizedMenus' => json_encode($normalizedMenus, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
    ]);
    return $normalizedMenus;
  }

  /**
   * Helper function to denormalize a single menu link.
   */
  protected function denormalizeMenuLink(array $linkData): array
  {
    $normalizedLink = [
      'title' => $linkData['title'] ?? '',
      'url' => $linkData['url'] ?? '',
      'hasChildren' => $linkData['hasChildren'] ?? false,
      'menu_name' => $linkData['menu_name'] ?? '',
      'translations' => $linkData['translations'] ?? [],
      'children' => [],
    ];

    // Recursively process children if present
    if (!empty($linkData['children']) && is_array($linkData['children'])) {
      foreach ($linkData['children'] as $child) {
        $normalizedChild = $this->denormalizeMenuLink($child);
        $normalizedLink['children'][] = $normalizedChild;
      }
    }

    return $normalizedLink;
  }


  /**
   * Get field value depending on its cardinality.
   */
  protected function getFieldValue($fieldValue, $isMultiple = FALSE, $arrayFormat = FALSE, $key = 'value')
  {
    $value = !$isMultiple ? $fieldValue[0][$key] : array_map(fn ($value) => $value[$key], $fieldValue);
    return $arrayFormat && !is_array($value) ? [$value] : $value;
  }

  /**
   * Get timestamp from date string.
   */
  protected function getTimestamp($format, $dateString, $entity_values = [])
  {
    $dateTime = \DateTime::createFromFormat($format, $dateString);
    if ($dateTime) {
      return $dateTime->getTimestamp();
    }
    throw new \Exception('Invalid datetime format, format must be ' . $format . ' "' . $dateString . '" given.' . Json::encode($entity_values));
  }

  /**
   * Get timestamp from date string.
   */
  protected function dateFromFormatToFormat($from_format, $to_format, $dateString, $entity_values = [])
  {
    $dateTime = \DateTime::createFromFormat($from_format, $dateString);
    if ($dateTime) {
      return $dateTime->format($to_format);
    }

    throw new \Exception('Invalid datetime format, format must be from ' . $from_format . ' to ' . $to_format . '"' . $dateString . '" given.<br>' . Json::encode($entity_values));
  }

  /**
   * Normalize dynamic field media.
   */
  protected function normalizeDfMedia($df_field_value, $media_type = 'image')
  {
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
                // If the image is not loading on localhost, you can use an online image link like "https://hips.hearstapps.com/hmg-prod/images/nature-quotes-landscape-1648265299.jpg"
                $df_field_value = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
              }
            }
          } else {
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
  protected function denormalizeDfMedia($df_field_value, $media_type = 'image')
  {
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
  public function generateMediaFromUrl(string $url, string $type): ?int
  {
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
