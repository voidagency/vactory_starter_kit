<?php

namespace Drupal\vactory_dynamic_import\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Dynamic import helpers service class.
 */
class DynamicImportHelpers {

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Service constructor.
   */
  public function __construct(EntityFieldManager $entityFieldManager, LanguageManagerInterface $languageManager, EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityFieldManager = $entityFieldManager;
    $this->languageManager = $languageManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->configFactory = $configFactory;
  }

  /**
   * Transform array to csv file.
   */
  public function generateCsv($header, $data, $filename, $delimiter = NULL) {
    $time = time();
    $delimiter = is_null($delimiter) ? $this->configFactory->get('vactory_migrate.settings')->get('delimiter') : $delimiter;
    $destination = 'public://dynamic-import-model';
    if (!file_exists($destination)) {
      mkdir($destination, 0777);
    }
    $path = "{$destination}/{$filename}-{$time}.csv";
    $fp = fopen($path, 'w');
    fputcsv($fp, $header, $delimiter);
    // Loop through file pointer and a line.
    foreach ($data as $item) {
      fputcsv($fp, $item, $delimiter);
    }

    fclose($fp);
    return $path;
  }

  /**
   * Get field of a given entity and bundle.
   */
  public function getRelatedFields($entity_type, $bundle, $only_keys = FALSE) {

    $excluded_fields = [
      'revision_',
      'field_content_access',
      'vcc_',
      'internal_',
      'notification_',
      'mail_',
      'comment',
      'field_vactory_meta_tags',
      'field_vactory_seo_status',
    ];

    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    $fields = [];
    foreach ($field_definitions as $field_name => $field_definition) {

      if (!$this->startsWithAnyInArray($field_name, $excluded_fields)) {
        $field_storage = FieldStorageConfig::loadByName($entity_type, $field_name);

        $field_label = !empty($field_definition->getLabel()) ? $field_definition->getLabel() : $field_name;
        $field_type = !empty($field_definition->getType()) ? $field_definition->getType() : '';

        if ($field_type == 'datetime') {
          $field_type = 'date';
        }
        if ($field_type == 'entity_reference') {
          $settings = $field_definition->getSettings();
          if (isset($settings['target_type']) && $settings['target_type'] == 'media') {
            if (isset($settings['handler_settings']['target_bundles']) && !is_null($settings['handler_settings']['target_bundles'])) {
              $field_type = 'media:' . reset($settings['handler_settings']['target_bundles']);
            }
          }
          if (isset($settings['target_type']) && $settings['target_type'] == 'taxonomy_term') {
            $field_type = 'taxonomy_term';
          }
        }
        if (!$field_storage) {
          $fields[$field_name] = $only_keys ? $field_name : [
            'label' => $field_label,
            'type' => $field_type,
          ];
          continue;
        }

        $field_properties = $field_storage->getPropertyDefinitions();
        if (count($field_properties) === 1 || isset($field_properties['target_id'])) {
          $fields[$field_name] = $only_keys ? $field_name : [
            'label' => $field_label,
            'type' => $field_type,
          ];
          continue;
        }
        foreach ($field_properties as $key => $field_property) {
          $fields["{$field_name}/{$key}"] = $only_keys ? "{$field_name}/{$key}" : [
            'label' => "{$field_label}/{$key}",
            'type' => $field_type,
          ];
        }
      }
    }
    return $fields;
  }

  /**
   * Check if array contains a value that starts with haystack.
   */
  private function startsWithAnyInArray($haystack, $array) {
    foreach ($array as $prefix) {
      if (strpos($haystack, $prefix) === 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Generates csv model based on dynamic import configuration.
   */
  public function generateCsvModel($target_entity, $target_bundle, $fields, $is_translation, $delimiter = NULL, $return_array = FALSE) {

    $header = [
      'id',
    ];

    if ($is_translation) {
      $header[] = 'original';
    }

    $fields = array_filter($fields, function ($item) {
      return $item != 0;
    });

    $otiginal_fields = $this->getRelatedFields($target_entity, $target_bundle);

    foreach ($fields as $field) {
      $formatted_field = str_replace('/', ':', $field);
      $original = $otiginal_fields[$field];

      if ($original['type'] == 'date') {
        $header[] = 'date|' . $formatted_field . '|Y-m-d';
      }
      elseif ($original['type'] == 'file') {
        $header[] = 'file|' . $formatted_field . '|-';
      }
      elseif ($original['type'] == 'taxonomy_term') {
        $header[] = 'term|' . $formatted_field . '|+';
      }
      elseif (str_starts_with($original['type'], 'media')) {
        $bundle = explode(':', $original['type']);
        $header[] = 'media|' . $formatted_field . '|' . $bundle[1];
        // Add a field for images alt.
        if ($bundle[1] == 'image') {
          $header[] = 'media|' . $formatted_field . '|' . $bundle[1] . '_alt';
        }
      }
      elseif ($original['type'] == 'text_with_summary') {
        $header[] = 'wysiwyg|' . $formatted_field . '|full_html';
      }
      else {
        $header[] = '-|' . $formatted_field . '|-';
      }
    }

    if ($return_array) {
      return $header;
    }
    $path = $this->generateCsv($header, [], "{$target_entity}-{$target_bundle}", $delimiter);

    $response = new BinaryFileResponse(\Drupal::service('file_system')
      ->realPath($path), 200, [], FALSE);
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, "{$target_entity}-{$target_bundle}" . '.csv');
    $response->deleteFileAfterSend(TRUE);
    $response->send();
  }

  /**
   * Generates migration config based on dynamic import config.
   */
  public function generateMigrationConfig($id, $label, $header, $target_entity, $target_bundle, $is_translation, $translation_langcode, $delimiter = NULL, $group = NULL) {
    $data = [];
    $data['id'] = $id;
    $data['label'] = $label;

    $group = is_null($group) ? $this->configFactory->get('vactory_migrate.settings')->get('group') : $group;
    $data['migration_group'] = $group;

    $delimiter = is_null($delimiter) ? $this->configFactory->get('vactory_migrate.settings')->get('delimiter') : $delimiter;
    $data['source'] = [
      'plugin'           => 'csv',
      'header_row_count' => 1,
      'ids'              => ['id'],
      'delimiter'        => $delimiter,
      'path'             => '',
      'constants'        => [
        'dest_path' => "public://migrated-{$target_bundle}/",
      ],
    ];

    $data['destination'] = [
      'plugin'         => 'entity:' . $target_entity,
      'default_bundle' => $target_bundle,
    ];

    if ($is_translation) {
      $data['destination']['translations'] = 'true';
    }

    $data['process'] = [];

    if ($translation_langcode != $this->languageManager->getDefaultLanguage()->getId()) {
      $data['process']['langcode'] = [
        'plugin'        => 'default_value',
        'default_value' => $translation_langcode,
      ];
    }

    if ($is_translation) {
      $data['process']['content_translation_source'] = [
        'plugin'        => 'default_value',
        'default_value' => $this->languageManager->getDefaultLanguage()
          ->getId(),
      ];
      $data['process']['default_langcode'] = [
        'plugin'        => 'default_value',
        'default_value' => 0,
      ];
      $entity_type_definition = $this->entityTypeManager->getDefinition($target_entity);
      $id_field = $entity_type_definition->getKey('id');

      $data['process'][$id_field] = 'original';
    }

    foreach ($header as $field) {
      if ($field == 'id') {
        $data['process']['legacy_id'] = $field;
      }
      else {
        $config = $field ? explode('|', $field) : [];
        if (is_array($config) && count($config) == 3) {
          $plugin = $config[0];
          $mapped_field = str_replace(':', '/', $config[1]);
          $info = $config[2];
          if ($plugin == '-' && $info == '-') {
            if ($target_entity == 'node' && $mapped_field == 'path') {
              $data['process']['path/alias'] = $field;
              $data['process']['path/pathauto'] = [
                'plugin' => 'default_value',
                'default_value' => 0,
              ];
            }
            else {
              $data['process'][$mapped_field] = $field;
            }
          }
          else {
            if ($plugin == 'date') {
              $data['process'][$mapped_field] = [
                'plugin'      => 'format_date',
                'source'      => $field,
                'from_format' => $info,
                'to_format'   => 'Y-m-d',
              ];
            }
            if ($plugin == 'media') {
              if ($info == 'remote_video') {
                $data['process'][$mapped_field] = [
                  'plugin' => 'remote_video_import',
                  'source' => $field,
                ];
              }
              elseif ($info !== 'image_alt') {
                $data['process'][$mapped_field] = [
                  'plugin' => 'media_import',
                  'destination' => 'constants/dest_path',
                  'media_bundle' => $info,
                  'media_field_name' => 'field_media_' . $info,
                  'source' => $field,
                  'skip_on_error'    => 'true',
                ];
                if ($info == 'image') {
                  $data['process'][$mapped_field]['alt_field'] = $field . '_alt';
                }
              }
            }
            if ($plugin == 'file') {
              $data['process'][$mapped_field] = [
                'plugin'        => 'file_import',
                'destination'   => 'constants/dest_path',
                'source'        => $field,
                'skip_on_error' => 'true',
              ];
            }
            if ($plugin == 'term') {
              $term_target_bundle = $this->getTermTargetBundle($target_entity, $target_bundle, $mapped_field);
              if (!$term_target_bundle['status']) {
                continue;
              }
              else {
                if ($info == 'id') {
                  $data['process'][$mapped_field] = [
                    'plugin' => 'dynamic_term_import',
                    'bundle' => $term_target_bundle['value'],
                    'source' => $field,
                  ];
                }
                if ($info == '-') {
                  $data['process'][$mapped_field] = [
                    [
                      'plugin'    => 'explode',
                      'delimiter' => '|',
                      'source'    => $field,
                    ],
                    [
                      'plugin'      => 'entity_lookup',
                      'value_key'   => 'name',
                      'bundle_key'  => 'vid',
                      'bundle'      => $term_target_bundle['value'],
                      'entity_type' => 'taxonomy_term',
                    ],
                  ];
                }
                if ($info == '+') {
                  $data['process'][$mapped_field] = [
                    [
                      'plugin'    => 'explode',
                      'delimiter' => '|',
                      'source'    => $field,
                    ],
                    [
                      'plugin'      => 'entity_generate',
                      'value_key'   => 'name',
                      'bundle_key'  => 'vid',
                      'bundle'      => $term_target_bundle['value'],
                      'entity_type' => 'taxonomy_term',
                    ],
                  ];
                }
              }
            }
            if ($plugin == 'wysiwyg') {
              $split_field = explode('/', $mapped_field);
              if (end($split_field) === 'format') {
                continue;
              }
              $data['process'][$mapped_field] = $field;
              $data['process'][$split_field[0] . '/format'] = [
                'plugin' => 'default_value',
                'default_value' => $info,
              ];
            }
          }
        }
      }
    }
    return $data;
  }

  /**
   * Get Term Target Bundle By Field.
   */
  public function getTermTargetBundle($entity_type, $bundle, $field) {
    $splitted = $field ? explode('/', $field) : [];
    $field = $splitted[0] ?? '';
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    foreach ($field_definitions as $field_name => $field_definition) {
      if ($field_name == $field) {
        $settings = $field_definition->getSettings();
        if ($settings['target_type'] !== 'taxonomy_term') {
          return [
            'status' => FALSE,
            'value'  => "{$field_name} configuration is not correct",
          ];
        }
        $target_bundle = $settings['handler_settings']['target_bundles'];
        return [
          'status' => TRUE,
          'value'  => reset($target_bundle),
        ];
      }
    }
  }

}
