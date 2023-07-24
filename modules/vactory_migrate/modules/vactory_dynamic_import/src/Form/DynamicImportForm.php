<?php

namespace Drupal\vactory_dynamic_import\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Class DynamicImportForm.
 *
 * Provides a form to import data dynamically.
 */
class DynamicImportForm extends FormBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity Field Manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Submitted values.
   *
   * @var array
   */
  protected $submitted = [];

  /**
   * Language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->entityTypeBundleInfo = $container->get('entity_type.bundle.info');
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->languageManager = $container->get('language_manager');
    return $instance;
  }

  /**
   * {@inheritDoc}
   */
  public function getFormId() {
    return 'dynamic_import_form';
  }

  /**
   * {@inheritDoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $url_options = ['absolute' => TRUE];
    $t_args = [
      ':settings_url' => Url::fromUri('base:/admin/structure/file-types/manage/document/edit', $url_options)
        ->toString(),
    ];
    $message = $this->t('If you\'re having trouble uploading the csv file. Add <strong><em>text/csv</em></strong> <a target="_blank" href=":settings_url"> to the allowed <em>MIME types</em></a>.', $t_args);

    $entity_types = $this->entityTypeManager->getDefinitions();
    $entity_types = array_filter($entity_types, fn($entity_type) => $entity_type instanceof ContentEntityType);
    $entity_types = array_map(fn($entity_type) => $entity_type->getLabel(), $entity_types);
    $form['entity_type'] = [
      '#type'         => 'select',
      '#title'        => $this->t('Targeted entity type'),
      '#options'      => $entity_types,
      '#empty_option' => '- Select -',
      '#required'     => TRUE,
      '#ajax'         => [
        'callback' => '::bundlesCallback',
        'wrapper'  => 'bundles-container',
      ],
      '#description'  => $this->t('Select the destination content type'),
    ];

    $form['container'] = [
      '#type'       => 'container',
      '#attributes' => ['id' => 'bundles-container'],
    ];

    if (isset($this->submitted['entity_type']) && !empty($this->submitted['entity_type'])) {
      $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->submitted['entity_type']);
      $bundles = array_map(fn($bundle) => $bundle['label'], $bundles);
      $form['container']['bundle'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Targeted bundle'),
        '#options'      => $bundles,
        '#empty_option' => '- Select -',
        '#required'     => TRUE,
        '#ajax'         => [
          'callback' => '::bundlesCallback',
          'wrapper'  => 'bundles-container',
        ],
        '#description'  => $this->t('Select the targeted bundle'),
      ];

      if (isset($this->submitted['bundle']) && !empty($this->submitted['bundle'])) {

        $form['container']['label'] = [
          '#type'        => 'textfield',
          '#title'       => $this->t('Migration key'),
          '#required'    => TRUE,
          '#description' => $this->t("Enter a unique key to identify the migration performed.<br>
                                    This key is essential for tracking and managing the migration process.<br>
                                    The migration config will be named as 'migrate_plus.migration.[entity]_[bundle]_migration_[key]' for easy tracking "),
        ];

        $groups = $this->entityTypeManager->getStorage('migration_group')
          ->loadMultiple();
        $groups = array_map(fn($group) => $group->label(), $groups);
        $current_path = $current_path = \Drupal::service('path.current')->getPath();
        $link = Url::fromRoute('entity.migration_group.add_form', ['destination' => $current_path])
          ->toString(TRUE)
          ->getGeneratedUrl();
        $form['container']['migration_group'] = [
          '#type' => 'select',
          '#title' => $this->t('Migration group'),
          '#options' => $groups,
          '#empty_option' => '- Select -',
          '#required' => TRUE,
          '#description' => $this->t('Select an existing migration group or <a href="@link">Create new migration group</a>', ['@link' => $link]),
        ];

        $form['container']['delimiter'] = [
          '#type'        => 'textfield',
          '#title'       => $this->t('Delimiter'),
          '#required'    => TRUE,
          '#description' => $this->t('Enter the delimiter used in the CSV file.'),
        ];

        $form['container']['csv'] = [
          '#type'              => 'managed_file',
          '#title'             => $this->t('CSV file'),
          '#name'              => 'csv',
          '#upload_location'   => 'private://migrate-tmp',
          '#upload_validators' => [
            'file_validate_extensions' => ['csv'],
          ],
          '#description'       => t("Load the csv file to import.<br>") . $message,
          '#required'          => TRUE,
        ];

        $form['container']['translation'] = [
          '#type'        => 'checkbox',
          '#title'       => $this->t('This is a translation'),
          '#description' => $this->t("For translations of existing content, please check this checkbox.<br>
                                            Ensures accurate processing and integration of translated data."),
        ];

        $form['container']['language'] = [
          '#type'          => 'language_select',
          '#title'         => $this->t('language'),
          '#default_value' => $this->languageManager->getDefaultLanguage()
            ->getId(),
        ];

        $form['container']['rollback'] = [
          '#type'        => 'checkbox',
          '#title'       => $this->t('Rollback'),
          '#description' => $this->t("Selecting 'Rollback' triggers a reversal of all migrations linked to the specified entity.<br>
                                             <b>Caution:</b> This action undoes changes made by the migrations."),
        ];

        $form['container']['submit'] = [
          '#type'        => 'submit',
          '#value'       => $this->t("Start process"),
          '#button_type' => 'primary',
        ];

      }

    }
    return $form;
  }

  /**
   * Ajax Callback.
   */
  public function bundlesCallback($form, FormStateInterface $form_state) {
    return $form['container'];
  }

  /**
   * {@inheritDoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $data = [];
    $id = "{$values['entity_type']}_{$values['bundle']}_migration_{$values['label']}";

    $fid = (int) reset($values['csv']);
    $file = File::load($fid);
    $file_path = NULL;
    if ($file) {
      $file_path = $file->getFileUri();
    }
    $header = [];
    if ($file_path) {
      $path = \Drupal::service('file_system')->realpath($file_path);
      $header = $this->getCsvHeader($path, $values['delimiter']);
    }

    $data['id'] = $id;
    $data['label'] = "{$values['entity_type']} {$values['bundle']} migration";
    $data['migration_group'] = $values['migration_group'];
    $data['source'] = [
      'plugin'           => 'csv',
      'header_row_count' => 1,
      'ids'              => ['id'],
      'delimiter'        => $values['delimiter'],
      'path'             => $file_path,
      'constants'        => [
        'dest_path' => "public://migrated-{$values['bundle']}/",
      ],
    ];

    $data['destination'] = [
      'plugin'         => 'entity:' . $values['entity_type'],
      'default_bundle' => $values['bundle'],
    ];

    if ($values['translation']) {
      $data['destination']['translations'] = 'true';
    }

    $data['process'] = [];

    if ($values['language'] != $this->languageManager->getDefaultLanguage()->getId()) {
      $data['process']['langcode'] = [
        'plugin'        => 'default_value',
        'default_value' => $values['language'],
      ];
    }

    if ($values['translation']) {
      $data['process']['content_translation_source'] = [
        'plugin'        => 'default_value',
        'default_value' => $this->languageManager->getDefaultLanguage()
          ->getId(),
      ];
      $data['process']['default_langcode'] = [
        'plugin'        => 'default_value',
        'default_value' => 0,
      ];
      $entity_type_definition = $this->entityTypeManager->getDefinition($values['entity_type']);
      $id_field = $entity_type_definition->getKey('id');
      $bundle_field = $entity_type_definition->getKey('bundle');

      $data['process'][$id_field] = [
        'plugin'        => 'translation_legacy_id',
        'entity'        => $values['entity_type'],
        'bundle'        => $values['bundle'],
        'mapping_field' => 'legacy_id',
        'bundle_key'    => $bundle_field,
        'source'        => 'original',
      ];
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
            $data['process'][$mapped_field] = $field;
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
              $data['process'][$mapped_field] = [
                'plugin'           => 'media_import',
                'destination'      => 'constants/dest_path',
                'media_bundle'     => $info,
                'media_field_name' => 'field_media_image',
                'source'           => $field,
                'skip_on_error'    => 'true',
              ];
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
              $target_bundle = $this->getTermTargetBundle($values['entity_type'], $values['bundle'], $mapped_field);
              if (!$target_bundle['status']) {
                $form_state->setErrorByName('csv', $target_bundle['value']);
              }
              else {
                if ($info == 'id') {
                  $data['process'][$mapped_field] = [
                    'plugin' => 'dynamic_term_import',
                    'bundle' => $target_bundle['value'],
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
                      'bundle'      => $target_bundle['value'],
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
                      'bundle'      => $target_bundle['value'],
                      'entity_type' => 'taxonomy_term',
                    ],
                  ];
                }
              }
            }
          }
        }
      }
    }
    $migration_config = \Drupal::configFactory()
      ->getEditable("migrate_plus.migration.{$id}");
    $migration_config->setData($data);
    $migration_config->save();
    drupal_flush_all_caches();

    if ($values['rollback']) {
      $url = Url::fromRoute('vactory_dynamic_import.rollback')
        ->setRouteParameters([
          'migration' => $id,
          'rollback'  => "{$values['entity_type']}_{$values['bundle']}_migration_",
          'delimiter' => $values['delimiter'],
        ]);

      $form_state->setRedirectUrl($url);
    }
    else {
      $url = Url::fromRoute('vactory_dynamic_import.import')
        ->setRouteParameters(['migration' => $id, 'delimiter' => $values['delimiter']]);

      $form_state->setRedirectUrl($url);
    }
  }

  /**
   * Form validation.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $name = $form_state->getTriggeringElement()['#name'];
    $this->submitted[$name] = $form_state->getValue($name);
    $triggeringElement = $form_state->getTriggeringElement();
    if ($triggeringElement['#name'] == 'csv_remove_button') {
      return;
    }
    $csv = $form_state->getValue('csv');
    $entity_type = $form_state->getValue('entity_type');
    $bundle = $form_state->getValue('bundle');
    $delimiter = $form_state->getValue('delimiter');
    $delimiter = $delimiter ? trim($delimiter) : $delimiter;
    $label = $form_state->getValue('label');
    $translation = $form_state->getValue('translation');
    $language = $form_state->getValue('language');
    if (isset($label)) {
      $config_id = "migrate_plus.migration.{$entity_type}_{$bundle}_migration_{$label}";
      $query = \Drupal::database()
        ->select('config', 'c')
        ->condition('c.name', $config_id, '=');

      $count = $query->countQuery()->execute()->fetchField();
      if ($count != 0) {
        $form_state->setErrorByName('label', $this->t('Label already used'));
      }
    }

    if (isset($translation) || isset($language)) {
      if ($translation && $language == $this->languageManager->getDefaultLanguage()->getId()) {
        $form_state->setErrorByName('language', $this->t('Cannot use translation with default language'));
      }
    }

    // Validation de header.
    if (isset($csv)) {
      $fid = (int) reset($csv);
      $file = File::load($fid);
      $file_path = NULL;
      if ($file) {
        $file_path = \Drupal::service('file_system')
          ->realpath($file->getFileUri());
      }
      $header = $this->getCsvHeader($file_path, $delimiter);

      if (!empty($header)) {
        if (!in_array('id', $header)) {
          $form_state->setErrorByName('csv', $this->t("The uploaded CSV file must include an 'id' column for proper processing."));
        }

        if ($translation && !in_array('original', $header)) {
          $form_state->setErrorByName('csv', $this->t("The uploaded CSV file must include an 'original' column for migrating translations."));
        }

        $check_content = $this->isValidCsvContent($file_path, $delimiter, count($header));
        if (!$check_content['status']) {
          $form_state->setErrorByName('csv', $this->t('Invalid CSV content format at line') . ' ' . $check_content['line']);
        }
        $check_duplicated_id = $this->isColumnDuplicated($file_path, $delimiter, 'id');
        if (!$check_duplicated_id['status']) {
          $form_state->setErrorByName('csv', $this->t('CSV contains duplicated ID :') . ' ' . $check_duplicated_id['value']);
        }
        $check_fields = $this->isValidFields($entity_type, $bundle, $header);
        if (!$check_fields['status']) {
          $form_state->setErrorByName('csv', $this->t('CSV header contains unknown fields :') . ' ' . implode(', ', $check_fields['fields']));
        }
      }
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * Get csv Header.
   */
  private function getCsvHeader($path, $delimiter) {
    $csv = fopen($path, 'r');
    if ($csv) {
      $header = fgetcsv($csv, NULL, $delimiter);
      return $header;
    }
    return [];
  }

  /**
   * Check csv format.
   */
  private function isValidCsvContent($path, $delimiter, $expected_columns) {
    $index = 0;
    $handle = fopen($path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      if (count($row) != $expected_columns) {
        return ['status' => FALSE, 'line' => $index + 1];
      }
      $index++;
    }

    fclose($handle);
    return ['status' => TRUE];
  }

  /**
   * Check if csv files contains duplicated column value.
   */
  private function isColumnDuplicated($file_path, $delimiter, $column_name) {
    $handle = fopen($file_path, 'r');
    if ($handle === FALSE) {
      return FALSE;
    }

    $header = fgetcsv($handle, 0, $delimiter);
    $column_index = array_search($column_name, $header);
    if ($column_index === FALSE) {
      fclose($handle);
      return FALSE;
    }

    $values = [];
    while (($row = fgetcsv($handle, 0, $delimiter)) !== FALSE) {
      if (isset($row[$column_index])) {
        $value = $row[$column_index];
        if (in_array($value, $values)) {
          fclose($handle);
          return [
            'status' => FALSE,
            'value'  => $value,
          ];
        }
        $values[] = $value;
      }
    }

    fclose($handle);
    return ['status' => TRUE];
  }

  /**
   * Check if field exists for an entity and bundle.
   */
  private function isValidFields($entity_type_id, $bundle, $header) {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    $fields = array_keys($field_definitions);

    $unknown_fields = [];

    foreach ($header as $field) {
      if ($field != 'id' && $field != 'original') {
        $config = $field ? explode('|', $field) : [];
        $extracted_field = $config[1] ?? '';
        $field = $extracted_field ? explode(':', $extracted_field) : [];
        $field = $field[0] ?? '';
        if (!in_array($field, $fields) && $field != 'id' && $field != 'original') {
          $unknown_fields[] = $field;
        }
      }
    }

    if (count($unknown_fields) == 0) {
      return ['status' => TRUE];
    }
    else {
      return [
        'status' => FALSE,
        'fields' => $unknown_fields,
      ];
    }
  }

  /**
   * Get Term Target Bundle By Field.
   */
  private function getTermTargetBundle($entity_type, $bundle, $field) {
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
